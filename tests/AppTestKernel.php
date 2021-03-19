<?php

namespace EDC\CommandSchedulerBundle\Tests;

// Set-up composer auto-loading if Client is insulated.
call_user_func(
    function () {
        if (!is_file($autoloadFile = __DIR__.'/../vendor/autoload.php')) {
            throw new \LogicException(
                'The autoload file "vendor/autoload.php" was not found. Did you run "composer install --dev"?'
            );
        }

        require_once $autoloadFile;
    }
);

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use EDC\CommandSchedulerBundle\EDCCommandSchedulerBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
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
            new MonologBundle(),
            new EDCCommandSchedulerBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $confDir = $this->getProjectDir().'/tests/config';
        $loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
    }
}