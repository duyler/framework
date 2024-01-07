<?php

declare(strict_types=1);

namespace Duyler\Framework;

use Dotenv\Dotenv;
use Duyler\Config\ConfigInterface;
use Duyler\Config\FileConfig;
use Duyler\DependencyInjection\Container;
use Duyler\DependencyInjection\ContainerConfig;
use Duyler\EventBus\BusBuilder;
use Duyler\EventBus\BusInterface;
use Duyler\EventBus\Dto\Config;
use Duyler\Framework\Build\Action;
use Duyler\Framework\Build\Service;
use Duyler\Framework\Build\Subscription;
use Duyler\Framework\Loader\LoaderCollection;
use Duyler\Framework\Loader\LoaderInterface;
use Duyler\Framework\Loader\LoaderService;
use FilesystemIterator;
use LogicException;
use Psr\Container\ContainerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

class Builder
{
    private BusBuilder $busBuilder;
    private FileConfig $config;
    private ContainerInterface $container;
    private BusInterface $bus;
    private string $projectRootDir;
    private RunnerInterface $runner;

    public function __construct(string $typeId)
    {
        $dir = dirname('__DIR__') . '/';

        while (!is_file($dir . '/composer.json')) {
            if (is_dir(realpath($dir))) {
                $dir = $dir . '../';
            }

            if (false === realpath($dir)) {
                throw new LogicException('Cannot auto-detect project dir');
            }
        }

        $this->projectRootDir = $dir;

        $env = Dotenv::createImmutable($this->projectRootDir);

        $containerConfig = new ContainerConfig();
        $containerConfig->withBind([
            LoaderInterface::class => Loader::class
        ]);

        $configCollector = new ConfigCollector($containerConfig);

        $this->config = new FileConfig(
            configDir: $this->projectRootDir . 'config',
            env: $env->safeLoad() + $_ENV + [ConfigInterface::PROJECT_ROOT => $this->projectRootDir],
            externalConfigCollector: $configCollector,
        );

        $this->container = new Container($containerConfig);
        $this->container->set($this->config);

        $this->busBuilder = new BusBuilder(
            new Config(
                bind: $containerConfig->getClassMap(),
                providers: $containerConfig->getProviders(),
                definitions: $containerConfig->getDefinitions(),
            )
        );

        $this->busBuilder->addSharedService($this->config);

        /** @var LoaderInterface $loader */
        $loader = $this->container->get(LoaderInterface::class);
        $runners = $loader->runners();

        if (array_key_exists($typeId, $runners) === false) {
            throw new RuntimeException('Unknown runner type: ' . $typeId);
        }

        /** @var RunnerInterface $runner */
        $this->runner = $this->container->get($runners[$typeId]);
        $this->runner->load(new LoaderService($this->container, $this->busBuilder, $this->config));

        $this->loadPackages();
        $this->loadBuild();
    }

    public function build(): RunnerInterface
    {
        $this->runner->prepare($this->busBuilder->build());
        return $this->runner;
    }

    private function loadBuild(): void
    {
        new Subscription($this->busBuilder);
        new Action($this->busBuilder);
        new Service($this->busBuilder, $this->container);

        $builder = new class () {
            public function collect(string $path): void
            {
                require_once $path;
            }
        };

        $buildPath = $this->projectRootDir . 'build';

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($buildPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );

        foreach ($iterator as $path => $dir) {
            if ($dir->isFile()) {
                if ('php' === strtolower($dir->getExtension())) {
                    $builder->collect($path);
                }
            }
        }
    }

    private function loadPackages(): void
    {
        $loaderCollection = new LoaderCollection();

        /** @var LoaderInterface $loader */
        $loader = $this->container->get(LoaderInterface::class);
        $loader->packages($loaderCollection);

        $packageLoaders = $loaderCollection->get();

        $loaderService = new LoaderService($this->container, $this->busBuilder, $this->config);

        foreach ($packageLoaders as $loaderClass) {
            $packageLoader = $this->container->get($loaderClass);
            $packageLoader->load($loaderService);
        }
    }
}