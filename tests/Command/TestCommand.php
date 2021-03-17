<?php


namespace EDC\CommandSchedulerBundle\Tests\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
    protected static $defaultName = 'edc-test-command';

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

        sleep(1);

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