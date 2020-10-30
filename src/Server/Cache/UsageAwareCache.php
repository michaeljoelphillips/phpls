<?php

declare(strict_types=1);

namespace LanguageServer\Server\Cache;

use Psr\SimpleCache\CacheInterface;

use function array_key_exists;
use function assert;
use function is_int;
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
    private function touch($key): void
    {
        $this->values[$key]['expiry'] = time() + self::DEFAULT_TTL;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = self::DEFAULT_TTL)
    {
        assert(is_int($ttl));

        $this->values[$key] = [
            'value' => $value,
            'expiry' => time() + (int) $ttl,
        ];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        unset($this->values[$key]);

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param iterable<string> $keys
     *
     * @return iterable<mixed>
     */
    public function getMultiple($keys, $default = null)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[] = $this->get($key);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param iterable<string, mixed> $values
     */
    public function setMultiple($values, $ttl = self::DEFAULT_TTL)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param iterable<string> $keys
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
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

    public function clean(): void
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
