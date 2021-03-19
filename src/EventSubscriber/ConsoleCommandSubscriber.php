<?php


namespace EDC\CommandSchedulerBundle\EventSubscriber;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use EDC\CommandSchedulerBundle\Entity\Job;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleCommandSubscriber implements EventSubscriberInterface
{
    /** @var ManagerRegistry */
    private $manager;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->manager = $managerRegistry;
    }

    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::ERROR => 'onConsoleError',
            ConsoleEvents::TERMINATE => 'onConsoleTerminate',
        ];
    }


    public function onConsoleError(ConsoleErrorEvent $event)
    {
        $input = $event->getInput();

        if (!$input->hasOption('edc-job-id') || null === $jobId = $input->getOption('edc-job-id')) {
            return;
        }

        $this->getConnection()->executeStatement(
            "UPDATE edc_jobs SET stackTrace = :trace WHERE id = :id",
            array(
                'id' => $jobId,
                'trace' => serialize(
                    $event->getError() ? FlattenException::createFromThrowable($event->getError()) : null
                ),
            ),
            array(
                'id' => Types::INTEGER,
                'trace' => Types::BLOB,
            )
        );
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        $input = $event->getInput();

        if (!$input->hasOption('edc-job-id') || null === $jobId = $input->getOption('edc-job-id')) {
            return;
        }

        $this->getConnection()->executeStatement(
            "UPDATE edc_jobs SET memoryUsage = :memory WHERE id = :id",
            array(
                'id' => $jobId,
                'memory' => memory_get_peak_usage(),
            ),
            array(
                'id' => Types::INTEGER,
                'memory' => Types::INTEGER,
            )
        );
    }

    /**
     * @return Connection
     */
    private function getConnection()
    {
        return $this->manager->getManagerForClass(Job::class)->getConnection();
    }
}