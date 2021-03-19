<?php


namespace EDC\CommandSchedulerBundle\Tests\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestExceptionCommand extends Command
{
    protected static $defaultName = 'edc-test-exeption-command';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::getDefaultName())
            ->setDescription('Simple test command');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dateTimeStart = new \DateTime();

        $output->writeln(
            [
                'Starting test command: '.$dateTimeStart->format("Y-m-d H:i:s"),
                '======================',
                '',
            ]
        );

        throw new \Exception('SOME EXCEPTION');

        $dateTimeEnd = new \DateTime();

        $output->writeln(
            [
                'Ending test command: '.$dateTimeEnd->format("Y-m-d H:i:s"),
                '======================',
                '',
            ]
        );

        return 0;
    }
}