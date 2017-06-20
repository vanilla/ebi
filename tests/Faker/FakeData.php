<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi\Tests\Faker;

use Faker\Address;
use Faker\Company;
use Faker\DateTime;
use Faker\Internet;
use Faker\Lorem;
use Faker\Name;
use Faker\PhoneNumber;
use Traversable;

class FakeData implements \IteratorAggregate, \ArrayAccess {
    private $path;
    private $parent = null;
    private $data = [];
    private $locked = false;

    protected static $providers;
    protected static $wildProviders;

    public function __construct($path = '', FakeData $parent = null) {
        $this->path = ltrim($path, '/');
        $this->parent = $parent;
    }

    public function fakeData($path) {
        if (empty($path) || $path === 'this') {
            return $this;
        }

        $search = preg_replace('`\[\d+\]`', '', strtolower($path));

        // Look for an exact match.
        $providers = static::getProviders();
        for ($subpath = $search; !empty($subpath); $subpath = ltrim(strstr($subpath, '/'), '/')) {
            if (isset($providers[$subpath])) {
                return call_user_func($providers[$subpath], $this, $search);
            }
        }

        // Look for a wildcard match.
        foreach (static::$wildProviders as $pattern => $generator) {
            if (fnmatch($pattern, $search)) {
                return call_user_func($generator, $this, $search);
            }
        }

        // No match so return a new fake data.
        return new FakeData($path, $this);
    }

    public static function getProviders() {
        self::ensureProviders();
        return static::$providers;
    }

    public static function addProvider($paths, callable $generator) {
        self::ensureProviders();

        foreach ((array)$paths as $path) {
            $path = strtolower(trim($path, '/'));

            if (strpos($path, '*') === false) {
                static::$providers[$path] = $generator;
            } else {
                static::$wildProviders[$path] = $generator;
            }
        }
    }

    public static function addProviderClass($class, $path = '') {
        self::ensureProviders();

        $methods = get_class_methods($class);
        foreach ($methods as $method) {
            static::addProvider($path.$method, [$class, $method]);
        }
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator() {
        $this->locked = true;
        if (empty($this->data)) {
            for ($i = 0; $i < 10; $i++) {
                $this->data[$i] = new FakeData($this->path."[{$i}]", $this);
            }
        }

        return new \ArrayIterator($this->data);
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset) {
        if ($this->locked && !isset($this->data[$offset])) {
            return false;
        }
        return true;
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset) {
        if (isset($this->data[$offset])) {
            return $this->data[$offset];
        } elseif ($this->locked && !isset($this->data[$offset])) {
            return null;
        }

        $path = ltrim("{$this->path}/$offset", '/');
        $r = $this->data[$offset] = $this->fakeData($path);
        return $r;
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value) {
        if ($offset === null) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

    public function __toString() {
        return Company::bs();
    }

    private static function ensureProviders() {
        if (static::$providers !== null) {
            return;
        }
        static::$providers = [];
        static::$wildProviders = [];

        // Names.
        static::addProviderClass(Name::class);
        static::addProvider('fullName', [Name::class, 'name']);

        // Phone numbers.
        static::addProviderClass(PhoneNumber::class);

        // Internet.
        static::addProvider('*email', function (FakeData $data) {
            return Internet::email($data['fullName']);
        });
        static::addProvider('username', function (FakeData $data) {
            return Internet::userName($data['fullName']);
        });
        static::addProvider(['slug', 'urlcode'], function (FakeData $data) {
            return Internet::slug($data['name'], '-');
        });
        static::addProvider(['domainName', 'domain'], [Internet::class, 'domainName']);
        static::addProvider('*ipaddress', [Internet::class, 'ipv4Address']);

        // Date/Time.
        static::addProvider(['date*', '*date'], function () {
            return DateTime::date(\DateTime::RFC3339);
        });

        // Address.
        static::addProvider(['street', 'address'], function () {
            return Address::streetAddress(mt_rand(0, 1) == 1);
        });
        static::addProvider('city', [Address::class, 'city']);
        static::addProvider(['state', 'province'], [Address::class, 'stateAbbr']);
        static::addProvider(['zip', 'zipCode'], [Address::class, 'zip']);
        static::addProvider(['postalCode', 'postCode'], [Address::class, 'postCode']);
        static::addProvider('country', [Address::class, 'country']);

        // Posts.
        static::addProvider(['discussions/name', 'title', 'subject'], function () {
            switch (mt_rand(1, 2)) {
                case 1:
                    return Social::clickBait();
                case 2:
                    return Social::question();
            }
        });
        static::addProvider(['headline'], [Social::class, 'clickBait']);
        static::addProvider(['question'], [Social::class, 'question']);
        static::addProvider(['body', 'post'], function () {
            return Lorem::paragraph(mt_rand(1, 3));
        });
    }
}
