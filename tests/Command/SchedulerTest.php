<?php


namespace EDC\CommandSchedulerBundle\Tests\Command;


use EDC\CommandSchedulerBundle\Cron\JobScheduler;
use EDC\CommandSchedulerBundle\Entity\Job;

class SchedulerTest implements JobScheduler
{
    public function getCommands(): array
    {
        return ['edc-test-command'];
    }

    protected function getScheduleInterval(): int
    {
        return 60;
    }

    public function shouldSchedule(string $command, \DateTime $lastRunAt): bool
    {
        return time() - $lastRunAt->getTimestamp() >= $this->getScheduleInterval();
    }

    public function createJob(string $command, \DateTime $lastRunAt): Job
    {
        return new Job('edc-test-command');
    }

}