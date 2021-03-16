<?php


namespace EDC\CommandSchedulerBundle\Tests\Command;


use EDC\CommandSchedulerBundle\Cron\CronCommand;
use EDC\CommandSchedulerBundle\Entity\Job;

class CronCommandTest implements CronCommand
{
    public function createCronJob(\DateTime $lastRunAt): Job
    {
        // TODO: Implement createCronJob() method.
    }

    public function shouldBeScheduled(\DateTime $lastRunAt): bool
    {
        // TODO: Implement shouldBeScheduled() method.
    }

}