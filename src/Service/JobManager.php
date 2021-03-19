<?php


namespace EDC\CommandSchedulerBundle\Service;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use EDC\CommandSchedulerBundle\Entity\Job;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Process\Process;

class JobManager
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var ManagerRegistry */
    private $managerRegistry;

    /**
     * JobManager constructor.
     * @param EventDispatcherInterface $dispatcher
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(EventDispatcherInterface $dispatcher, ManagerRegistry $managerRegistry)
    {
        $this->dispatcher = $dispatcher;
        $this->managerRegistry = $managerRegistry;
    }

    private function getBasicCommandLineArgs($env, $verbose): array
    {
        $args = array(
            PHP_BINARY,
            $_SERVER['SYMFONY_CONSOLE_FILE'] ?? $_SERVER['argv'][0],
            '--env='.$env,
        );

        if ($verbose) {
            $args[] = '--verbose';
        }

        return $args;
    }

    /**
     * @param Job $job
     * @param string $env
     * @param bool $verbose
     * @return Process
     */
    public function runJob(Job $job, string $env, bool $verbose)
    {
        $job->setState(Job::STATE_RUNNING);
        $em = $this->getJobManager();
        $em->persist($job);
        $em->flush();

        $args = $this->getBasicCommandLineArgs($env, $verbose);
        $args[] = $job->getCommand();

        foreach ($job->getArgs() as $arg) {
            $args[] = $arg;
        }

        $proc = new Process($args);

        $proc->start();

        return $proc;
    }


    /**
     * @return Job|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findPendingJob()
    {
        $qb = $this->getJobManager()->getRepository(Job::class)->createQueryBuilder(Job::ALIAS);

        $qb->select(Job::ALIAS)
            ->where(Job::ALIAS.'.state = :state')
            ->setParameter('state', Job::STATE_PENDING)
            ->orderBy('j.priority', 'ASC')
            ->addOrderBy('j.id', 'ASC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getJobDispatcher(): EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    public function getJobManager(): EntityManagerInterface
    {
        return $this->managerRegistry->getManagerForClass(Job::class);
    }

    public function markJobAsIncomplete(Job $job)
    {
        $this->closeJob($job, Job::STATE_INCOMPLETE);
    }

    public function closeJob(Job $job, string $finalState)
    {
        /**
         * https://www.doctrine-project.org/projects/doctrine-orm/en/2.8/reference/transactions-and-concurrency.html
         */
        $this->getJobManager()->getConnection()->beginTransaction();

        try {
            $this->updateJobState($job, $finalState);
            $this->getJobManager()->persist($job);
            $this->getJobManager()->flush();
            $this->getJobManager()->getConnection()->commit();
        } catch (\Exception $ex) {
            $this->getJobManager()->getConnection()->rollback();
            $this->getJobManager()->clear(Job::class);
            throw $ex;
        }
    }

    private function updateJobState(Job $job, string $finalState)
    {
        if ($job->isInFinalState()) {
            return;
        }

        $job->setState($finalState);
    }

}