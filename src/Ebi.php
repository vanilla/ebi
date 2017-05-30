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
    private $components;

    /**
     * @var ComponentLoaderInterface
     */
    private $componentLoader;


    /**
     * Render a component.
     *
     * @param string $name The name of the component.
     * @param array ...$args
     */
    public function render($name, ...$args) {
        if ($component = $this->get($name)) {
            call_user_func($component, ...$args);
        } else {
            trigger_error("Could not find component $name", E_USER_NOTICE);
        }
    }

    public function get($name) {
        if ($this->componentLoader && !array_key_exists($name, $this->components)) {
            $this->componentLoader->load($name, $this);
        }

        if (isset($this->components, $name)) {
            return $this->components[$name];
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
                if (is_int($i)) {
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
}
