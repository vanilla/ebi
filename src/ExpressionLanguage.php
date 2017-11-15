<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi;

use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\Node\GetAttrNode;
use Symfony\Component\ExpressionLanguage\Node\NameNode;

class ExpressionLanguage extends \Symfony\Component\ExpressionLanguage\ExpressionLanguage {
    public function __construct() {
        parent::__construct(new NullAdapter());

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
                        $itExpr = $this->iteratorExpression($node);

                        if ($itExpr) {
                            $compiler->raw($itExpr);
                        } else {
                            $compiler
                                ->compile($node->nodes['node'])
                                ->raw('[')
                                ->compile($node->nodes['attribute'])->raw(']');
                        }
                        break;
                }
        });
    }

    /**
     * Look for a specific iterator expression.
     *
     * Iterator expressions are one of the following:
     *
     * - i123.index
     * - i123.first
     * - i123.last
     * - i123.count
     *
     * @param GetAttrNode $node The node to inspect.
     * @return null|string Returns the appropriate variable or **null** if the node isn't an iterator expression.
     */
    private function iteratorExpression(GetAttrNode $node) {
        if (empty($node->nodes['node'])
            || !($node->nodes['node'] instanceof NameNode)
            || !preg_match('`^i(\d+)$`', $node->nodes['node']->attributes['name'], $m)
            || empty($node->nodes['attribute'])
            || !($node->nodes['attribute'] instanceof ConstantNode)
            || !in_array($node->nodes['attribute']->attributes['value'], ['index', 'first', 'last', 'count'], true)
        ) {

            return null;
        }
        $i = $m[1];
        $field = $node->nodes['attribute']->attributes['value'];
        return "\${$field}{$i}";
    }

    public function getFunctionCompiler($name) {
        if (isset($this->functions[$name])) {
            return $this->functions[$name]['compiler'];
        }
        return null;
    }
}
