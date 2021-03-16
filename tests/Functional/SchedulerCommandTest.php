<?php


namespace EDC\CommandSchedulerBundle\Tests\Functional;


use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class SchedulerCommandTest extends BaseTest
{

    public function testExecute()
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        $command = $application->find('edc-job-queue:schedule');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('edc-test-command', $output);
    }
}