<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi;


use Symfony\Component\ExpressionLanguage\SyntaxError;

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

    private $source;

    private $path;

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
        $this->nodeProps = isset($defaults['nodeProps']) ? $defaults['nodeProps'] : new \SplObjectStorage();
        $this->style = $style;
        $this->defaults = $defaults;
        $this->select('');
    }

    /**
     * Select a specific component buffer.
     * @param string $component The name of the component to select.
     * @param bool $add Whether to add a new component if there is already a compile buffer with the same name.
     */
    public function select($component, $add = false) {
        $previous = $this->currentName;
        $this->currentName = $component;

        if (!array_key_exists($component, $this->buffers)) {
            $this->buffers[$component] = $buffer = new ComponentBuffer($this->defaults);
        } elseif ($add) {
            if (is_array($this->buffers[$component])) {
                $this->buffers[$component][] = $buffer = new ComponentBuffer($this->defaults);
            } else {
                $this->buffers[$component] = [
                    $this->buffers[$component],
                    $buffer = new ComponentBuffer($this->defaults)
                ];
            }
        } else {
            $buffer = $this->buffers[$component];
            if (is_array($buffer)) {
                $buffer = end($buffer);
                if ($previous === $component) {
                    $buffer = prev($buffer);
                }
            }
        }

        $this->current = $buffer;

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

        foreach ($this->buffers as $name => $buffers) {
            $flushed = [];
            if (is_array($buffers)) {
                foreach ($buffers as $buffer) {
                    $flushed[] = $buffer->flush();
                }
            } else {
                $flushed = [$buffers->flush()];
            }

            $flushed = array_filter($flushed);
            if (empty($flushed)) {
                continue;
            }

            if (count($flushed) === 1) {
                $children = reset($flushed);
            } else {
                $children = "[\n".implode(",\n", $flushed)."\n".$this->px(+1).']';
            }

            if ($name === '') {
                $result[] = $children;
            } else {
                $result[] = var_export($name, true).' => '.ltrim($children);
            }
        }

        if (empty($result)) {
            return '[]';
        } else {
            return "[\n".implode(",\n\n".$this->px(+1), $result)."\n".$this->px().']';
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

    /**
     * Create a new **CompileException** with proper context.
     *
     * @param \DOMNode $node The node that has the error.
     * @param \Exception $ex The exception that represents the low-level error.
     * @param array $context Custom context information for the exception.
     * @return CompileException Returns a new exception that can be thrown.
     */
    public function createCompilerException(\DOMNode $node, \Exception $ex, array $context = []) {
        $result = $context + [
            'path' => $this->getPath(),
            'source' => '',
            'sourcePosition' => null,
            'line' => $node->getLineNo(),
            'lines' => []
        ];
        $message = $ex->getMessage();

        if ($ex instanceof SyntaxError) {
            list($error, $position) = $this->splitSyntaxError($ex);
            $result['source'] = $result['source'] ?: ($node instanceof \DOMAttr ? $node->value : $node->nodeValue);
            if (!isset($context['sourcePosition'])) {
                $result['sourcePosition'] = $position;
            }
        } elseif (empty($result['source'])) {
            if ($node instanceof \DOMAttr) {
                $result['source'] = $node->name.'="'.$node->value.'"';
            }
        }

        if (!empty($this->source)) {
            $allLines = explode("\n", $this->source);

            $lines = [];
            $line = $result['line'];
            for ($i = max(0, $line - 4); $i < $line + 3; $i++) {
                if (isset($allLines[$i])) {
                    $lines[$i + 1] = $allLines[$i];
                }
            }

            $result['lines'] = $lines;
        }

        return new CompileException($message, $result, $ex);
    }

    private function splitSyntaxError(SyntaxError $ex) {
        if (preg_match('`^(.*) around position (.*)\.$`', $ex->getMessage(), $m)) {
            return [$m[1], $m[2]];
        } else {
            return [$ex->getMessage(), 0];
        }
    }

    /**
     * Get the source.
     *
     * @return mixed Returns the source.
     */
    public function getSource() {
        return $this->source;
    }

    /**
     * Set the source.
     *
     * @param mixed $source
     * @return $this
     */
    public function setSource($source) {
        $this->source = $source;
        return $this;
    }

    /**
     * Get the path.
     *
     * @return mixed Returns the path.
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * Set the path.
     *
     * @param mixed $path
     * @return $this
     */
    public function setPath($path) {
        $this->path = $path;
        return $this;
    }

    /**
     * Get the entire node property array.
     *
     * @return \SplObjectStorage Returns the node properties.
     */
    public function getNodePropArray() {
        return $this->nodeProps;
    }
}
