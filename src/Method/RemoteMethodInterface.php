<?php

declare(strict_types=1);

namespace LanguageServer\Method;

use LanguageServer\Exception\LanguageServerException;

interface RemoteMethodInterface
{
    /**
     * @param array $parameters
     *
     * @return mixed|null
     *
     * @throws LanguageServerException
     */
    public function __invoke(array $parameters);
}
