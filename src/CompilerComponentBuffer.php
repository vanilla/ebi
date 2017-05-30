<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi;


class CompilerComponentBuffer {
    /**
     * @var CompilerBuffer[]
     */
    private $buffers;

    /**
     * @var CompilerBuffer
     */
    private $current;

    private $basename;

    public function __construct($basename = '') {
        $this->basename = $basename;
        $this->buffers = ['' => new CompilerBuffer()];
        $this->current =& $this->buffers[''];
    }

    public function select($component) {
        if (!array_key_exists($component, $this->buffers)) {
            $this->buffers[$component] = new CompilerBuffer();
        }

        $this->current =& $this->buffers[$component];
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
        return $this->current->flush();
    }
}
