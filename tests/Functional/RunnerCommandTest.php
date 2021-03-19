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

        $this->executeRunnerTest();

        /** @var Job $job */
        $job = $this->getEm()->getRepository(Job::class)->findOneBy(['id' => 1]);

        $this->assertEquals('finished', $job->getState());
    }
}