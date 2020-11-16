<?php

declare(strict_types=1);

namespace LanguageServer\Server\Cache;

interface CleanableCache
{
    public function clean(): void;
}
