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

    public function __construct(TemplateLoaderInterface $templateLoader, $cachePath) {
        $this->componentLoader = new CompilingLoader($templateLoader, $cachePath);
    }

    /**
     * Write a component to the output buffer.
     *
     * @param string $name The name of the component.
     * @param array ...$args
     */
    public function write($name, ...$args) {
        $name = strtolower($name);
        if ($component = $this->lookup($name)) {
            call_user_func($component, ...$args);
        } else {
            trigger_error("Could not find component $name.", E_USER_NOTICE);
        }
    }

    public function render($component, ...$args) {
        if ($component = $this->lookup($component)) {
            ob_start();
            $errs = error_reporting(error_reporting() & ~E_NOTICE & ~E_WARNING);
            call_user_func($component, ...$args);
            error_reporting($errs);
            $str = ob_get_clean();
            return $str;
        } else {
            trigger_error("Could not find component $component.", E_USER_NOTICE);
            return null;
        }
    }

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

    public function register($name, callable $component) {
        $this->components[$name] = $component;
    }

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
