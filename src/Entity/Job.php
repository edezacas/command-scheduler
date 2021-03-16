<?php


namespace EDC\CommandSchedulerBundle\Entity;


use Doctrine\ORM\Mapping as ORM;

/**
 * Class Jobs
 * @package EDC\CommandSchedulerBundle\Entity
 *
 * @ORM\Table()
 * @ORM\Entity()
 */
class Job
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /** @ORM\Column(type = "string") */
    private $command;

    /**
     * Job constructor.
     */
    public function __construct(string $command)
    {
        $this->command = $command;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }
}