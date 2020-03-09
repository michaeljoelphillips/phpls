<?php

declare(strict_types=1);

namespace LanguageServer\Console;

use LanguageServer\Server\Server as LanguageServer;
use React\EventLoop\LoopInterface;
use React\Socket\Server;
use React\Stream\CompositeStream;
use React\Stream\ReadableResourceStream;
use React\Stream\WritableResourceStream;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function sprintf;
use const STDIN;
use const STDOUT;

class RunCommand extends Command
{
    // phpcs:ignore
    protected static $defaultName = 'phpls:run';

    private LanguageServer $server;
    private LoopInterface $loop;

    public function __construct(LanguageServer $server, LoopInterface $loop)
    {
        parent::__construct();

        $this->server = $server;
        $this->loop   = $loop;
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Start PHPLS')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Run over TCP with the specified port');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : void
    {
        $stream = $this->openStream($input->getOption('port'));

        $this->server->listen($stream);

        $this->loop->run();
    }

    /**
     * @return Server|CompositeStream
     */
    private function openStream(?string $port)
    {
        if ($port === null) {
            return new CompositeStream(
                new ReadableResourceStream(STDIN, $this->loop),
                new WritableResourceStream(STDOUT, $this->loop)
            );
        }

        return new Server(sprintf('127.0.0.1:%d', $port), $this->loop);
    }
}
