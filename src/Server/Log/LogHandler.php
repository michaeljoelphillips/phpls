<?php

declare(strict_types=1);

namespace LanguageServer\Server\Log;

use InvalidArgumentException;
use LanguageServer\Server\MessageSerializer;
use LanguageServer\Server\Protocol\LogMessageParams;
use LanguageServer\Server\Protocol\NotificationMessage;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use React\Promise\Promise;
use React\Socket\ConnectionInterface;
use React\Socket\Server;
use React\Stream\WritableStreamInterface;

class LogHandler extends AbstractProcessingHandler
{
    private const WINDOW_LOG     = 'window/logMessage';
    private const LSP_LOG_LEVELS = [
        'ALERT' => 1,
        'CRITICAL' => 1,
        'EMERGENCY' => 1,
        'ERROR' => 1,
        'WARNING' => 2,
        'INFO' => 3,
        'NOTICE' => 3,
        'DEBUG' => 3,
    ];

    private MessageSerializer $serializer;
    private WritableStreamInterface $stream;

    /**
     * {@inheritdoc}
     */
    public function __construct(MessageSerializer $serializer, $level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->serializer = $serializer;
    }

    /**
     * @param WritableStreamInterface|Server $stream
     */
    public function setStream($stream) : void
    {
        if ($stream instanceof Server) {
            $stream->on('connection', function (ConnectionInterface $connection) : void {
                $this->setStream($connection);
            });

            return;
        }

        if ($stream instanceof Promise) {
            $stream->then(function (ConnectionInterface $connection) : void {
                $this->setStream($connection);
            });

            return;
        }

        if ($stream instanceof WritableStreamInterface) {
            $this->stream = $stream;

            return;
        }

        throw new InvalidArgumentException();
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record) : void
    {
        $message = $this->buildNotificationFromRecord($record);
        $message = $this->serializer->serialize($message);

        $this->stream->write($message);
    }

    /**
     * @param array<string, mixed> $record
     */
    private function buildNotificationFromRecord(array $record) : NotificationMessage
    {
        $params = new LogMessageParams($record['message'], self::LSP_LOG_LEVELS[$record['level_name']]);

        return new NotificationMessage(self::WINDOW_LOG, $params);
    }
}
