<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi\Expressions;

use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\Node\FunctionNode;
use Symfony\Component\ExpressionLanguage\Node\NameNode;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\ExpressionLanguage\Token;

class Parser extends \Symfony\Component\ExpressionLanguage\Parser {
    public function parsePrimaryExpression()
    {
        $token = $this->stream->current;
        switch ($token->type) {
            case Token::NAME_TYPE:
                $this->stream->next();
                switch ($token->value) {
                    case 'true':
                    case 'TRUE':
                        return new ConstantNode(true);

                    case 'false':
                    case 'FALSE':
                        return new ConstantNode(false);

                    case 'null':
                    case 'NULL':
                        return new ConstantNode(null);

                    default:
                        if ('(' === $this->stream->current->value) {
                            if (false === isset($this->functions[$token->value])) {
                                throw new SyntaxError(sprintf('The function "%s" does not exist', $token->value), $token->cursor);
                            }

                            $node = new FunctionNode($token->value, $this->parseArguments());
                        } else {
                            if (!in_array($token->value, $this->names, true)) {
                                if (!is_int($name = array_search('this', $this->names))) {
                                    $name .= '['.var_export($token->value, true).']';
                                } else {
                                    throw new SyntaxError(sprintf('Variable "%s" is not valid', $token->value), $token->cursor);
                                }
                            }

                            // is the name used in the compiled code different
                            // from the name used in the expression?
                            elseif (is_int($name = array_search($token->value, $this->names))) {
                                $name = $token->value;
                            }

                            $node = new NameNode($name);
                        }
                }
                break;

            case Token::NUMBER_TYPE:
            case Token::STRING_TYPE:
                $this->stream->next();

                return new ConstantNode($token->value);

            default:
                if ($token->test(Token::PUNCTUATION_TYPE, '[')) {
                    $node = $this->parseArrayExpression();
                } elseif ($token->test(Token::PUNCTUATION_TYPE, '{')) {
                    $node = $this->parseHashExpression();
                } else {
                    throw new SyntaxError(sprintf('Unexpected token "%s" of value "%s"', $token->type, $token->value), $token->cursor);
                }
        }

        return $this->parsePostfixExpression($node);
    }
}
