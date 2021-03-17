<?php


namespace EDC\CommandSchedulerBundle\Tests\Functional;


use EDC\CommandSchedulerBundle\Entity\CronJob;
use EDC\CommandSchedulerBundle\Entity\Job;
use EDC\CommandSchedulerBundle\Tests\Command\TestCommand;

class RunnerCommandTest extends BaseTest
{

    public function testFinishedRun()
    {
        $testJob = new Job(TestCommand::getDefaultName());
        $this->getEm()->persist($testJob);
        $this->getEm()->flush();

        $this->assertNotNull($testJob);
        $this->assertEquals(1, $testJob->getId());
        $this->assertNull($testJob->getStackTrace());
        $this->assertNull($testJob->getMemoryUsage());
        $this->assertNull($testJob->getMemoryUsageReal());

        $output = $this->executeRunnerTest();


        var_dump($output);
    }
}