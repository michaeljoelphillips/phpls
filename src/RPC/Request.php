<?php

declare(strict_types=1);

namespace LanguageServer\RPC;

/**
 * @author Michael Phillips <michael.phillips@realpage.com>
 */
class Request
{
    /** @var int */
    public $id;

    /** @var string */
    public $method;

    /** @var object */
    public $params;

    /**
     * @param int    $id
     * @param string $method
     * @param object $params
     */
    public function __construct(int $id, string $method, object $params)
    {
        $this->id = $id;
        $this->method = $method;
        $this->params = $params;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getParams(): object
    {
        return $this->params;
    }
}
