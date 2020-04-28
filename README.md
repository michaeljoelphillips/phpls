# PHPLS - A Language Server for PHP

## Getting Started

PHPLS is available for download as a phar via the [releases
page](https://github.com/michaeljoelphillips/phpls/releases).

PHPLS is compatible with any Language Client that can communicate over `STDIO`
or TCP.  PHPLS will run over `STDIO` by default, but can be ran over TCP via
the `--mode` and `--port` switches.

```
# Over stdio
phpls

# As a TCP client
phpls --mode=client --port=9900

# As a TCP server
phpls --mode=server --port=9900
```

Note: `STDIO` is not a working option for users on Windows due to a lack of
support for non-blocking stdio.

## Supported Clients

* [michaeljoelphillips/vscode-phpls](https://github.com/michaeljoelphillips/vscode-phpls)
* [michaeljoelphillips/nvim-lsp](https://github.com/michaeljoelphillips/nvim-lsp)

## Configuration

PHPLS can be configured via `$XDG_CONFIG_HOME/phpls/config.php` where
`config.php` is a PHP file returning an array of configuration options.  See
the example below for the full list of options:

```php
<?php

return [
    'log' => [
        /**
         * Boolean value
         */
        'enabled' => true,

        /**
         * Enum
         *
         * info, debug
         */
        'level' => 'info',

        /**
         * String
         */
        'path' => '/tmp/phpls.log',
    ],
];
```

## Requirements

PHPLS will only work with Composer projects running PHP7.  Projects that do not
use Composer for autoloading cannot be analyzed by PHPLS at this time.

## Features

PHPLS is designed to be lightweight and fast, without the need to index project
files.  Source code is parsed by the universal
[nikic/php-parser](https://github.com/nikic/php-parser) and
[roave/better-reflection](https://github.com/roave/better-reflection).

This project is still a work in progress.  Features that have been implemented
are expected to improve with time:

| Feature            | Status       |
|--------------------|------------- |
| Signature Help     | Working      |
| Completion         | Working      |
| Hover              | Planned      |
| Jump To Definition | Planned      |
| Find References    | Planned      |
| Diagnostics        | Unplanned    |
