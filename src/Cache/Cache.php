<?php

namespace Kirby\Cache;

/**
 * Cache foundation
 * This class doesn't do anything
 * and is perfect as foundation for
 * other cache drivers and to be used
 * when the cache is disabled
 *
 * @package   Kirby Cache
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      http://getkirby.com
 * @copyright Bastian Allgeier
 * @license   MIT
 */
abstract class Cache
{

    /**
     * stores all options for the driver
     * @var array
     */
    protected $options = [];

    /**
     * Set all parameters which are needed to connect to the cache storage
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Write an item to the cache for a given number of minutes.
     *
     * <code>
     *   // Put an item in the cache for 15 minutes
     *   Cache::set('value', 'my value', 15);
     * </code>
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  int     $minutes
     * @return boolean
     */
    abstract public function set(string $key, $value, int $minutes = 0): bool;

    /**
     * Adds the prefix to the key if given
     *
     * @param string $key
     * @return string
     */
    protected function key(string $key): string
    {
        if (empty($this->options['prefix']) === false) {
            $key = $this->options['prefix'] . '/' . $key;
        }

        return $key;
    }

    /**
     * Private method to retrieve the cache value
     * This needs to be defined by the driver
     *
     * @param  string $key
     * @return mixed
     */
    abstract public function retrieve(string $key): ?Value;

    /**
     * Get an item from the cache.
     *
     * <code>
     *   // Get an item from the cache driver
     *   $value = Cache::get('value');
     *
     *   // Return a default value if the requested item isn't cached
     *   $value = Cache::get('value', 'default value');
     * </code>
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        // get the Value
        $value = $this->retrieve($key);

        // check for a valid cache value
        if (!is_a($value, Value::class)) {
            return $default;
        }

        // remove the item if it is expired
        if ($value->expires() > 0 && time() >= $value->expires()) {
            $this->remove($key);
            return $default;
        }

        // return the pure value
        return $value->value();
    }

    /**
     * Calculates the expiration timestamp
     *
     * @param  int $minutes
     * @return int
     */
    protected function expiration(int $minutes = 0): int
    {
        // 0 = keep forever
        if ($minutes === 0) {
            return 0;
        }

        // calculate the time
        return time() + ($minutes * 60);
    }

    /**
     * Checks when an item in the cache expires
     *
     * @param  string $key
     * @return mixed
     */
    public function expires(string $key)
    {
        // get the Value object
        $value = $this->retrieve($key);

        // check for a valid Value object
        if (!is_a($value, Value::class)) {
            return false;
        }

        // return the expires timestamp
        return $value->expires();
    }

    /**
     * Checks if an item in the cache is expired
     *
     * @param  string   $key
     * @return boolean
     */
    public function expired(string $key): bool
    {
        $expires = $this->expires($key);

        if ($expires === null) {
            return false;
        } elseif (!is_int($expires)) {
            return true;
        } else {
            return time() >= $expires;
        }
    }

    /**
     * Checks when the cache has been created
     *
     * @param  string $key
     * @return mixed
     */
    public function created(string $key)
    {
        // get the Value object
        $value = $this->retrieve($key);

        // check for a valid Value object
        if (!is_a($value, Value::class)) {
            return false;
        }

        // return the expires timestamp
        return $value->created();
    }

    /**
     * Alternate version for Cache::created($key)
     *
     * @param  string $key
     * @return mixed
     */
    public function modified(string $key)
    {
        return static::created($key);
    }

    /**
     * Determine if an item exists in the cache.
     *
     * @param  string  $key
     * @return boolean
     */
    public function exists(string $key): bool
    {
        return $this->expired($key) === false;
    }

    /**
     * Remove an item from the cache
     *
     * @param  string $key
     * @return boolean
     */
    abstract public function remove(string $key): bool;

    /**
     * Flush the entire cache
     *
     * @return boolean
     */
    abstract public function flush(): bool;

    /**
     * Returns all passed cache options
     *
     * @return array
     */
    public function options(): array
    {
        return $this->options;
    }
}
