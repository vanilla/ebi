<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi;


class ComponentBuffer {
    private $buffer = '';
    private $literalBuffer = '';
    private $inEcho = false;
    private $indent = 0;
    private $depth = 0;
    private $scopes = [];

    public function echoLiteral($value) {
        $this->literalBuffer .= $value;
    }

    public function echoCode($php) {
        if (empty($php)) {
            return;
        }

        $this->flushLiteralBuffer();
        $this->ensureEcho(true);
        $this->buffer .= $php;
    }

    private function flushLiteralBuffer() {
        if (!empty($this->literalBuffer)) {
            $this->ensureEcho(true);
            $this->buffer .= var_export($this->literalBuffer, true);
            $this->literalBuffer = '';
        }
    }

    private function ensureEcho($append) {
        if (!$this->inEcho) {
            $this->buffer .= $this->px().'echo ';
            $this->inEcho = true;
        } elseif ($append) {
            $this->buffer .= ",\n".$this->px(+1);
        }
    }

    protected function px($add = 0) {
        return str_repeat(' ', ($this->indent + $add) * 4);
    }

    public function appendCode($php) {
        $this->flushLiteralBuffer();
        $this->flushEcho();

        $this->buffer .= $this->px().$php;
    }

    private function flushEcho() {
        $this->flushLiteralBuffer();

        if ($this->inEcho) {
            $this->buffer .= ";\n";
            $this->inEcho = false;
        }
    }

    public function indent($add) {
        $this->flushEcho();
        $this->indent += $add;
    }

    public function depth($add = 1) {
        $this->depth += $add;
    }

    public function depthName($name, $add = 0) {
        $depth = $this->depth + $add;

        if ($depth === 0) {
            return $name;
        } else {
            return $name.$depth;
        }
    }

    public function pushScope(array $vars) {
        $this->scopes[] = $vars;
    }

    public function popScope() {
        array_pop($this->scopes);
    }

    public function getScopeVariables() {
        $r = array_replace(...$this->scopes);
        return $r;
    }

    public function flush() {
        $this->flushEcho();

        $result = "function (\$props = [], \$children = []) {\n{$this->buffer}\n}";

        return $result;
    }
}
