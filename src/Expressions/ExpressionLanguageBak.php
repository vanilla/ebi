<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi\Expressions;

use Symfony\Component\ExpressionLanguage\Compiler;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\Lexer;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

class ExpressionLanguageBak {
    protected $functions = array();
    private $lexer;
    private $parser;
    private $compiler;

    public function __construct() {
        $this->registerFunctions();
    }

    /**
     * Compiles an expression source code.
     *
     * @param Expression|string $expression The expression to compile
     *
     * @return string The compiled PHP source code
     */
    public function compile($expression, $names = []) {
        return $this->getCompiler()->compile($this->parse($expression, $names)->getNodes())->getSource();
    }

    private function getCompiler() {
        if (null === $this->compiler) {
            $this->compiler = new Compiler($this->functions);
        }

        return $this->compiler->reset();
    }

    /**
     * Parses an expression.
     *
     * @param Expression|string $expression The expression to parse
     * @param array             $names      An array of valid names
     *
     * @return ParsedExpression A ParsedExpression instance
     */
    public function parse($expression, $names = []) {
        if ($expression instanceof ParsedExpression) {
            return $expression;
        }

        $nodes = $this->getParser()->parse($this->getLexer()->tokenize((string)$expression), $names);
        $parsedExpression = new ParsedExpression((string)$expression, $nodes);


        return $parsedExpression;
    }

    private function getParser() {
        if (null === $this->parser) {
            $this->parser = new Parser($this->functions);
        }

        return $this->parser;
    }

    private function getLexer() {
        if (null === $this->lexer) {
            $this->lexer = new Lexer();
        }

        return $this->lexer;
    }

    protected function registerFunctions() {
        $this->registerFunction('count');
        $this->registerFunction('empty');
        $this->registerFunction('implode');
        $this->registerFunction('lcfirst');
        $this->registerFunction('strtolower');
        $this->registerFunction('strtoupper');
        $this->registerFunction('ucfirst');
        $this->registerFunction('ucwords');
        $this->registerFunction('ltrim');
        $this->registerFunction('rtrim');
        $this->registerFunction('trim');
        $this->registerFunction('sprintf');
        $this->registerFunction('substr');

        $this->registerFunction('@class', function ($expr) {
            return "\$this->cssClass($expr)";
        });
    }

    /**
     * Registers a function.
     *
     * @param string $name The function name
     * @param callable $compiler A callable able to compile the function
     *
     * @throws \LogicException when registering a function after calling evaluate(), compile() or parse()
     *
     * @see ExpressionFunction
     */
    public function registerFunction($name, callable $compiler = null) {
        if (null !== $this->parser) {
            throw new \LogicException('Registering functions after calling evaluate(), compile() or parse() is not supported.');
        }

        if ($compiler === null) {
            $compiler = $this->makeFunctionCompiler($name);
        }

        $this->functions[$name] = array('compiler' => $compiler,);
    }

    public function getFunctionCompiler($name) {
        if (isset($this->functions[$name])) {
            return $this->functions[$name]['compiler'];
        }
        return null;
    }

    public function makeFunctionCompiler($name) {
        return function($expr) use ($name) {
            return sprintf('%s(%s)', $name, $expr);
        };
    }
}
