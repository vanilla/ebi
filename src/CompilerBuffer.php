<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi;


class CompilerBuffer {
    const STYLE_FUNCTION = 'return';
    const STYLE_REGISTER = 'register';

    /**
     * @var ComponentBuffer[]
     */
    private $buffers;

    /**
     * @var ComponentBuffer
     */
    private $current;

    private $currentName;

    private $basename;

    private $style = self::STYLE_REGISTER;

    /**
     * @var \SplObjectStorage
     */
    private $nodeProps;

    public function __construct() {
        $this->buffers = ['' => new ComponentBuffer()];
        $this->nodeProps = new \SplObjectStorage();
        $this->select('');
    }

    /**
     * Select a specific component buffer.
     * @param $component
     */
    public function select($component) {
        $previous = $this->currentName;
        $this->currentName = $component;

        if (!array_key_exists($component, $this->buffers)) {
            $this->buffers[$component] = new ComponentBuffer();
        }

        $this->current =& $this->buffers[$component];

        return $previous;
    }

    public function echoLiteral($value) {
        $this->current->echoLiteral($value);
    }

    public function echoCode($php) {
        $this->current->echoCode($php);
    }

    public function appendCode($php) {
        $this->current->appendCode($php);
    }

    public function indent($add) {
        $this->current->indent($add);
    }

    public function depth($add = 1) {
        $this->current->depth($add);
    }

    public function depthName($name, $add = 0) {
        return $this->current->depthName($name, $add);
    }

    public function pushScope(array $vars) {
        $this->current->pushScope($vars);
    }

    public function popScope() {
        $this->current->popScope();
    }

    public function getScopeVariables() {
        return $this->current->getScopeVariables();
    }

    public function flush() {
        $result = [];

        foreach ($this->buffers as $name => $buffer) {
            if ($this->getStyle() === self::STYLE_FUNCTION && empty($name)) {
                // Render the component return last.
                continue;
            }

            if (empty($name)) {
                $component = $this->basename;
            } elseif ($name[0] === '.') {
                $component = trim($this->basename.$name, '.');
            } else {
                $component = $name;
            }

            $result[] = '$this->register('.var_export($component, true).', '.$buffer->flush().');';
        }

        if ($this->getStyle() === self::STYLE_FUNCTION) {
            $result[] = $this->buffers['']->flush().';';
        }

        return implode("\n\n", $result);
    }

    /**
     * Get the style.
     *
     * @return string Returns the style.
     */
    public function getStyle() {
        return $this->style;
    }

    /**
     * Set the style.
     *
     * @param string $style One of the **STYLE_*** constants.
     * @return $this
     */
    public function setStyle($style) {
        $this->style = $style;
        return $this;
    }

    /**
     * Get the basename.
     *
     * @return string Returns the basename.
     */
    public function getBasename() {
        return $this->basename;
    }

    /**
     * Set the basename.
     *
     * @param string $basename
     * @return $this
     */
    public function setBasename($basename) {
        $this->basename = $basename;
        return $this;
    }

    public function getNodeProp(\DOMNode $node, $name, $default = null) {
        if (!$this->nodeProps->contains($node) || !array_key_exists($name, $this->nodeProps[$node])) {
            return $default;
        }
        return $this->nodeProps[$node][$name];
    }

    public function setNodeProp(\DOMNode $node = null, $name, $value) {
        if ($node === null) {
            return;
        }

        if (!$this->nodeProps->contains($node)) {
            $this->nodeProps->attach($node, [$name => $value]);
        }

        $this->nodeProps[$node] = [$name => $value] + $this->nodeProps[$node];
        return $this;
    }
}
