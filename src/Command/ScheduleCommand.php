<?php


namespace EDC\CommandSchedulerBundle\Command;


use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use EDC\CommandSchedulerBundle\Cron\CommandScheduler;
use EDC\CommandSchedulerBundle\Cron\CronCommand;
use EDC\CommandSchedulerBundle\Cron\JobScheduler;
use EDC\CommandSchedulerBundle\Entity\CronJob;
use EDC\CommandSchedulerBundle\Entity\Job;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScheduleCommand extends Command
{
    protected static $defaultName = 'edc-job-queue:schedule';

    /** @var ManagerRegistry */
    private $registry;

    /** @var iterable */
    private $schedulers;

    /** @var iterable */
    private $cronCommands;

    /**
     * ScheduleCommand constructor.
     * @param ManagerRegistry $managerRegistry
     * @param iterable $schedulers
     * @param iterable $cronCommands
     */
    public function __construct(ManagerRegistry $managerRegistry, iterable $schedulers, iterable $cronCommands)
    {
        parent::__construct();
        $this->registry = $managerRegistry;
        $this->schedulers = $schedulers;
        $this->cronCommands = $cronCommands;
    }


    protected function configure()
    {
        $this->setDescription('Schedules jobs');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobSchedulers = $this->populateJobSchedulers();

        if (empty($jobSchedulers)) {
            $output->writeln('No job schedulers found, exiting...');

            return 0;
        }

        $jobsLastRunAt = $this->populateJobsLastRunAt(
            $this->registry->getManagerForClass(CronJob::class),
            $jobSchedulers
        );

        $this->scheduleJobs($output, $jobSchedulers, $jobsLastRunAt);

        return 0;
    }

    /**
     * @param JobScheduler[] $jobSchedulers
     * @param \DateTime[] $jobsLastRunAt
     */
    private function scheduleJobs(OutputInterface $output, array $jobSchedulers, array $jobsLastRunAt)
    {
        foreach ($jobSchedulers as $name => $scheduler) {
            $lastRunAt = $jobsLastRunAt[$name];

            if (!$scheduler->shouldSchedule($name, $lastRunAt)) {
                continue;
            }

            $updatedJobs = $this->updateLastRunAt($name, $lastRunAt);

            if ($updatedJobs) {
                $output->writeln('Scheduling command '.$name);
                $job = $scheduler->createJob($name, $lastRunAt);
                $em = $this->registry->getManagerForClass(Job::class);
                $em->persist($job);
                $em->flush();
            }
        }
    }

    private function updateLastRunAt($commandName, \DateTime $lastRunAt): bool
    {
        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass(CronJob::class);
        $con = $em->getConnection();

        $now = new \DateTime();

        $affectedRows = $con->executeStatement(
            "UPDATE edc_cron_jobs SET lastRunAt = :now WHERE command = :command AND lastRunAt = :lastRunAt",
            array(
                'now' => $now,
                'command' => $commandName,
                'lastRunAt' => $lastRunAt,
            ),
            array(
                'now' => Types::DATETIME_MUTABLE,
                'lastRunAt' => Types::DATETIME_MUTABLE,
            )
        );

        return $affectedRows > 0;
    }

    /**
     * @return JobScheduler[]
     */
    private function populateJobSchedulers()
    {
        $schedulers = [];
        foreach ($this->schedulers as $scheduler) {
            /** @var JobScheduler $scheduler */
            foreach ($scheduler->getCommands() as $name) {
                $schedulers[$name] = $scheduler;
            }
        }

        foreach ($this->cronCommands as $command) {
            /** @var CronCommand $command */
            if (!$command instanceof Command) {
                throw new \RuntimeException('CronCommand should only be used on Symfony commands.');
            }

            $schedulers[$command->getName()] = new CommandScheduler($command->getName(), $command);
        }

        return $schedulers;
    }

    private function populateJobsLastRunAt(ObjectManager $em, array $jobSchedulers)
    {
        $jobsLastRunAt = array();

        $cronJobs = $em->getRepository(CronJob::class)->findAll();

        foreach ($cronJobs as $job) {
            /** @var CronJob $job */
            $jobsLastRunAt[$job->getCommand()] = $job->getLastRunAt();
        }

        foreach (array_keys($jobSchedulers) as $name) {
            if (!isset($jobsLastRunAt[$name])) {
                $job = new CronJob($name);
                $em->persist($job);
                $jobsLastRunAt[$name] = $job->getLastRunAt();
            }
        }
        $em->flush();

        return $jobsLastRunAt;
    }
}