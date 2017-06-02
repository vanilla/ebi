<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi;

use DOMElement;
use DOMNode;

class Compiler {
    const T_IF = 'bi-if';
    const T_EACH = 'bi-each';
    const T_WITH = 'bi-with';
    const T_LITERAL = 'bi-literal';
    const T_AS = 'bi-as';
    const T_COMPONENT = 'bi-component';
    const T_CHILDREN = 'bi-children';
    const T_BLOCK = 'bi-block';
    const T_ELSE = 'bi-else';
    const T_EMPTY = 'bi-empty';
    const T_X = 'bi-x';

    protected static $special = [
        self::T_COMPONENT => 1, self::T_IF => 2, self::T_ELSE => 3, self::T_EACH => 4, self::T_AS => 5, self::T_EMPTY => 6, self::T_WITH => 7, self::T_LITERAL => 8
    ];

    protected static $htmlTags = [
        'a' => 'i',
        'abbr' => 'i',
        'acronym' => 'i', // deprecated
        'address' => 'b',
//        'applet' => 'i', // deprecated
        'area' => 'i',
        'article' => 'b',
        'aside' => 'b',
        'audio' => 'i',
        'b' => 'i',
        'base' => 'i',
//        'basefont' => 'i',
        'bdi' => 'i',
        'bdo' => 'i',
//        'bgsound' => 'i',
//        'big' => 'i',
        'bi-x' => 'i',
//        'blink' => 'i',
        'blockquote' => 'b',
        'body' => 'b',
        'br' => 'i',
        'button' => 'i',
        'canvas' => 'b',
        'caption' => 'i',
//        'center' => 'i',
        'cite' => 'i',
        'code' => 'i',
        'col' => 'i',
        'colgroup' => 'i',
//        'command' => 'i',
        'content' => 'i',
        'data' => 'i',
        'datalist' => 'i',
        'dd' => 'b',
        'del' => 'i',
        'details' => 'i',
        'dfn' => 'i',
        'dialog' => 'i',
//        'dir' => 'i',
        'div' => 'i',
        'dl' => 'b',
        'dt' => 'b',
//        'element' => 'i',
        'em' => 'i',
        'embed' => 'i',
        'fieldset' => 'b',
        'figcaption' => 'b',
        'figure' => 'b',
//        'font' => 'i',
        'footer' => 'b',
        'form' => 'b',
        'frame' => 'i',
        'frameset' => 'i',
        'h1' => 'b',
        'h2' => 'b',
        'h3' => 'b',
        'h4' => 'b',
        'h5' => 'b',
        'h6' => 'b',
        'head' => 'b',
        'header' => 'b',
        'hgroup' => 'b',
        'hr' => 'b',
        'html' => 'b',
        'i' => 'i',
        'iframe' => 'i',
        'image' => 'i',
        'img' => 'i',
        'input' => 'i',
        'ins' => 'i',
        'isindex' => 'i',
        'kbd' => 'i',
        'keygen' => 'i',
        'label' => 'i',
        'legend' => 'i',
        'li' => 'i',
        'link' => 'i',
//        'listing' => 'i',
        'main' => 'b',
        'map' => 'i',
        'mark' => 'i',
//        'marquee' => 'i',
        'menu' => 'i',
        'menuitem' => 'i',
        'meta' => 'i',
        'meter' => 'i',
        'multicol' => 'i',
        'nav' => 'b',
        'nobr' => 'i',
        'noembed' => 'i',
        'noframes' => 'i',
        'noscript' => 'b',
        'object' => 'i',
        'ol' => 'b',
        'optgroup' => 'i',
        'option' => 'b',
        'output' => 'i',
        'p' => 'b',
        'param' => 'i',
        'picture' => 'i',
//        'plaintext' => 'i',
        'pre' => 'b',
        'progress' => 'i',
        'q' => 'i',
        'rp' => 'i',
        'rt' => 'i',
        'rtc' => 'i',
        'ruby' => 'i',
        's' => 'i',
        'samp' => 'i',
        'script' => 'i',
        'section' => 'b',
        'select' => 'i',
//        'shadow' => 'i',
        'slot' => 'i',
        'small' => 'i',
        'source' => 'i',
//        'spacer' => 'i',
        'span' => 'i',
//        'strike' => 'i',
        'strong' => 'i',
        'style' => 'i',
        'sub' => 'i',
        'summary' => 'i',
        'sup' => 'i',
        'table' => 'b',
        'tbody' => 'i',
        'td' => 'i',
        'template' => 'i',
        'textarea' => 'i',
        'tfoot' => 'b',
        'th' => 'i',
        'thead' => 'i',
        'time' => 'i',
        'title' => 'i',
        'tr' => 'i',
        'track' => 'i',
//        'tt' => 'i',
        'u' => 'i',
        'ul' => 'b',
        'var' => 'i',
        'video' => 'b',
        'wbr' => 'i'
    ];

    protected static $blocks = [
        'body', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'head', 'html', 'li', 'meta', 'p', 'ol', 'ul'
    ];

    /**
     * @var ExpressionLanguage
     */
    protected $expressions;

    public function __construct() {
        $this->expressions = new ExpressionLanguage();

//        $this->expressions->registerNodeFunction()
    }

    public function compile($src, array $options = []) {
        $options += ['basename' => '', 'runtime' => true];
        $src = trim($src);

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);

        $fragment = false;
        if (strpos($src, '<html') === false) {
            $src = "<ebi>$src</ebi>";
            $fragment = true;
        }

        $dom->loadHTML($src, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOCDATA | LIBXML_NOXMLDECL);
//        $arr = $this->domToArray($dom);

        $out = new CompilerBuffer();

        $out->setBasename($options['basename'])
            ->setStyle($options['runtime'] ? CompilerBuffer::STYLE_REGISTER : CompilerBuffer::STYLE_FUNCTION);

        $out->pushScope(['this' => 'props']);
        $out->indent(+1);

        $parent = $fragment ? $dom->firstChild : $dom;

        foreach ($parent->childNodes as $node) {
            $this->compileNode($node, $out);
        }

        $out->indent(-1);
        $out->popScope();

        $r = $out->flush();
        return $r;
    }

    protected function isComponent($tag) {
        return !isset(static::$htmlTags[$tag]);
    }

    protected function compileNode(DOMNode $node, CompilerBuffer $output) {
        switch ($node->nodeType) {
            case XML_TEXT_NODE:
                $this->compileTextNode($node, $output);
                break;
            case XML_ELEMENT_NODE:
                /* @var \DOMElement $node */
                $this->compileElementNode($node, $output);
                break;
            case XML_COMMENT_NODE:
                /* @var \DOMComment $node */
                $this->compileCommentNode($node, $output);
                break;
            case XML_DOCUMENT_TYPE_NODE:
                $output->echoCode($node->ownerDocument->saveHTML($node));
                break;
            default:
                $r = "// Unknown node\n".
                    '// '.str_replace("\n", "\n// ", $node->ownerDocument->saveHTML($node));
        }
    }

    protected function domToArray(DOMNode $root) {
        $result = array();

        if ($root->hasAttributes()) {
            $attrs = $root->attributes;
            foreach ($attrs as $attr) {
                $result['@attributes'][$attr->name] = $attr->value;
            }
        }

        if ($root->hasChildNodes()) {
            $children = $root->childNodes;
            if ($children->length == 1) {
                $child = $children->item(0);
                if ($child->nodeType == XML_TEXT_NODE) {
                    $result['_value'] = $child->nodeValue;
                    return count($result) == 1
                        ? $result['_value']
                        : $result;
                }
            }
            $groups = array();
            foreach ($children as $child) {
                if (!isset($result[$child->nodeName])) {
                    $result[$child->nodeName] = $this->domToArray($child);
                } else {
                    if (!isset($groups[$child->nodeName])) {
                        $result[$child->nodeName] = array($result[$child->nodeName]);
                        $groups[$child->nodeName] = 1;
                    }
                    $result[$child->nodeName][] = $this->domToArray($child);
                }
            }
        }

        return $result;
    }

    protected function newline(DOMNode $node, $output) {
        if ($node->previousSibling && $node->previousSibling->nodeType !== XML_COMMENT_NODE) {
            $output->appendCode("\n");
        }
    }

    protected function compileCommentNode(\DOMComment $node, CompilerBuffer $output) {
        $comments = explode("\n", trim($node->nodeValue));

        $this->newline($node, $output);
        foreach ($comments as $comment) {
            $output->appendCode("// $comment\n");
        }
    }

    protected function compileTextNode(DOMNode $node, CompilerBuffer $output) {
        $items = $this->splitExpressions($node->nodeValue);

        foreach ($items as $i => list($text, $offset)) {
            if (preg_match('`^{\S`', $text)) {
                $output->echoCode('htmlspecialchars('.$this->expr(substr($text, 1, -1), $output).')');
            } else {
                if ($i === 0) {
                    $text = $this->ltrim($text, $node);
                }
                if ($i === count($items) - 1) {
                    $text = $this->rtrim($text, $node);
                }

                $output->echoLiteral($text);
            }
        }
    }

    protected function compileElementNode(DOMElement $node, CompilerBuffer $output) {
        list($attributes, $special) = $this->splitAttributes($node);

        if (!empty($special) || $this->isComponent($node->tagName)) {
            $this->compileSpecialNode($node, $attributes, $special, $output);
        } else {
            $this->compileOpenTag($node, $node->attributes, $output);

            foreach ($node->childNodes as $childNode) {
                $this->compileNode($childNode, $output);
            }

            $this->compileCloseTag($node, $output);
        }
    }

    protected function splitExpressions($value) {
        $values = preg_split('`({\S[^}]*?})`', $value, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE);
        return $values;
    }

    protected function expr($expr, CompilerBuffer $output, \DOMAttr $attr = null) {
        $names = $output->getScopeVariables();

        $compiled = $this->expressions->compile($expr, function ($name) use ($names) {
            if (isset($names[$name])) {
                return $names[$name];
            } else {
                return $names['this'].'['.var_export($name, true).']';
            }
        });

        if ($attr !== null && null !== $fn = $this->expressions->getFunctionCompiler('@'.$attr->name)) {
            $compiled = call_user_func($fn, $compiled);
        }

        return $compiled;
    }

    /**
     * @param DOMElement $node
     */
    protected function splitAttributes(DOMElement $node) {
        $attributes = [];
        $special = [];

        foreach ($node->attributes as $name => $attribute) {
            if (isset(static::$special[$name])) {
                $special[$name] = $attribute;
            } else {
                $attributes[$name] = $attribute;
            }
        }

        uksort($special, function ($a, $b) {
            return strnatcmp(static::$special[$a], static::$special[$b]);
        });

        return [$attributes, $special];
    }

    protected function compileSpecialNode(DOMElement $node, array $attributes, array $special, CompilerBuffer $output) {
        $specialName = key($special);

        switch ($specialName) {
            case self::T_COMPONENT:
                $this->compileComponentRegister($node, $attributes, $special, $output);
                break;
            case self::T_IF:
                $this->compileIf($node, $attributes, $special, $output);
                break;
            case self::T_EACH:
                $this->compileEach($node, $attributes, $special, $output);
                break;
            case self::T_WITH:
                if ($this->isComponent($node->tagName)) {
                    // With has a special meaning in components.
                    $this->compileComponentInclude($node, $attributes, $special, $output);
                } else {
                    $this->compileWith($node, $attributes, $special, $output);
                }
                break;
            case self::T_LITERAL:
                $this->compileLiteral($node, $attributes, $special, $output);
                break;
            case '':
                if ($this->isComponent($node->tagName)) {
                    $this->compileComponentInclude($node, $attributes, $special, $output);
                } else {
                    $this->compileElement($node, $attributes, $output);
                }
                break;
        }
    }

    /**
     * Compile component registering.
     *
     * @param DOMElement $node
     * @param $attributes
     * @param $special
     * @param CompilerBuffer $out
     */
    public function compileComponentRegister(DOMElement $node, $attributes, $special, CompilerBuffer $out) {
        $name = strtolower($special[self::T_COMPONENT]->value);
        unset($special[self::T_COMPONENT]);

        $prev = $out->select($name);
        $out->pushScope(['this' => 'props']);
        $out->indent(+1);

        try {
            $this->compileSpecialNode($node, $attributes, $special, $out);
        } finally {
            $out->popScope();
            $out->indent(-1);
            $out->select($prev);
        }
    }

    /**
     * Compile component inclusion and rendering.
     *
     * @param DOMElement $node
     * @param $attributes
     * @param $special
     * @param CompilerBuffer $out
     */
    protected function compileComponentInclude(DOMElement $node, $attributes, $special, CompilerBuffer $out) {
        // Generate the attributes into a property array.
        $props = [];
        foreach ($attributes as $name => $attribute) {
            /* @var \DOMAttr $attr */
            if ($this->isExpression($attribute->value)) {
                $expr = $this->expr(substr($attribute->value, 1, -1), $out, $attribute);
            } else {
                $expr = var_export($attribute->value, true);
            }

            $props[] = var_export($name, true).' => '.$expr;
        }
        $propsStr = '['.implode(', ', $props).']';

        $out->appendCode('$this->write('.var_export($node->tagName, true).", $propsStr);\n");
    }

    protected function compileTagComment(DOMElement $node, $attributes, $special, CompilerBuffer $output) {
        // Don't double up comments.
        if ($node->previousSibling && $node->previousSibling->nodeType === XML_COMMENT_NODE) {
            return;
        }

        $str = '<'.$node->tagName;
        foreach ($special as $attr) {
            /* @var \DOMAttr $attr */
            $str .= ' '.$attr->name.(empty($attr->value) ? '' : '="'.htmlspecialchars($attr->value).'"');
        }
        $str .= '>';
        $comments = explode("\n", $str);
        foreach ($comments as $comment) {
            $output->appendCode("// $comment\n");
        }
    }

    protected function compileOpenTag(DOMElement $node, $attributes, CompilerBuffer $output) {
        if ($node->tagName === self::T_X) {
            return;
        }

        $output->echoLiteral('<'.$node->tagName);

        foreach ($attributes as $name => $attribute) {
            /* @var \DOMAttr $attribute */
            $output->echoLiteral(' '.$name.'="');

            // Check for an attribute expression.
            if ($this->isExpression($attribute->value)) {
                $output->echoCode('htmlspecialchars('.$this->expr(substr($attribute->value, 1, -1), $output, $attribute).')');
            } else {
                $output->echoLiteral(htmlspecialchars($attribute->value));
            }

            $output->echoLiteral('"');
        }

        if ($node->hasChildNodes()) {
            $output->echoLiteral('>');
        } else {
            $output->echoLiteral(" />");
        }
    }

    private function isExpression($value) {
        return preg_match('`^{\S.*}$`', $value);
    }

    protected function compileCloseTag(DOMElement $node, CompilerBuffer $output) {
        if ($node->hasChildNodes() && $node->tagName !== self::T_X) {
            $output->echoLiteral("</{$node->tagName}>");
        }
    }

    protected function compileIf(DOMElement $node, array $attributes, array $special, CompilerBuffer $output) {
        $this->compileTagComment($node, $attributes, $special, $output);
        $expr = $this->expr($special[self::T_IF]->value, $output);
        unset($special[self::T_IF]);

        $output->appendCode('if ('.$expr.") {\n");
        $output->indent(+1);

        $this->compileSpecialNode($node, $attributes, $special, $output);

        $output->indent(-1);

        if (null !== $elseNode = $this->findElseNode($node)) {
            $elseNode->compiled = true;
            list($attributes, $special) = $this->splitAttributes($elseNode);
            unset($special[self::T_ELSE]);

            $output->appendCode("} else {\n");

            $output->indent(+1);
            $this->compileSpecialNode($elseNode, $attributes, $special, $output);
            $output->indent(-1);
        }

        $output->appendCode("}\n");
    }

    protected function compileEach(DOMElement $node, array $attributes, array $special, CompilerBuffer $output) {
        $this->compileTagComment($node, $attributes, $special, $output);
        $this->compileOpenTag($node, $attributes, $output);

        if (null === $emptyNode = $this->findEmptyNode($node)) {
            $this->compileEachLoop($node, $attributes, $special, $output);
        } else {
            $expr = $this->expr("empty({$special[self::T_EACH]->value})", $output);

            list ($emptyAttributes, $emptySpecial) = $this->splitAttributes($emptyNode);
            unset($emptySpecial[self::T_EMPTY]);

            $output->appendCode('if ('.$expr.") {\n");

            $output->indent(+1);
            $this->compileSpecialNode($emptyNode, $emptyAttributes, $emptySpecial, $output);
            $output->indent(-1);

            $output->appendCode("} else {\n");

            $output->indent(+1);
            $this->compileEachLoop($node, $attributes, $special, $output);
            $output->indent(-1);

            $output->appendCode("}\n");
        }

        $this->compileCloseTag($node, $output);
    }

    protected function compileWith(DOMElement $node, array $attributes, array $special, CompilerBuffer $output) {
        $this->compileTagComment($node, $attributes, $special, $output);
        $with = $this->expr($special[self::T_WITH]->value, $output);
        unset($special[self::T_WITH]);

        $output->depth(+1);
        $output->pushScope(['this' => $output->depthName('props')]);
        $output->appendCode('$'.$output->depthName('props')." = $with;\n");

        $this->compileSpecialNode($node, $attributes, $special, $output);

        $output->depth(-1);
        $output->popScope();
    }

    protected function compileLiteral(DOMElement $node, array $attributes, array $special, CompilerBuffer $output) {
        $this->compileTagComment($node, $attributes, $special, $output);
        unset($special[self::T_LITERAL]);

        $this->compileOpenTag($node, $attributes, $output);

        foreach ($node->childNodes as $childNode) {
            $html = $childNode->ownerDocument->saveHTML($childNode);
            $output->echoLiteral($html);
        }

        $this->compileCloseTag($node, $output);
    }

    protected function compileElement(DOMElement $node, array $attributes, CompilerBuffer $output) {
        $this->compileOpenTag($node, $attributes, $output);

        foreach ($node->childNodes as $childNode) {
            $this->compileNode($childNode, $output);
        }

        $this->compileCloseTag($node, $output);
    }

    protected function findElseNode(DOMElement $ifNode) {
        for ($node = $ifNode->nextSibling; $node !== null; $node = $node->nextSibling) {
            switch ($node->nodeType) {
                case XML_TEXT_NODE:
                    /* @var \DOMText $node */
                    if (empty(trim($node->data))) {
                        continue;
                    } else {
                        return null;
                    }
                    break;
                case XML_ELEMENT_NODE:
                    if ($node->hasAttribute(self::T_ELSE)) {
                        return $node;
                    } else {
                        return null;
                    }
                default:
                    return null;
            }
        }
        return null;
    }

    protected function findEmptyNode(DOMElement $eachNode) {
        foreach ($eachNode->childNodes as $node) {
            if ($node instanceof DOMElement && $node->hasAttribute(self::T_EMPTY)) {
                return $node;
            }
        }
        return null;
    }

    /**
     * @param DOMElement $node
     * @param array $attributes
     * @param array $special
     * @param CompilerBuffer $output
     */
    private function compileEachLoop(DOMElement $node, array $attributes, array $special, CompilerBuffer $output) {
        $each = $this->expr($special[self::T_EACH]->value, $output);
        unset($special[self::T_EACH]);

        $as = [$output->depthName('i', 1), $output->depthName('props', 1)];
        $scope = ['this' => $as[1]];
        if (!empty($special[self::T_AS])) {
            if (preg_match('`(?:([a-z0-9]+)\s+)?([a-z0-9]+)`', $special[self::T_AS]->value, $m)) {
                $scope = [$m[2] => $as[1]];
                if (!empty($m[1])) {
                    $scope[$m[1]] = $as[0];
                }
            }
        }
        unset($special[self::T_AS]);
        $output->appendCode("foreach ($each as \${$as[0]} => \${$as[1]}) {\n");
        $output->depth(+1);
        $output->indent(+1);
        $output->pushScope($scope);

        foreach ($node->childNodes as $childNode) {
            $this->compileNode($childNode, $output);
        }

        $output->indent(-1);
        $output->depth(-1);
        $output->popScope();
        $output->appendCode("}\n");
    }

    protected function ltrim($text, \DOMNode $node) {
        if ($this->inPre($node)) {
            return $text;
        }

        $sib = $node->previousSibling ?: $node->parentNode;

        if ($sib !== null && ($sib->nodeType === XML_COMMENT_NODE || in_array($sib->tagName, static::$blocks))) {
            return ltrim($text);
        }
        return $text;
    }

    protected function rtrim($text, \DOMNode $node) {
        if ($this->inPre($node)) {
            return $text;
        }

        $sib = $node->nextSibling ?: $node->parentNode;

        if ($sib !== null && ($sib->nodeType === XML_COMMENT_NODE || in_array($sib->tagName, static::$blocks))) {
            return rtrim($text);
        }
        return $text;
    }

    protected function inPre(\DOMNode $node) {
        for ($node = $node->parentNode; $node !== null; $node = $node->parentNode) {
            if (in_array($node->nodeType, ['code', 'pre'], true)) {
                return true;
            }
        }
        return false;
    }
}
