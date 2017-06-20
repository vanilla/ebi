<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi;


class CompilerBuffer {
    const STYLE_JOIN = 'join';
    const STYLE_ARRAY = 'array';

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

    private $style = self::STYLE_JOIN;

    /**
     * @var array
     */
    private $defaults;

    /**
     * @var \SplObjectStorage
     */
    private $nodeProps;

    public function __construct($style = self::STYLE_JOIN, array $defaults = []) {
        $defaults += [
            'baseIndent' => 0
        ];

        $this->buffers = [];
        $this->nodeProps = new \SplObjectStorage();
        $this->style = $style;
        $this->defaults = $defaults;
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
            $this->buffers[$component] = $buffer = new ComponentBuffer($this->defaults);
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
        switch ($this->getStyle()) {
            case self::STYLE_ARRAY:
                return $this->flushArray();
            default:
                return $this->flushJoin();
        }
    }

    private function flushJoin() {
        return implode("\n\n", array_map(function ($buffer) {
            /* @var ComponentBuffer $buffer */
            return $buffer->flush();
        }, $this->buffers));
    }

    private function flushArray() {
        $result = [];

        foreach ($this->buffers as $name => $buffer) {
            $flushed = $buffer->flush();
            if (empty($flushed)) {
                continue;
            }

            if ($name === '') {
                $result[] = $flushed;
            } else {
                $result[] = var_export($name, true).' => '.$flushed;
            }
        }

        if (empty($result)) {
            return '[]';
        } else {
            return "[\n".implode(",\n\n", $result)."\n".$this->px().']';
        }
    }

    protected function px($add = 0) {
        return str_repeat(' ', ($this->defaults['baseIndent'] + $add) * 4);
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
            return $this;
        }

        if (!$this->nodeProps->contains($node)) {
            $this->nodeProps->attach($node, [$name => $value]);
        }

        $this->nodeProps[$node] = [$name => $value] + $this->nodeProps[$node];
        return $this;
    }

    public function getIndent() {
        return $this->current->getIndent();
    }

    public function getDepth() {
        return $this->current->getDepth();
    }

    public function getScope() {
        return $this->current->getScope();
    }

    public function getAllScopes() {
        return $this->current->getAllScopes();
    }
}
