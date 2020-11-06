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
use Symfony\Component\Console\Style\SymfonyStyle;

use function implode;
use function in_array;
use function sprintf;

class RunCommand extends Command
{
    private const MODES = [
        'stdio',
        'client',
        'server',
    ];

    // phpcs:ignore
    protected static $defaultName = 'phpls:run';

    private Container $container;

    public function __construct(Container $container)
    {
        parent::__construct();

        $this->container = $container;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Start PHPLS')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Run over TCP with the specified port')
            ->addOption('mode', null, InputOption::VALUE_OPTIONAL, 'Set the TCP mode', 'stdio');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setOptions($input, $output);

        $loop   = $this->container->get(LoopInterface::class);
        $stream = $this->container->get('stream');
        $server = $this->container->get(LanguageServer::class);

        $logger         = $this->container->get(LoggerInterface::class)->withName('cache');
        $parserCache    = $this->container->get('parserCache');
        $reflectorCache = $this->container->get('reflectorCache');

        (new ThresholdCacheMonitor($logger, $parserCache, $reflectorCache))(15, $loop);
        (new TtlCacheMonitor($parserCache, $reflectorCache))(60, $loop);

        $server->listen($stream);
        $loop->run();

        return 0;
    }

    private function setOptions(InputInterface $input, OutputInterface $output): void
    {
        $io   = new SymfonyStyle($input, $output);
        $mode = $input->getOption('mode');
        $port = $input->getOption('port');

        if (in_array($mode, self::MODES) === false) {
            $io->getErrorStyle()->error(
                sprintf("Option 'mode' must be one of: %s", implode(', ', self::MODES)),
            );

            exit(1);
        }

        if ($mode !== 'stdio' && $port === null) {
            $io->getErrorStyle()->error("Option 'port' is required");

            exit(1);
        }

        $this->container->set('port', $input->getOption('port'));
        $this->container->set('mode', $input->getOption('mode'));
    }
}
