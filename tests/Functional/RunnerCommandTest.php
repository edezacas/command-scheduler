<?php


namespace EDC\CommandSchedulerBundle\Tests\Functional;


use EDC\CommandSchedulerBundle\Entity\CronJob;
use EDC\CommandSchedulerBundle\Entity\Job;
use EDC\CommandSchedulerBundle\Tests\Command\TestCommand;
use EDC\CommandSchedulerBundle\Tests\Command\TestExceptionCommand;

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
        $this->getEm()->clear();

        /** @var Job $job */
        $job = $this->getEm()->getRepository(Job::class)->findOneBy(['id' => 1]);

        $this->assertEquals(Job::STATE_FINISHED, $job->getState());
        $this->assertNotEmpty($job->getOutput());
        $this->assertNotEmpty($job->getMemoryUsage());
        $this->assertEmpty($job->getErrorOutput());
        $this->assertNull($job->getStackTrace());
    }

    public function testFailedRun()
    {
        $testJob = new Job(TestExceptionCommand::getDefaultName());
        $this->getEm()->persist($testJob);
        $this->getEm()->flush();

        $this->assertNotNull($testJob);
        $this->assertEquals(1, $testJob->getId());
        $this->assertNull($testJob->getStackTrace());
        $this->assertNull($testJob->getMemoryUsage());
        $this->assertNull($testJob->getMemoryUsageReal());

        $this->executeRunnerTest();
        $this->getEm()->clear();

        /** @var Job $job */
        $job = $this->getEm()->getRepository(Job::class)->findOneBy(['id' => 1]);

        $this->assertEquals(Job::STATE_FAILED, $job->getState());
        $this->assertNotEmpty($job->getOutput());
        $this->assertNotEmpty($job->getErrorOutput());
        $this->assertNotEmpty($job->getMemoryUsage());
        $this->assertNotNull($job->getStackTrace());
    }
}