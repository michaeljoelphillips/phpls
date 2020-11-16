# PHPLS

A lightweight [language server](https://langserver.org/) for PHP.

* Completion
* Diagnostics
* Signature Help

## Getting Started

Download the phar from the [releases
page](https://github.com/michaeljoelphillips/phpls/releases).

```
# Over stdio
phpls

# As a TCP client
phpls --mode=client --port=9900

# As a TCP server
phpls --mode=server --port=9900
```

Note: `STDIO` mode is [not supported for users on Windows](https://bugs.php.net/bug.php?id=47918)

### Neovim 0.5.0

Install the [neovim/nvim-lspconfig](https://github.com/neovim/nvim-lspconfig) plugin and add the following to your vimrc:
```
local nvim_lsp = require 'nvim_lsp'
local configs = require 'nvim_lsp/configs'
local util = require 'nvim_lsp/util'
local lsp = vim.lsp

configs.phpls = {
  default_config = {
    cmd = {"php", "-d", "memory_limit=512M", "/usr/local/bin/phpls"};
    filetypes = {"php"};
    root_dir = util.root_pattern("composer.lock", ".git");
    log_level = lsp.protocol.MessageType.Warning;
    settings = {};
  };
}

nvim_lsp.phpls.setup{on_attach=require'completion'.on_attach}
```

### Vim/Neovim 0.4.0

* [prabirshrestha/vim-lsp](https://github.com/prabirshrestha/vim-lsp)
* [autozimu/LanguageClient-neovim](https://github.com/autozimu/LanguageClient-neovim)

## Configuration

PHPLS can be configured via `$XDG_CONFIG_HOME/phpls/config.php`.  See
`config.php.dist`.
