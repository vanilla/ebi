<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi;


class Ebi {
    /**
     * @var callable[]
     */
    private $components = [];

    /**
     * @var ComponentLoaderInterface
     */
    private $componentLoader;

    /**
     * Ebi constructor.
     *
     * @param TemplateLoaderInterface $templateLoader Used to load template sources from component names.
     * @param string $cachePath The path to cache compiled templates.
     */
    public function __construct(TemplateLoaderInterface $templateLoader, $cachePath) {
        $this->componentLoader = new CompilingLoader($templateLoader, $cachePath);
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

        if ($this->componentLoader && !array_key_exists($component, $this->components)) {
            $this->componentLoader->load($component, $this);
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
    public function register($name, callable $component) {
        $this->components[$name] = $component;
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
    public function cssClass($expr) {
        if (is_array($expr)) {
            $classes = [];
            foreach ($expr as $i => $val) {
                if (is_array($val)) {
                    $classes[] = $this->cssClass($val);
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
    public function dateFormat($date, $format = 'c') {
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
}
