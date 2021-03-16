<?php


namespace EDC\CommandSchedulerBundle\Cron;



use EDC\CommandSchedulerBundle\Entity\Job;

interface CronCommand
{
    /**
     * Returns the job when this command is scheduled.
     *
     * @return Job
     */
    public function createCronJob(\DateTime $lastRunAt): Job;

    /**
     * Returns whether this command should be scheduled.
     *
     * @return boolean
     */
    public function shouldBeScheduled(\DateTime $lastRunAt): bool;
}