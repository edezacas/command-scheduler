<?php


namespace EDC\CommandSchedulerBundle\Cron;


use EDC\CommandSchedulerBundle\Entity\Job;


class CommandScheduler implements JobScheduler
{
    /** @var string */
    private $name;

    /** @var CronCommand */
    private $command;

    public function __construct(string $name, CronCommand $command)
    {
        $this->name = $name;
        $this->command = $command;
    }

    public function getCommands(): array
    {
        return [$this->name];
    }

    public function shouldSchedule(string $command, \DateTime $lastRunAt): bool
    {
        return $this->command->shouldBeScheduled($lastRunAt);
    }

    public function createJob(string $command, \DateTime $lastRunAt): Job
    {
        return $this->command->createCronJob($lastRunAt);
    }

}