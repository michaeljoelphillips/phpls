<?php

declare(strict_types=1);

namespace LanguageServer\Console;

use DI\Container;
use LanguageServer\Server\Cache\ThresholdCacheMonitor;
use LanguageServer\Server\Cache\TtlCacheMonitor;
use LanguageServer\Server\Server as LanguageServer;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{
    // phpcs:ignore
    protected static $defaultName = 'phpls:run';

    private Container $container;

    public function __construct(Container $container)
    {
        parent::__construct();

        $this->container = $container;
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Start PHPLS')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Run over TCP with the specified port');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->container->set('server.port', $input->getOption('port'));

        $loop   = $this->container->get(LoopInterface::class);
        $stream = $this->container->get('stream');
        $server = $this->container->get(LanguageServer::class);

        $logger         = $this->container->get(LoggerInterface::class);
        $parserCache    = $this->container->get('parserCache');
        $reflectorCache = $this->container->get('reflectorCache');

        (new ThresholdCacheMonitor($logger, $parserCache, $reflectorCache))(15, $loop);
        (new TtlCacheMonitor($logger, $parserCache, $reflectorCache))(60, $loop);

        $server->listen($stream);

        $loop->run();

        return 0;
    }
}
