<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi;


class Ebi {
    /**
     * @var string
     */
    protected $cachePath;
    /**
     * @var callable[]
     */
    protected $functions;
    /**
     * @var TemplateLoaderInterface
     */
    private $templateLoader;
    /**
     * @var CompilerInterface
     */
    private $compiler;
    /**
     * @var callable[]
     */
    private $components = [];
    /**
     * @var array
     */
    private $meta;

    /**
     * Ebi constructor.
     *
     * @param TemplateLoaderInterface $templateLoader Used to load template sources from component names.
     * @param string $cachePath The path to cache compiled templates.
     * @param CompilerInterface $compiler The compiler used to compile templates.
     */
    public function __construct(TemplateLoaderInterface $templateLoader, $cachePath, CompilerInterface $compiler = null) {
        $this->templateLoader = $templateLoader;
        $this->cachePath = $cachePath;
        $this->compiler = $compiler ?: new Compiler();

        $this->defineFunction('abs');
        $this->defineFunction('ceil');
        $this->defineFunction('componentExists', [$this, 'componentExists']);
        $this->defineFunction('count');
        $this->defineFunction('formatDate', [$this, 'formatDate']);
        $this->defineFunction('empty');
        $this->defineFunction('floor');
        $this->defineFunction('htmlEncode', 'htmlspecialchars');
        $this->defineFunction('join');
        $this->defineFunction('lcase', $this->mb('strtolower'));
        $this->defineFunction('lcfirst');
        $this->defineFunction('ltrim');
        $this->defineFunction('max');
        $this->defineFunction('min');
        $this->defineFunction('queryEncode', 'http_build_query');
        $this->defineFunction('round');
        $this->defineFunction('rtrim');
        $this->defineFunction('sprintf');
        $this->defineFunction('strlen', $this->mb('strlen'));
        $this->defineFunction('substr', $this->mb('substr'));
        $this->defineFunction('trim');
        $this->defineFunction('ucase', $this->mb('strtoupper'));
        $this->defineFunction('ucfirst');
        $this->defineFunction('ucwords');
        $this->defineFunction('urlencode', 'rawurlencode');

        $this->defineFunction('@class', [$this, 'attributeClass']);
    }

    /**
     * Register a runtime function.
     *
     * @param string $name The name of the function.
     * @param callable $function The function callback.
     */
    public function defineFunction($name, $function = null) {
        if ($function === null) {
            $function = $name;
        }

        $this->functions[strtolower($name)] = $function;
        $this->compiler->defineFunction($name, $function);
    }

    private function mb($func) {
        return function_exists("mb_$func") ? "mb_$func" : $func;
    }

    /**
     * Write a component to the output buffer.
     *
     * @param string $component The name of the component.
     * @param array ...$args
     */
    public function write($component, ...$args) {
        $component = strtolower($component);
        if ($callback = $this->lookup($component)) {
            call_user_func($callback, ...$args);
        } else {
            trigger_error("Could not find component $component.", E_USER_NOTICE);
        }
    }

    /**
     * Lookup a component with a given name.
     *
     * @param string $component The component to lookup.
     * @return callable|null Returns the component function or **null** if the component is not found.
     */
    public function lookup($component) {
        $component = strtolower($component);
        $key = $this->componentKey($component);

        if (!array_key_exists($key, $this->components)) {
            $this->loadComponent($component);
        }

        if (isset($this->components[$key])) {
            return $this->components[$key];
        } else {
            // Mark a tombstone to the component array so it doesn't keep getting loaded.
            $this->components[$key] = null;
            return null;
        }
    }

    /**
     * Check to see if a component exists.
     *
     * @param string $component The name of the component.
     * @param bool $loader Whether or not to use the component loader or just look in the component cache.
     * @return bool Returns **true** if the component exists or **false** otherwise.
     */
    public function componentExists($component, $loader = true) {
        $componentKey = $this->componentKey($component);
        if (array_key_exists($componentKey, $this->components)) {
            return $this->components[$componentKey] !== null;
        } elseif ($loader) {
            return !empty($this->templateLoader->cacheKey($component));
        }
        return false;
    }

    /**
     * Strip the namespace off a component name to get the component key.
     *
     * @param string $component The full name of the component with a possible namespace.
     * @return string Returns the component key.
     */
    protected function componentKey($component) {
        if (false !== $pos = strpos($component, ':')) {
            $component = substr($component, $pos + 1);
        }
        return strtolower($component);
    }

    /**
     * Load a component.
     *
     * @param string $component The name of the component to load.
     * @return callable|null Returns the component or **null** if the component isn't found.
     */
    protected function loadComponent($component) {
        $cacheKey = $this->templateLoader->cacheKey($component);
        // The template loader can tell us a template doesn't exist when giving the cache key.
        if (empty($cacheKey)) {
            return null;
        }

        $cachePath = "{$this->cachePath}/$cacheKey.php";
        $componentKey = $this->componentKey($component);

        if (!file_exists($cachePath)) {
            $src = $this->templateLoader->load($component);
            return $this->compile($componentKey, $src, $cacheKey);
        } else {
            return $this->includeComponent($componentKey, $cachePath);
        }
    }

    /**
     * Check to see if a specific cache key exists in the cache.
     *
     * @param string $cacheKey The cache key to check.
     * @return bool Returns **true** if there is a cache key at the file or **false** otherwise.
     */
    public function cacheKeyExists($cacheKey) {
        $cachePath = "{$this->cachePath}/$cacheKey.php";
        return file_exists($cachePath);
    }

    /**
     * Compile a component from source, cache it and include it.
     *
     * @param string $component The name of the component.
     * @param string $src The component source.
     * @param string $cacheKey The cache key of the component.
     * @return callable|null Returns the compiled component closure.
     */
    public function compile($component, $src, $cacheKey) {
        $cachePath = "{$this->cachePath}/$cacheKey.php";
        $component = strtolower($component);

        $php = $this->compiler->compile($src, ['basename' => $component]);
        $comment = "/*\n".str_replace('*/', '❄/', trim($src))."\n*/";

        $this->filePutContents($cachePath, "<?php\n$comment\n$php");

        return $this->includeComponent($component, $cachePath);
    }

    /**
     * Include a cached component.
     *
     * @param string $component The component key.
     * @param string $cachePath The path to the component.
     * @return callable|null Returns the component function or **null** if the component wasn't properly defined.
     */
    private function includeComponent($component, $cachePath) {
        unset($this->components[$component]);
        $fn = $this->requireFile($cachePath);

        if (isset($this->components[$component])) {
            return $this->components[$component];
        } elseif (is_callable($fn)) {
            $this->defineComponent($component, $fn);
            return $fn;
        } else {
            $this->components[$component] = null;
            return null;
        }
    }

    /**
     * A safe version of {@link file_put_contents()} that also clears op caches.
     *
     * @param string $path The path to save to.
     * @param string $contents The contents of the file.
     * @return bool Returns **true** on success or **false** on failure.
     */
    private function filePutContents($path, $contents) {
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        $tmpPath = tempnam(dirname($path), 'ebi-');
        $r = false;
        if (file_put_contents($tmpPath, $contents) !== false) {
            chmod($tmpPath, 0664);
            $r = rename($tmpPath, $path);
        }

        if (function_exists('apc_delete_file')) {
            // This fixes a bug with some configurations of apc.
            @apc_delete_file($path);
        } elseif (function_exists('opcache_invalidate')) {
            @opcache_invalidate($path);
        }

        return $r;
    }

    /**
     * Include a file.
     *
     * This is method is useful for including a file bound to this object instance.
     *
     * @param string $path The path to the file to include.
     * @return mixed Returns the result of the include.
     */
    public function requireFile($path) {
        return require $path;
    }

    /**
     * Register a component.
     *
     * @param string $name The name of the component to register.
     * @param callable $component The component function.
     */
    public function defineComponent($name, callable $component) {
        $this->components[$name] = $component;
    }

    /**
     * Render a component to a string.
     *
     * @param string $component The name of the component to render.
     * @param array ...$args Arguments to pass to the component.
     * @return string|null Returns the rendered component or **null** if the component was not found.
     */
    public function render($component, ...$args) {
        if ($callback = $this->lookup($component)) {
            ob_start();
            $errs = error_reporting(error_reporting() & ~E_NOTICE & ~E_WARNING);
            call_user_func($callback, ...$args);
            error_reporting($errs);
            $str = ob_get_clean();
            return $str;
        } else {
            trigger_error("Could not find component $component.", E_USER_NOTICE);
            return null;
        }
    }

    /**
     * Call a function registered with **defineFunction()**.
     *
     * If a static or global function is registered then it's simply rendered in the compiled template.
     * This method is for closures or callbacks.
     *
     * @param string $name The name of the registered function.
     * @param array ...$args The function's argument.
     * @return mixed Returns the result of the function
     * @throws RuntimeException Throws an exception when the function isn't found.
     */
    public function call($name, ...$args) {
        if (!isset($this->functions[$name])) {
            throw new RuntimeException("Call to undefined function $name.", 500);
        } else {
            return $this->functions[$name](...$args);
        }
    }

    /**
     * Render a variable appropriately for CSS.
     *
     * This is a convenience runtime function.
     *
     * @param string|array $expr A CSS class, an array of CSS classes, or an associative array where the keys are class
     * names and the values are truthy conditions to include the class (or not).
     * @return string Returns a space-delimited CSS class string.
     */
    public function attributeClass($expr) {
        if (is_array($expr)) {
            $classes = [];
            foreach ($expr as $i => $val) {
                if (is_array($val)) {
                    $classes[] = $this->attributeClass($val);
                } elseif (is_int($i)) {
                    $classes[] = $val;
                } elseif (!empty($val)) {
                    $classes[] = $i;
                }
            }
            return implode(' ', $classes);
        } else {
            return (string)$expr;
        }
    }

    /**
     * Format a data.
     *
     * @param mixed $date The date to format. This can be a string data, a timestamp or an instance of **DateTimeInterface**.
     * @param string $format The format of the date.
     * @return string Returns the formatted data.
     * @see date_format()
     */
    public function formatDate($date, $format = 'c') {
        if (is_string($date)) {
            try {
                $date = new \DateTimeImmutable($date);
            } catch (\Exception $ex) {
                return '#error#';
            }
        } elseif (empty($date)) {
            return '';
        } elseif (is_int($date)) {
            try {
                $date = new \DateTimeImmutable('@'.$date);
            } catch (\Exception $ex) {
                return '#error#';
            }
        } elseif (!$date instanceof \DateTimeInterface) {
            return '#error#';
        }

        return $date->format($format);
    }

    /**
     * Get a single item from the meta array.
     *
     * @param string $name The key to get from.
     * @param mixed $default The default value if no item at the key exists.
     * @return mixed Returns the meta value.
     */
    public function getMeta($name, $default = null) {
        return isset($this->meta[$name]) ? $this->meta[$name] : $default;
    }

    /**
     * Set a single item to the meta array.
     *
     * @param string $name The key to set.
     * @param mixed $value The new value.
     * @return $this
     */
    public function setMeta($name, $value) {
        $this->meta[$name] = $value;
        return $this;
    }

    /**
     * Get the template loader.
     *
     * The template loader translates component names into template contents.
     *
     * @return TemplateLoaderInterface Returns the template loader.
     */
    public function getTemplateLoader() {
        return $this->templateLoader;
    }

    /**
     * Set the template loader.
     *
     * The template loader translates component names into template contents.
     *
     * @param TemplateLoaderInterface $templateLoader The new template loader.
     * @return $this
     */
    public function setTemplateLoader($templateLoader) {
        $this->templateLoader = $templateLoader;
        return $this;
    }

    /**
     * Get the entire meta array.
     *
     * @return array Returns the meta.
     */
    public function getMetaArray() {
        return $this->meta;
    }

    /**
     * Set the entire meta array.
     *
     * @param array $meta The new meta array.
     * @return $this
     */
    public function setMetaArray(array $meta) {
        $this->meta = $meta;
        return $this;
    }
}
