<?php


namespace EDC\CommandSchedulerBundle\DependencyInjection;


use EDC\CommandSchedulerBundle\Cron\CronCommand;
use EDC\CommandSchedulerBundle\Cron\JobScheduler;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class EDCCommandSchedulerExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if ($config['enabled']) {

            $loader = new XmlFileLoader(
                $container,
                new FileLocator(__DIR__.'/../Resources/config')
            );
            $loader->load('services.xml');
            $loader->load('console.xml');

            $container->registerForAutoconfiguration(JobScheduler::class)
                ->addTag('edc_command_scheduler.scheduler');

            $container->registerForAutoconfiguration(CronCommand::class)
                ->addTag('edc_command_scheduler.cron_command');
        }
    }

    public function prepend(ContainerBuilder $container)
    {
        // TODO: Implement prepend() method.
    }
}