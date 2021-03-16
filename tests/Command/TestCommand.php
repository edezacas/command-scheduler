<?php


namespace EDC\CommandSchedulerBundle\Tests\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('edc-test-command')
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

        sleep(10);

        $dateTimeEnd = new \DateTime();

        $output->writeln(
            [
                'Ending test command: '.$dateTimeEnd->format("Y-m-d H:i:s"),
                '======================',
                '',
            ]
        );
    }
}