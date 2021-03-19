<?php


namespace EDC\CommandSchedulerBundle\Command;


use EDC\CommandSchedulerBundle\Entity\Job;
use EDC\CommandSchedulerBundle\Service\JobManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{
    protected static $defaultName = 'edc-command:run';

    /** @var JobManager */
    private $jobManager;

    /** @var array */
    private $runningJobs = array();

    /** @var bool */
    private $finishRun = false;

    /** @var string */
    private $env;

    /** @var boolean */
    private $verbose;

    /** @var OutputInterface */
    private $output;

    /**
     * RunCommand constructor.
     * @param JobManager $jobManager
     */
    public function __construct(JobManager $jobManager)
    {
        parent::__construct();
        $this->jobManager = $jobManager;
    }


    protected function configure()
    {
        $this
            ->setDescription('Runs jobs from the queue.')
            ->addOption('max-runtime', 'r', InputOption::VALUE_REQUIRED, 'The maximum runtime in seconds.', 900)
            ->addOption(
                'max-concurrent-jobs',
                'j',
                InputOption::VALUE_REQUIRED,
                'The maximum number of concurrent jobs.',
                4
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = time();

        $maxRuntime = (integer)$input->getOption('max-runtime');
        if ($maxRuntime <= 0) {
            throw new \InvalidArgumentException('The maximum runtime must be greater than zero.');
        }

        $maxJobs = (integer)$input->getOption('max-concurrent-jobs');
        if ($maxJobs <= 0) {
            throw new \InvalidArgumentException('The maximum number of jobs per queue must be greater than zero.');
        }

        $this->output = $output;
        $this->env = $input->getOption('env');
        $this->verbose = $input->getOption('verbose');

        $this->jobManager->getJobManager()->getConnection()->getConfiguration()->setSQLLogger(null);

        $this->runJobs($maxJobs, $startTime, $maxRuntime);

        return 0;
    }

    private function runJobs($maxJobs, $startTime, $maxRuntime)
    {
        while (true) {
            if ($this->finishRun || time() - $startTime > $maxRuntime) {
                break;
            }

            $this->checkRunningJobs();
            $this->startJobs($maxJobs);
        }

        while (!empty($this->runningJobs)) {
            sleep(5);
            $this->checkRunningJobs();
        }

        if ($this->verbose) {
            $this->output->writeln('All jobs finished, exiting.');
        }
    }

    private function startJobs($maxJobs)
    {
        while (count($this->runningJobs) < $maxJobs) {
            $job = $this->jobManager->findPendingJob();

            if (is_null($job)) {
                return;
            }

            $this->startJob($job);
        }
    }

    private function startJob(Job $job)
    {
        $proc = $this->jobManager->runJob($job, $this->env, $this->verbose);

        $this->runningJobs[] = array(
            'process' => $proc,
            'job' => $job,
            'start_time' => time(),
            'output_pointer' => 0,
            'error_output_pointer' => 0,
        );
    }

    private function checkRunningJobs()
    {
        foreach ($this->runningJobs as $i => &$data) {
            $newOutput = substr($data['process']->getOutput(), $data['output_pointer']);
            $data['output_pointer'] += strlen($newOutput);

            $newErrorOutput = substr($data['process']->getErrorOutput(), $data['error_output_pointer']);
            $data['error_output_pointer'] += strlen($newErrorOutput);

            if ($this->verbose) {
                if (!empty($newOutput)) {
                    $this->output->writeln(
                        'Job '.$data['job']->getId().': '.str_replace(
                            "\n",
                            "\nJob ".$data['job']->getId().": ",
                            $newOutput
                        )
                    );
                }

                if (!empty($newErrorOutput)) {
                    $this->output->writeln(
                        'Job '.$data['job']->getId().': '.str_replace(
                            "\n",
                            "\nJob ".$data['job']->getId().": ",
                            $newErrorOutput
                        )
                    );
                }
            }

            // Check whether this process exceeds the maximum runtime, and terminate if that is
            // the case.
            $runtime = time() - $data['job']->getStartedAt()->getTimestamp();
            if ($data['job']->getMaxRuntime() > 0 && $runtime > $data['job']->getMaxRuntime()) {
                $data['process']->stop(5);

                $this->output->writeln($data['job'].' terminated; maximum runtime exceeded.');
                $this->jobManager->closeJob($data['job'], Job::STATE_TERMINATED);
                unset($this->runningJobs[$i]);

                continue;
            }

            if ($data['process']->isRunning()) {
                // For long running processes, it is nice to update the output status regularly.
                $data['job']->addOutput($newOutput);
                $data['job']->addErrorOutput($newErrorOutput);
                $data['job']->checked();
                $em = $this->jobManager->getJobManager();
                $em->persist($data['job']);
                $em->flush();

                continue;
            }

            $this->output->writeln($data['job'].' finished with exit code '.$data['process']->getExitCode().'.');

            // If the Job exited with an exception, let's reload it so that we
            // get access to the stack trace. This might be useful for listeners.
            $this->jobManager->getJobManager()->refresh($data['job']);

            $data['job']->setExitCode($data['process']->getExitCode());
            $data['job']->setOutput($data['process']->getOutput());
            $data['job']->setErrorOutput($data['process']->getErrorOutput());
            $data['job']->setRuntime(time() - $data['start_time']);

            $newState = 0 === $data['process']->getExitCode() ? Job::STATE_FINISHED : Job::STATE_FAILED;
            $this->jobManager->closeJob($data['job'], $newState);
            unset($this->runningJobs[$i]);
        }

        if (empty($this->runningJobs)) {
            $this->finishRun = true;
        }

        gc_collect_cycles();
    }

}