#!/bin/sh

export XDEBUG_CONFIG="idekey=xdebug"

php -d memory_limit=256m /home/nomad/Code/phpls/bin/language-server.php ${@:1} 2>&1 | tee -a /tmp/crash.log

# /home/nomad/Code/phpls/build/phpls.phar
# /usr/local/bin/phpls 2>&1 | tee -a /tmp/crash.log
