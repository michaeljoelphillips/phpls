<?php

declare(strict_types=1);

namespace LanguageServer\Server\Cache;

use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use function array_key_exists;
use function is_iterable;
use function time;

class UsageAwareCache implements CacheInterface, CleanableCache
{
    private const DEFAULT_TTL = 180;

    /** @var array<mixed, array<string, mixed>> */
    private array $values = [];

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        if ($this->has($key) === false) {
            return $default;
        }

        $this->touch($key);

        return $this->values[$key]['value'];
    }

    /**
     * @param mixed $key
     */
    private function touch($key) : void
    {
        $this->values[$key]['expiry'] = time() + self::DEFAULT_TTL;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = self::DEFAULT_TTL) : void
    {
        $this->values[$key] = [
            'value' => $value,
            'expiry' => time() + (int) $ttl,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        unset($this->values[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null)
    {
        if (is_iterable($keys) === false) {
            throw new InvalidArgumentException('$keys must be an array or traversable');
        }

        $result = [];
        foreach ($keys as $key) {
            $result[] = $this->get($key);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = self::DEFAULT_TTL)
    {
        if (is_iterable($values) === false) {
            throw new InvalidArgumentException('$keys must be an array or traversable');
        }

        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys)
    {
        if (is_iterable($keys) === false) {
            throw new InvalidArgumentException('$keys must be an array or traversable');
        }

        foreach ($keys as $key) {
            $this->delete($key);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        if (array_key_exists($key, $this->values) === false) {
            return false;
        }

        $value = $this->values[$key];

        return $value['expiry'] > time();
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->values = [];

        return true;
    }

    public function clean() : void
    {
        $time = time();

        foreach ($this->values as $key => $value) {
            if ($value['expiry'] > $time) {
                continue;
            }

            unset($this->values[$key]);
        }
    }
}
