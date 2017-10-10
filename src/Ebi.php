<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi;


class Ebi {
    /**
     * @var TemplateLoaderInterface
     */
    private $templateLoader;

    /**
     * @var string
     */
    private $cachePath;

    /**
     * @var Compiler
     */
    private $compiler;

    /**
     * @var callable[]
     */
    private $components = [];

    /**
     * @var callable[]
     */
    protected $functions;

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
     * Lookup a component with a given name.
     *
     * @param string $component The component to lookup.
     * @return callable|null Returns the component function or **null** if the component is not found.
     */
    public function lookup($component) {
        $component = strtolower($component);

        if (!array_key_exists($component, $this->components)) {
            $this->loadComponent($component);
        }

        if (isset($this->components[$component])) {
            return $this->components[$component];
        } else {
            return null;
        }
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
     * Load a component.
     *
     * @param string $component The name of the component to load.
     * @return callable|null Returns the component.
     */
    protected function loadComponent($component) {
        $cacheKey = $this->templateLoader->cacheKey($component);
        $cachePath = "{$this->cachePath}/$cacheKey.php";

        if (!file_exists($cachePath)) {
            $src = $this->templateLoader->load($component);

            $php = $this->compiler->compile($src, ['basename' => $component]);
            $comment = "/*\n".str_replace('*/', '❄/', trim($src))."\n*/";

            $this->filePutContents($cachePath, "<?php\n$comment\n$php");
        }

        $fn = $this->requireFile($cachePath);

        if (is_callable($fn) && basename($cacheKey, '.php') === $component) {
            $this->defineComponent($component, $fn);
            return $fn;
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
     * Render a variable appropriately for CSS.
     *
     * @param $expr
     * @return string
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
}
