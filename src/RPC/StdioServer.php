<?php

declare(strict_types=1);

namespace LanguageServer\RPC;

use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class StdioServer extends Server
{
    /**
     * @param ReadableStreamInterface $input
     * @param WritableStreamInterface $output
     */
    public function __construct(ReadableStreamInterface $input, WritableStreamInterface $output)
    {
        $input->on(
            'data',
            function (string $data) use ($output) {
                $this->handle($data, $output);
            }
        );
    }
}
