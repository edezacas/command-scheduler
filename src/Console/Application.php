<?php


namespace EDC\CommandSchedulerBundle\Console;

use Symfony\Bundle\FrameworkBundle\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpKernel\KernelInterface;


class Application extends BaseApplication
{
    public function __construct(KernelInterface $kernel)
    {
        parent::__construct($kernel);
        $inputDefinition = $this->getDefinition();
        $inputDefinition->addOption(
            new InputOption('--edc-job-id', null, InputOption::VALUE_REQUIRED, 'The ID of the Job.')
        );
    }

}