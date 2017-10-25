<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi;

use DOMAttr;
use DOMElement;
use DOMNode;

class Compiler {
    const T_IF = 'x-if';
    const T_EACH = 'x-each';
    const T_WITH = 'x-with';
    const T_LITERAL = 'x-literal';
    const T_AS = 'x-as';
    const T_COMPONENT = 'x-component';
    const T_CHILDREN = 'x-children';
    const T_BLOCK = 'x-block';
    const T_ELSE = 'x-else';
    const T_EMPTY = 'x-empty';
    const T_X = 'x';
    const T_INCLUDE = 'x-include';
    const T_EXPR = 'x-expr';
    const T_UNESCAPE = 'x-unescape';

    const IDENT_REGEX = '`^([a-z0-9-]+)$`i';

    protected static $special = [
        self::T_COMPONENT => 1,
        self::T_IF => 2,
        self::T_ELSE => 3,
        self::T_EACH => 4,
        self::T_EMPTY => 5,
        self::T_CHILDREN => 6,
        self::T_INCLUDE => 7,
        self::T_WITH => 8,
        self::T_BLOCK => 9,
        self::T_LITERAL => 10,
        self::T_AS => 11,
        self::T_UNESCAPE => 12
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
        'x' => 'i',
//        'blink' => 'i',
        'blockquote' => 'b',
        'body' => 'b',
        'br' => 'i',
        'button' => 'i',
        'canvas' => 'b',
        'caption' => 'i',
//        'center' => 'b',
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
        'wbr' => 'i',

        /// SVG ///
        'animate' => 's',
        'animateColor' => 's',
        'animateMotion' => 's',
        'animateTransform' => 's',
//        'canvas' => 's',
        'circle' => 's',
        'desc' => 's',
        'defs' => 's',
        'discard' => 's',
        'ellipse' => 's',
        'g' => 's',
//        'image' => 's',
        'line' => 's',
        'marker' => 's',
        'mask' => 's',
        'missing-glyph' => 's',
        'mpath' => 's',
        'metadata' => 's',
        'path' => 's',
        'pattern' => 's',
        'polygon' => 's',
        'polyline' => 's',
        'rect' => 's',
        'set' => 's',
        'svg' => 's',
        'switch' => 's',
        'symbol' => 's',
        'text' => 's',
//        'unknown' => 's',
        'use' => 's',
    ];

    /**
     * @var ExpressionLanguage
     */
    protected $expressions;

    public function __construct() {
        $this->expressions = new ExpressionLanguage();
        $this->expressions->setNamePattern('/[@a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/A');
        $this->expressions->register(
            'hasChildren',
            function ($name = null) {
                return empty($name) ? 'isset($children[0])' : "isset(\$children[$name ?: 0])";
            },
            function ($name = null) {
                return false;
            });
    }

    /**
     * Register a runtime function.
     *
     * @param string $name The name of the function.
     * @param callable $function The function callback.
     */
    public function defineFunction($name, $function = null) {
        if ($function === null) {
            $function = $name;
        }

        $this->expressions->register(
            $name,
            $this->getFunctionCompiler($name, $function),
            $this->getFunctionEvaluator($function)
        );
    }

    private function getFunctionEvaluator($function) {
        if ($function === 'empty') {
            return function ($expr) {
                return empty($expr);
            };
        } elseif ($function === 'isset') {
            return function ($expr) {
                return isset($expr);
            };
        }

        return $function;
    }

    private function getFunctionCompiler($name, $function) {
        $var = var_export(strtolower($name), true);
        $fn = function ($expr) use ($var) {
            return "\$this->call($var, $expr)";
        };

        if (is_string($function)) {
            $fn = function (...$args) use ($function) {
                return $function.'('.implode(', ', $args).')';
            };
        } elseif (is_array($function)) {
            if (is_string($function[0])) {
                $fn = function (...$args) use ($function) {
                    return "$function[0]::$function[1](".implode(', ', $args).')';
                };
            } elseif ($function[0] instanceof Ebi) {
                $fn = function (...$args) use ($function) {
                    return "\$this->$function[1](".implode(', ', $args).')';
                };
            }
        }

        return $fn;
    }

    public function compile($src, array $options = []) {
        $options += ['basename' => '', 'runtime' => true];

        $src = trim($src);

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);

        $fragment = false;
        if (strpos($src, '<html') === false) {
            $src = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><html><body>$src</body></html>";
            $fragment = true;
        }

        $dom->loadHTML($src, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOCDATA | LIBXML_NOXMLDECL);
//        $arr = $this->domToArray($dom);

        $out = new CompilerBuffer();

        $out->setBasename($options['basename']);

        if ($options['runtime']) {
            $name = var_export($options['basename'], true);
            $out->appendCode("\$this->defineComponent($name, function (\$props = [], \$children = []) {\n");
        } else {
            $out->appendCode("function (\$props = [], \$children = []) {\n");
        }

        $out->pushScope(['this' => 'props']);
        $out->indent(+1);

        $parent = $fragment ? $dom->firstChild->nextSibling->firstChild : $dom;

        foreach ($parent->childNodes as $node) {
            $this->compileNode($node, $out);
        }

        $out->indent(-1);
        $out->popScope();

        if ($options['runtime']) {
            $out->appendCode("});");
        } else {
            $out->appendCode("};");
        }

        $r = $out->flush();
        return $r;
    }

    protected function isComponent($tag) {
        return !isset(static::$htmlTags[$tag]);
    }

    protected function compileNode(DOMNode $node, CompilerBuffer $out) {
        if ($out->getNodeProp($node, 'skip')) {
            return;
        }

        switch ($node->nodeType) {
            case XML_TEXT_NODE:
                $this->compileTextNode($node, $out);
                break;
            case XML_ELEMENT_NODE:
                /* @var \DOMElement $node */
                $this->compileElementNode($node, $out);
                break;
            case XML_COMMENT_NODE:
                /* @var \DOMComment $node */
                $this->compileCommentNode($node, $out);
                break;
            case XML_DOCUMENT_TYPE_NODE:
                $out->echoLiteral("<!DOCTYPE {$node->name}>\n");
                break;
            case XML_CDATA_SECTION_NODE:
                $this->compileTextNode($node, $out);
                break;
            default:
                $r = "// Unknown node\n".
                    '// '.str_replace("\n", "\n// ", $node->ownerDocument->saveHTML($node));
        }
    }

    protected function domToArray(DOMNode $root) {
        $result = [];

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
            $groups = [];
            foreach ($children as $child) {
                if (!isset($result[$child->nodeName])) {
                    $result[$child->nodeName] = $this->domToArray($child);
                } else {
                    if (!isset($groups[$child->nodeName])) {
                        $result[$child->nodeName] = [$result[$child->nodeName]];
                        $groups[$child->nodeName] = 1;
                    }
                    $result[$child->nodeName][] = $this->domToArray($child);
                }
            }
        }

        return $result;
    }

    protected function newline(DOMNode $node, CompilerBuffer $out) {
        if ($node->previousSibling && $node->previousSibling->nodeType !== XML_COMMENT_NODE) {
            $out->appendCode("\n");
        }
    }

    protected function compileCommentNode(\DOMComment $node, CompilerBuffer $out) {
        $comments = explode("\n", trim($node->nodeValue));

        $this->newline($node, $out);
        foreach ($comments as $comment) {
            $out->appendCode("// $comment\n");
        }
    }

    protected function compileTextNode(DOMNode $node, CompilerBuffer $out) {
        $text = $this->ltrim($this->rtrim($node->nodeValue, $node, $out), $node, $out);

        $items = $this->splitExpressions($text);

        foreach ($items as $i => list($text, $offset)) {
            if (preg_match('`^{\S`', $text)) {
                if (preg_match('`^{\s*unescape\((.+)\)\s*}$`', $text, $m)) {
                    $out->echoCode($this->expr($m[1], $out));
                } else {
                    $out->echoCode('htmlspecialchars('.$this->expr(substr($text, 1, -1), $out).')');
                }
            } else {
//                if ($i === 0) {
//                    $text = $this->ltrim($text, $node, $out);
//                }
//                if ($i === count($items) - 1) {
//                    $text = $this->rtrim($text, $node, $out);
//                }

                $out->echoLiteral($text);
            }
        }
    }

    protected function compileElementNode(DOMElement $node, CompilerBuffer $out) {
        list($attributes, $special) = $this->splitAttributes($node);

        if ($node->tagName === self::T_EXPR) {
            $this->compileExpressionNode($node, $attributes, $special, $out);
        } elseif (!empty($special) || $this->isComponent($node->tagName)) {
            $this->compileSpecialNode($node, $attributes, $special, $out);
        } else {
            $this->compileOpenTag($node, $node->attributes, $out);

            foreach ($node->childNodes as $childNode) {
                $this->compileNode($childNode, $out);
            }

            $this->compileCloseTag($node, $out);
        }
    }

    protected function splitExpressions($value) {
        $values = preg_split('`({\S[^}]*?})`', $value, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE);
        return $values;
    }

    protected function expr($expr, CompilerBuffer $output, DOMAttr $attr = null) {
        $names = $output->getScopeVariables();

        $compiled = $this->expressions->compile($expr, function ($name) use ($names) {
            if (isset($names[$name])) {
                return $names[$name];
            } elseif ($name[0] === '@') {
                return 'this->meta['.var_export(substr($name, 1), true).']';
            } else {
                return $names['this'].'['.var_export($name, true).']';
            }
        });

        if ($attr !== null && null !== $fn = $this->getAttributeFunction($attr)) {
            $compiled = call_user_func($fn, $compiled);
        }

        return $compiled;
    }

    /**
     * Get the compiler function to wrap an attribute.
     *
     * Attribute functions are regular expression functions, but with a special naming convention. The following naming
     * conventions are supported:
     *
     * - **@tag:attribute**: Applies to an attribute only on a specific tag.
     * - **@attribute**: Applies to all attributes with a given name.
     *
     * @param DOMAttr $attr The attribute to look at.
     * @return callable|null A function or **null** if the attribute doesn't have a function.
     */
    private function getAttributeFunction(DOMAttr $attr) {
        $keys = ['@'.$attr->ownerElement->tagName.':'.$attr->name, '@'.$attr->name];

        foreach ($keys as $key) {
            if (null !== $fn = $this->expressions->getFunctionCompiler($key)) {
                return $fn;
            }
        }
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

    protected function compileSpecialNode(DOMElement $node, array $attributes, array $special, CompilerBuffer $out) {
        $specialName = key($special);

        switch ($specialName) {
            case self::T_COMPONENT:
                $this->compileComponentRegister($node, $attributes, $special, $out);
                break;
            case self::T_IF:
                $this->compileIf($node, $attributes, $special, $out);
                break;
            case self::T_EACH:
                $this->compileEach($node, $attributes, $special, $out);
                break;
            case self::T_BLOCK:
                $this->compileBlock($node, $attributes, $special, $out);
                break;
            case self::T_CHILDREN:
                $this->compileChildBlock($node, $attributes, $special, $out);
                break;
            case self::T_INCLUDE:
                $this->compileComponentInclude($node, $attributes, $special, $out);
                break;
            case self::T_WITH:
                if ($this->isComponent($node->tagName)) {
                    // With has a special meaning in components.
                    $this->compileComponentInclude($node, $attributes, $special, $out);
                } else {
                    $this->compileWith($node, $attributes, $special, $out);
                }
                break;
            case self::T_LITERAL:
                $this->compileLiteral($node, $attributes, $special, $out);
                break;
            case '':
                if ($this->isComponent($node->tagName)) {
                    $this->compileComponentInclude($node, $attributes, $special, $out);
                } else {
                    $this->compileElement($node, $attributes, $out);
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

        $varName = var_export($name, true);
        $out->appendCode("\$this->defineComponent($varName, function (\$props = [], \$children = []) {\n");
        $out->pushScope(['this' => 'props']);
        $out->indent(+1);

        try {
            $this->compileSpecialNode($node, $attributes, $special, $out);
        } finally {
            $out->popScope();
            $out->indent(-1);
            $out->appendCode("});");
            $out->select($prev);
        }
    }

    private function compileBlock(DOMElement $node, array $attributes, array $special, CompilerBuffer $out) {
        $name = strtolower($special[self::T_BLOCK]->value);
        unset($special[self::T_BLOCK]);

        $prev = $out->select($name);

        $use = '$'.implode(', $', $out->getScopeVariables()).', $children';

        $out->appendCode("function () use ($use) {\n");
        $out->pushScope(['this' => 'props']);
        $out->indent(+1);

        try {
            $this->compileSpecialNode($node, $attributes, $special, $out);
        } finally {
            $out->indent(-1);
            $out->popScope();
            $out->appendCode("}");
            $out->select($prev);
        }

        return $out;
    }

    /**
     * Compile component inclusion and rendering.
     *
     * @param DOMElement $node
     * @param DOMAttr[] $attributes
     * @param DOMAttr[] $special
     * @param CompilerBuffer $out
     */
    protected function compileComponentInclude(DOMElement $node, array $attributes, array $special, CompilerBuffer $out) {
        // Generate the attributes into a property array.
        $props = [];
        foreach ($attributes as $name => $attribute) {
            /* @var DOMAttr $attr */
            if ($this->isExpression($attribute->value)) {
                $expr = $this->expr(substr($attribute->value, 1, -1), $out, $attribute);
            } else {
                $expr = var_export($attribute->value, true);
            }

            $props[] = var_export($name, true).' => '.$expr;
        }
        $propsStr = '['.implode(', ', $props).']';

        if (isset($special[self::T_WITH])) {
            $withExpr = $this->expr($special[self::T_WITH]->value, $out, $special[self::T_WITH]);
            unset($special[self::T_WITH]);

            $propsStr = empty($props) ? $withExpr : $propsStr.' + (array)'.$withExpr;
        } elseif (empty($props)) {
            // By default the current context is passed to components.
            $propsStr = $this->expr('this', $out);
        }

        // Compile the children blocks.
        $blocks = $this->compileComponentBlocks($node, $out);
        $blocksStr = $blocks->flush();

        if (isset($special[self::T_INCLUDE])) {
            $name = $this->expr($special[self::T_INCLUDE]->value, $out, $special[self::T_INCLUDE]);
        } else {
            $name = var_export($node->tagName, true);
        }

        $out->appendCode("\$this->write($name, $propsStr, $blocksStr);\n");
    }

    /**
     * @param DOMElement $parent
     * @return CompilerBuffer
     */
    protected function compileComponentBlocks(DOMElement $parent, CompilerBuffer $out) {
        $blocksOut = new CompilerBuffer(CompilerBuffer::STYLE_ARRAY, [
            'baseIndent' => $out->getIndent(),
            'indent' => $out->getIndent() + 1,
            'depth' => $out->getDepth(),
            'scopes' => $out->getAllScopes()
        ]);

        if ($this->isEmptyNode($parent)) {
            return $blocksOut;
        }

        $use = '$'.implode(', $', $blocksOut->getScopeVariables()).', $children';

        $blocksOut->appendCode("function () use ($use) {\n");
        $blocksOut->indent(+1);

        try {
            foreach ($parent->childNodes as $node) {
                $this->compileNode($node, $blocksOut);
            }
        } finally {
            $blocksOut->indent(-1);
            $blocksOut->appendCode("}");
        }

        return $blocksOut;
    }

    protected function compileTagComment(DOMElement $node, $attributes, $special, CompilerBuffer $out) {
        // Don't double up comments.
        if ($node->previousSibling && $node->previousSibling->nodeType === XML_COMMENT_NODE) {
            return;
        }

        $str = '<'.$node->tagName;
        foreach ($special as $attr) {
            /* @var DOMAttr $attr */
            $str .= ' '.$attr->name.(empty($attr->value) ? '' : '="'.htmlspecialchars($attr->value).'"');
        }
        $str .= '>';
        $comments = explode("\n", $str);
        foreach ($comments as $comment) {
            $out->appendCode("// $comment\n");
        }
    }

    protected function compileOpenTag(DOMElement $node, $attributes, CompilerBuffer $out, $force = false) {
        if ($node->tagName === self::T_X) {
            return;
        }

        $out->echoLiteral('<'.$node->tagName);

        foreach ($attributes as $name => $attribute) {
            /* @var DOMAttr $attribute */
            $out->echoLiteral(' '.$name.'="');

            // Check for an attribute expression.
            if ($this->isExpression($attribute->value)) {
                $out->echoCode('htmlspecialchars('.$this->expr(substr($attribute->value, 1, -1), $out, $attribute).')');
            } elseif (null !== $fn = $this->getAttributeFunction($attribute)) {
                $value  = call_user_func($fn, var_export($attribute->value, true));

                $out->echoCode("htmlspecialchars($value)");

            } else {
                $out->echoLiteral(htmlspecialchars($attribute->value));
            }

            $out->echoLiteral('"');
        }

        if ($node->hasChildNodes() || $force) {
            $out->echoLiteral('>');
        } else {
            $out->echoLiteral(" />");
        }
    }

    private function isExpression($value) {
        return preg_match('`^{\S.*}$`', $value);
    }

    protected function compileCloseTag(DOMElement $node, CompilerBuffer $out, $force = false) {
        if (($force || $node->hasChildNodes()) && $node->tagName !== self::T_X) {
            $out->echoLiteral("</{$node->tagName}>");
        }
    }

    protected function isEmptyText(DOMNode $node) {
        return $node instanceof \DOMText && empty(trim($node->data));
    }

    protected function isEmptyNode(DOMNode $node) {
        if (!$node->hasChildNodes()) {
            return true;
        }

        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                return false;
            }
            if ($childNode instanceof \DOMText && !$this->isEmptyText($childNode)) {
                return false;
            }
        }

        return true;
    }

    protected function compileIf(DOMElement $node, array $attributes, array $special, CompilerBuffer $out) {
        $this->compileTagComment($node, $attributes, $special, $out);
        $expr = $this->expr($special[self::T_IF]->value, $out);
        unset($special[self::T_IF]);

        $elseNode = $this->findSpecialNode($node, self::T_ELSE, self::T_IF);
        $out->setNodeProp($elseNode, 'skip', true);

        $out->appendCode('if ('.$expr.") {\n");
        $out->indent(+1);

        $this->compileSpecialNode($node, $attributes, $special, $out);

        $out->indent(-1);

        if ($elseNode) {
            list($attributes, $special) = $this->splitAttributes($elseNode);
            unset($special[self::T_ELSE]);

            $out->appendCode("} else {\n");

            $out->indent(+1);
            $this->compileSpecialNode($elseNode, $attributes, $special, $out);
            $out->indent(-1);
        }

        $out->appendCode("}\n");
    }

    protected function compileEach(DOMElement $node, array $attributes, array $special, CompilerBuffer $out) {
        $this->compileTagComment($node, $attributes, $special, $out);
        $this->compileOpenTag($node, $attributes, $out);

        $emptyNode = $this->findSpecialNode($node, self::T_EMPTY, self::T_ELSE);
        $out->setNodeProp($emptyNode, 'skip', true);

        if ($emptyNode === null) {
            $this->compileEachLoop($node, $attributes, $special, $out);
        } else {
            $expr = $this->expr("empty({$special[self::T_EACH]->value})", $out);

            list ($emptyAttributes, $emptySpecial) = $this->splitAttributes($emptyNode);
            unset($emptySpecial[self::T_EMPTY]);

            $out->appendCode('if ('.$expr.") {\n");

            $out->indent(+1);
            $this->compileSpecialNode($emptyNode, $emptyAttributes, $emptySpecial, $out);
            $out->indent(-1);

            $out->appendCode("} else {\n");

            $out->indent(+1);
            $this->compileEachLoop($node, $attributes, $special, $out);
            $out->indent(-1);

            $out->appendCode("}\n");
        }

        $this->compileCloseTag($node, $out);
    }

    protected function compileWith(DOMElement $node, array $attributes, array $special, CompilerBuffer $out) {
        $this->compileTagComment($node, $attributes, $special, $out);
        $with = $this->expr($special[self::T_WITH]->value, $out);

        $out->depth(+1);
        $scope = ['this' => $out->depthName('props')];
        if (!empty($special[self::T_AS]) && preg_match(self::IDENT_REGEX, $special[self::T_AS]->value, $m)) {
            // The template specified an x-as attribute to alias the with expression.
            $scope = [$m[1] => $out->depthName('props')];
        }
        unset($special[self::T_WITH], $special[self::T_AS]);

        $out->pushScope($scope);
        $out->appendCode('$'.$out->depthName('props')." = $with;\n");

        $this->compileSpecialNode($node, $attributes, $special, $out);

        $out->depth(-1);
        $out->popScope();
    }

    protected function compileLiteral(DOMElement $node, array $attributes, array $special, CompilerBuffer $out) {
        $this->compileTagComment($node, $attributes, $special, $out);
        unset($special[self::T_LITERAL]);

        $this->compileOpenTag($node, $attributes, $out);

        foreach ($node->childNodes as $childNode) {
            $html = $childNode->ownerDocument->saveHTML($childNode);
            $out->echoLiteral($html);
        }

        $this->compileCloseTag($node, $out);
    }

    protected function compileElement(DOMElement $node, array $attributes, CompilerBuffer $out) {
        $this->compileOpenTag($node, $attributes, $out);

        foreach ($node->childNodes as $childNode) {
            $this->compileNode($childNode, $out);
        }

        $this->compileCloseTag($node, $out);
    }

    /**
     * Find a special node in relation to another node.
     *
     * This method is used to find things such as x-empty and x-else elements.
     *
     * @param DOMElement $node The node to search in relation to.
     * @param string $attribute The name of the attribute to search for.
     * @param string $parentAttribute The name of the parent attribute to resolve conflicts.
     * @return DOMElement|null Returns the found element node or **null** if not found.
     */
    protected function findSpecialNode(DOMElement $node, $attribute, $parentAttribute) {
        // First look for a sibling after the node.
        for ($sibNode = $node->nextSibling; $sibNode !== null; $sibNode = $sibNode->nextSibling) {
            if ($sibNode instanceof DOMElement && $sibNode->hasAttribute($attribute)) {
                return $sibNode;
            }

            // Stop searching if we encounter another node.
            if (!$this->isEmptyText($sibNode)) {
                break;
            }
        }

        // Next look inside the node.
        $parentFound = false;
        foreach ($node->childNodes as $childNode) {
            if (!$parentFound && $childNode instanceof DOMElement && $childNode->hasAttribute($attribute)) {
                return $childNode;
            }

            if ($childNode instanceof DOMElement) {
                $parentFound = $childNode->hasAttribute($parentAttribute);
            } elseif ($childNode instanceof \DOMText && !empty(trim($childNode->data))) {
                $parentFound = false;
            }
        }

        return null;
    }

    /**
     * @param DOMElement $node
     * @param array $attributes
     * @param array $special
     * @param CompilerBuffer $out
     */
    private function compileEachLoop(DOMElement $node, array $attributes, array $special, CompilerBuffer $out) {
        $each = $this->expr($special[self::T_EACH]->value, $out);
        unset($special[self::T_EACH]);

        $as = ['', $out->depthName('props', 1)];
        $scope = ['this' => $as[1]];
        if (!empty($special[self::T_AS])) {
            if (preg_match('`(?:([a-z0-9]+)\s+)?([a-z0-9]+)`i', $special[self::T_AS]->value, $m)) {
                $scope = [$m[2] => $as[1]];
                if (!empty($m[1])) {
                    $scope[$m[1]] = $as[0] = $out->depthName('i', 1);
                }
            }
        }
        unset($special[self::T_AS]);
        if (empty($as[0])) {
            $out->appendCode("foreach ($each as \${$as[1]}) {\n");
        } else {
            $out->appendCode("foreach ($each as \${$as[0]} => \${$as[1]}) {\n");
        }
        $out->depth(+1);
        $out->indent(+1);
        $out->pushScope($scope);

        foreach ($node->childNodes as $childNode) {
            $this->compileNode($childNode, $out);
        }

        $out->indent(-1);
        $out->depth(-1);
        $out->popScope();
        $out->appendCode("}\n");
    }

    protected function ltrim($text, \DOMNode $node, CompilerBuffer $out) {
        if ($this->inPre($node)) {
            return $text;
        }

        $sib = $node->previousSibling ?: $node->parentNode;
        if ($sib === null || !$sib instanceof \DOMElement || $out->getNodeProp($sib, 'skip') || $sib->tagName === self::T_X) {
            return ltrim($text);
        }

        $text = preg_replace('`^\s*\n\s*`', "\n", $text, -1, $count);
        if ($count === 0) {
            $text = preg_replace('`^\s+`', ' ', $text);
        }

//        if ($sib !== null && ($sib->nodeType === XML_COMMENT_NODE || in_array($sib->tagName, static::$blocks))) {
//            return ltrim($text);
//        }
        return $text;
    }

    protected function rtrim($text, \DOMNode $node, CompilerBuffer $out) {
        if ($this->inPre($node)) {
            return $text;
        }

        $sib = $node->nextSibling ?: $node->parentNode;

        if ($sib === null || !$sib instanceof \DOMElement || $out->getNodeProp($sib, 'skip') || $sib->tagName === self::T_X) {
            return rtrim($text);
        }

        $text = preg_replace('`\s*\n\s*$`', "\n", $text, -1, $count);
        if ($count === 0) {
            $text = preg_replace('`\s+$`', ' ', $text);
        }

//        if ($sib !== null && ($sib->nodeType === XML_COMMENT_NODE || in_array($sib->tagName, static::$blocks))) {
//            return rtrim($text);
//        }
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

    private function compileChildBlock(DOMElement $node, array $attributes, array $special, CompilerBuffer $out) {
        /* @var DOMAttr $child */
        $child = $special[self::T_CHILDREN];
        unset($special[self::T_CHILDREN]);

        $key = $child->value === '' ? 0 : $child->value;
        $keyStr = var_export($key, true);

        $this->compileOpenTag($node, $attributes, $out, true);

        $out->appendCode("if (isset(\$children[{$keyStr}])) {\n");
        $out->indent(+1);
        $out->appendCode("\$children[{$keyStr}]();\n");
        $out->indent(-1);
        $out->appendCode("}\n");

        $this->compileCloseTag($node, $out, true);
    }

    /**
     * Compile an x-expr node.
     *
     * @param DOMElement $node The node to compile.
     * @param array $attributes The node's attributes.
     * @param array $special An array of special attributes.
     * @param CompilerBuffer $out The compiler output.
     */
    private function compileExpressionNode(DOMElement $node, array $attributes, array $special, CompilerBuffer $out) {
        $str = $raw = $node->nodeValue;
        $expr = $this->expr($str, $out);

        if (!empty($special[self::T_AS]) && preg_match(self::IDENT_REGEX, $special[self::T_AS]->value, $m)) {
            // The template specified an x-as attribute to alias the with expression.
            $scope = [$m[1] => $out->depthName('props', 1)];
            $out->pushScope($scope);
            $out->appendCode('$'.$out->depthName('props', 1)." = $expr;\n");
        } elseif (!empty($special[self::T_UNESCAPE])) {
            $out->echoCode($expr);
        } else {
            $out->echoCode('htmlspecialchars('.$expr.')');
        }
    }
}
