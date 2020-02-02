# PHPLS - A Language Server for PHP

## Installation

PHPLS is available for download as a phar via the [releases
page](https://github.com/michaeljoelphillips/phpls/releases).

Once downloaded, PHPLS will run with any Language Client over a `stdio` pipe.
For NeoVim users, see
[michaeljoelphillips/nvim-lsp](https://github.com/michaeljoelphillips/nvim-lsp).

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
use Composer for autoloading cannot be analyzed by PHPLS.

## Features

PHPLS is designed to be lightweight and fast, without the need to index project
files.  Source code is parsed by the universal
[nikic/php-parser](https://github.com/nikic/php-parser) and analyzed by
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
