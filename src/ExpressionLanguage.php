<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\Node\GetAttrNode;

class ExpressionLanguage extends \Symfony\Component\ExpressionLanguage\ExpressionLanguage {
    public function __construct() {
        parent::__construct();
        $this->registerFunctions();

        $this->registerNodeFunction(GetAttrNode::class, function (\Symfony\Component\ExpressionLanguage\Compiler $compiler, GetAttrNode $node) {
                switch ($node->attributes['type']) {
                    case GetAttrNode::METHOD_CALL:
                        $compiler
                            ->compile($node->nodes['node'])
                            ->raw('->')
                            ->raw($node->nodes['attribute']->attributes['value'])
                            ->raw('(')
                            ->compile($node->nodes['arguments'])
                            ->raw(')')
                        ;
                        break;

                    case GetAttrNode::PROPERTY_CALL:
                    case GetAttrNode::ARRAY_CALL:
                        $compiler
                            ->compile($node->nodes['node'])
                            ->raw('[')
                            ->compile($node->nodes['attribute'])->raw(']')
                        ;
                        break;
                }
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
        if (!$compiler) {
            $compiler = function ($expr) use ($name) {
                return "$name($expr)";
            };
        }

        $this->register($name, $compiler, function ($value) {
            return $value;
        });
    }

    public function getFunctionCompiler($name) {
        if (isset($this->functions[$name])) {
            return $this->functions[$name]['compiler'];
        }
        return null;
    }
}
