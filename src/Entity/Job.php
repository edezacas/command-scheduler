<?php


namespace EDC\CommandSchedulerBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use EDC\CommandSchedulerBundle\Exception\InvalidStateTransitionException;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

/**
 * Class Jobs
 * @package EDC\CommandSchedulerBundle\Entity
 *
 * @ORM\Table(name = "edc_jobs")
 * @ORM\Entity()
 */
class Job
{
    /** State if job is inserted, but not yet ready to be started. */
    const STATE_NEW = 'new';

    /**
     * State if job is inserted, and might be started.
     *
     * It is important to note that this does not automatically mean that all
     * jobs of this state can actually be started, but you have to check
     * isStartable() to be absolutely sure.
     *
     * In contrast to NEW, jobs of this state at least might be started,
     * while jobs of state NEW never are allowed to be started.
     */
    const STATE_PENDING = 'pending';

    /** State if job was never started, and will never be started. */
    const STATE_CANCELED = 'canceled';

    /** State if job was started and has not exited, yet. */
    const STATE_RUNNING = 'running';

    /** State if job exists with a successful exit code. */
    const STATE_FINISHED = 'finished';

    /** State if job exits with a non-successful exit code. */
    const STATE_FAILED = 'failed';

    /** State if job exceeds its configured maximum runtime. */
    const STATE_TERMINATED = 'terminated';

    /**
     * State if an error occurs in the runner command.
     *
     * The runner command is the command that actually launches the individual
     * jobs. If instead an error occurs in the job command, this will result
     * in a state of FAILED.
     */
    const STATE_INCOMPLETE = 'incomplete';

    const PRIORITY_LOW = -5;
    const PRIORITY_DEFAULT = 0;
    const PRIORITY_HIGH = 5;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type = "string")
     */
    private $command;

    /**
     * @var string
     * @ORM\Column(type = "string", length = 15)
     */
    private $state;

    /**
     * @var int
     * @ORM\Column(type = "smallint")
     */
    private $priority = 0;

    /**
     * @var \DateTime
     * @ORM\Column(type = "datetime", name="createdAt")
     */
    private $createdAt;

    /**
     * @var \DateTime|null
     * @ORM\Column(type = "datetime", name="startedAt", nullable = true)
     */
    private $startedAt;

    /**
     * @var \DateTime|null
     * @ORM\Column(type = "datetime", name="checkedAt", nullable = true)
     */
    private $checkedAt;

    /** @ORM\Column(type = "json") */
    private $args;

    /**
     * @var string|null
     * @ORM\Column(type = "text", nullable = true)
     */
    private $output;

    /**
     * @var string|null
     * @ORM\Column(type = "text", name="errorOutput", nullable = true)
     */
    private $errorOutput;

    /**
     * @var int|null
     * @ORM\Column(type = "smallint", name="exitCode", nullable = true, options = {"unsigned": true})
     */
    private $exitCode;

    /**
     * @var int
     * @ORM\Column(type = "smallint", name="maxRuntime", options = {"unsigned": true})
     */
    private $maxRuntime = 0;

    /**
     * @var int
     * @ORM\Column(type = "smallint", name="maxRetries", options = {"unsigned": true})
     */
    private $maxRetries = 0;

    /**
     * @var int|null
     * @ORM\Column(type = "integer", name="memoryUsage", nullable = true, options = {"unsigned": true})
     */
    private $memoryUsage;

    /**
     * @var int|null
     * @ORM\Column(type = "integer", name="memoryUsageReal", nullable = true, options = {"unsigned": true})
     */
    private $memoryUsageReal;

    /** @ORM\Column(type = "blob", name="stackTrace", nullable = true) */
    private $stackTrace;

    public static function create(
        $command,
        array $args = array(),
        $confirmed = true,
        $priority = self::PRIORITY_DEFAULT
    ) {
        return new self($command, $args, $confirmed, $priority);
    }

    public static function isNonSuccessfulFinalState($state)
    {
        return in_array(
            $state,
            array(self::STATE_CANCELED, self::STATE_FAILED, self::STATE_INCOMPLETE, self::STATE_TERMINATED),
            true
        );
    }

    public static function getStates()
    {
        return array(
            self::STATE_NEW,
            self::STATE_PENDING,
            self::STATE_CANCELED,
            self::STATE_RUNNING,
            self::STATE_FINISHED,
            self::STATE_FAILED,
            self::STATE_TERMINATED,
            self::STATE_INCOMPLETE,
        );
    }

    /**
     * Job constructor.
     * @param $command
     * @param array $args
     * @param bool $confirmed
     * @param int $priority
     */
    public function __construct($command, array $args = array(), $confirmed = true, $priority = self::PRIORITY_DEFAULT)
    {
        $this->command = $command;
        $this->args = $args;
        $this->state = $confirmed ? self::STATE_PENDING : self::STATE_NEW;
        $this->priority = $priority * -1;
        $this->createdAt = new \DateTime();
        $this->executeAfter = new \DateTime();
        $this->executeAfter = $this->executeAfter->modify('-1 second');
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return \DateTime|null
     */
    public function getStartedAt(): ?\DateTime
    {
        return $this->startedAt;
    }

    /**
     * @return \DateTime|null
     */
    public function getCheckedAt(): ?\DateTime
    {
        return $this->checkedAt;
    }

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @return string|null
     */
    public function getOutput(): ?string
    {
        return $this->output;
    }

    /**
     * @return string|null
     */
    public function getErrorOutput(): ?string
    {
        return $this->errorOutput;
    }

    /**
     * @return int|null
     */
    public function getExitCode(): ?int
    {
        return $this->exitCode;
    }

    /**
     * @return int
     */
    public function getMaxRuntime(): int
    {
        return $this->maxRuntime;
    }

    /**
     * @return int
     */
    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    /**
     * @return int|null
     */
    public function getMemoryUsage(): ?int
    {
        return $this->memoryUsage;
    }

    /**
     * @return int|null
     */
    public function getMemoryUsageReal(): ?int
    {
        return $this->memoryUsageReal;
    }

    /**
     * @return mixed
     */
    public function getStackTrace()
    {
        return $this->stackTrace;
    }

    /**
     * @param string|null $output
     */
    public function setOutput(?string $output): void
    {
        $this->output = $output;
    }

    /**
     * @param string|null $errorOutput
     */
    public function setErrorOutput(?string $errorOutput): void
    {
        $this->errorOutput = $errorOutput;
    }

    /**
     * @param int|null $exitCode
     */
    public function setExitCode(?int $exitCode): void
    {
        $this->exitCode = $exitCode;
    }

    /**
     * @param int $maxRuntime
     */
    public function setMaxRuntime(int $maxRuntime): void
    {
        $this->maxRuntime = $maxRuntime;
    }

    /**
     * @param int $maxRetries
     */
    public function setMaxRetries(int $maxRetries): void
    {
        $this->maxRetries = $maxRetries;
    }

    /**
     * @param int|null $memoryUsage
     */
    public function setMemoryUsage(?int $memoryUsage): void
    {
        $this->memoryUsage = $memoryUsage;
    }

    /**
     * @param int|null $memoryUsageReal
     */
    public function setMemoryUsageReal(?int $memoryUsageReal): void
    {
        $this->memoryUsageReal = $memoryUsageReal;
    }

    /**
     * @param FlattenException $exception
     */
    public function setStackTrace(FlattenException $exception): void
    {
        $this->stackTrace = $exception;
    }

    public function isNew()
    {
        return self::STATE_NEW === $this->state;
    }

    public function isPending()
    {
        return self::STATE_PENDING === $this->state;
    }

    public function isCanceled()
    {
        return self::STATE_CANCELED === $this->state;
    }

    public function isRunning()
    {
        return self::STATE_RUNNING === $this->state;
    }

    public function isTerminated()
    {
        return self::STATE_TERMINATED === $this->state;
    }

    public function isFailed()
    {
        return self::STATE_FAILED === $this->state;
    }

    public function isFinished()
    {
        return self::STATE_FINISHED === $this->state;
    }

    public function isIncomplete()
    {
        return self::STATE_INCOMPLETE === $this->state;
    }

    public function isInFinalState()
    {
        return !$this->isNew() && !$this->isPending() && !$this->isRunning();
    }

    public function isStartable()
    {
        return true;
    }

    public function setState($newState)
    {
        if ($newState === $this->state) {
            return;
        }

        switch ($this->state) {
            case self::STATE_NEW:
                if (!in_array($newState, array(self::STATE_PENDING, self::STATE_CANCELED), true)) {
                    throw new InvalidStateTransitionException(
                        $this,
                        $newState,
                        array(self::STATE_PENDING, self::STATE_CANCELED)
                    );
                }

                if (self::STATE_CANCELED === $newState) {
                    $this->closedAt = new \DateTime();
                }

                break;

            case self::STATE_PENDING:
                if (!in_array($newState, array(self::STATE_RUNNING, self::STATE_CANCELED), true)) {
                    throw new InvalidStateTransitionException(
                        $this,
                        $newState,
                        array(self::STATE_RUNNING, self::STATE_CANCELED)
                    );
                }

                if ($newState === self::STATE_RUNNING) {
                    $this->startedAt = new \DateTime();
                    $this->checkedAt = new \DateTime();
                } else {
                    if ($newState === self::STATE_CANCELED) {
                        $this->closedAt = new \DateTime();
                    }
                }

                break;

            case self::STATE_RUNNING:
                if (!in_array(
                    $newState,
                    array(
                        self::STATE_FINISHED,
                        self::STATE_FAILED,
                        self::STATE_TERMINATED,
                        self::STATE_INCOMPLETE,
                    )
                )) {
                    throw new InvalidStateTransitionException(
                        $this,
                        $newState,
                        array(
                            self::STATE_FINISHED,
                            self::STATE_FAILED,
                            self::STATE_TERMINATED,
                            self::STATE_INCOMPLETE,
                        )
                    );
                }

                $this->closedAt = new \DateTime();

                break;

            case self::STATE_FINISHED:
            case self::STATE_FAILED:
            case self::STATE_TERMINATED:
            case self::STATE_INCOMPLETE:
                throw new InvalidStateTransitionException($this, $newState);

            default:
                throw new \LogicException('The previous cases were exhaustive. Unknown state: '.$this->state);
        }

        $this->state = $newState;
    }

    private function mightHaveStarted()
    {
        if (null === $this->id) {
            return false;
        }

        if (self::STATE_NEW === $this->state) {
            return false;
        }

        if (self::STATE_PENDING === $this->state && !$this->isStartable()) {
            return false;
        }

        return true;
    }

    public function __toString()
    {
        return sprintf('Job(id = %s, command = "%s")', $this->id, $this->command);
    }
}