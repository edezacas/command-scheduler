<?php

namespace EDC\CommandSchedulerBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use EDC\CommandSchedulerBundle\EDCCommandSchedulerBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppTestKernel extends Kernel
{
    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function __construct(string $environment, bool $debug)
    {
        parent::__construct('test', false);
    }

    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new EDCCommandSchedulerBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $confDir = $this->getProjectDir().'/tests/config';
        $loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');

    }
}