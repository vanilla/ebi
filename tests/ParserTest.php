<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi\Tests;

use Ebi\Compiler;
use Ebi\Tests\Faker\Social;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ParserTest extends TestCase {
    public function testIf() {
        $tpl = '<p x-if="empty(items)">There are no items!!!</p>';

        $compiler = new Compiler();
        $compiler->defineFunction('empty');

        $r = $compiler->compile($tpl, ['runtime' => false]);

        $fn = eval("return $r");

        $this->expectOutputString('<p>There are no items!!!</p>');
        $fn(['items' => []]);
    }

    public function testEach() {
        $tpl = '<ul x-each="people"><li>Hi {name}!</li></ul>';

        $compiler = new Compiler();

        $r = $compiler->compile($tpl);
    }

    public function testEachAs() {
        $tpl = '<ul x-each="comments" x-as="comment"><li>{name}: {comment.body}</li></ul>';

        $compiler = new Compiler();

        $r = $compiler->compile($tpl);
    }

    public function testParsing() {
        $html = '<foo>{this} is a <a x-if="{foo + bar}" literal s=\'foo bar\'>foo</a>       1 > 2<br> <Time foo.dateInserted /><each "a + b + c"></each></foo>';

        $parser = xml_parser_create_ns('UTF-8', ':');

        $handle = function ($parser, $data) {
            $foo = $data;
        };

        xml_set_element_handler($parser, $handle, $handle);
        xml_set_character_data_handler($parser, $handle);
        xml_set_default_handler($parser, $handle);

        $r = xml_parse_into_struct($parser, $html, $values, $index);
        $foo = xml_get_error_code($parser);
        $bar = xml_error_string($foo);

        $dom = new \DOMDocument();

        libxml_use_internal_errors(true);
        $r2 = $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOCDATA | LIBXML_NOXMLDECL);
        $errors = libxml_get_errors();


        $output = $dom->saveHTML();

        $struct = $this->visit($dom->firstChild);
    }

    private function visit(\DOMNode $node) {
        $r = [
            'name' => $node->nodeName,
            'type' => $node->nodeType
        ];

        if ($node->nodeType === XML_TEXT_NODE) {
            $r['text'] = $node->textContent;
        }

        if ($node->hasAttributes()) {
            $r['attributes'] = [];
            foreach ($node->attributes as $name => $attribute) {
                /* @var \DOMAttr $attribute */
                $r['attributes'][$name] = $attribute->value;
            }
        }

        if ($node->hasChildNodes()) {
            $r['children'] = [];
            foreach ($node->childNodes as $childNode) {
                $r['children'][] = $this->visit($childNode);
            }
        }

        return $r;
    }

    public function testExpression() {
        $lang = new ExpressionLanguage();

        $expr = $lang->compile('_.bar', ['_']);
    }

    /**
     * The `<x-expr>` element allows expressions to be in-lined.
     */
    public function testExpressionElement() {
        $ebi = new TestEbi($this);

        $src = <<<EOT
<script type="ebi">
    join(
        '|',
        [1, 2, 3]
    )
</script>
EOT;

        $ebi->compile('expr-elem', $src, 'expr-elem');
        $r = $ebi->render('expr-elem');

        $this->assertEquals('1|2|3', $r);
    }

    public function testExpressionElementWithAs() {
        $ebi = new TestEbi($this);

        $src = <<<EOT
<script x-as="foo">
    {
        a: 1,
        b: 2
    }
</script>{unescape(json_encode(foo))}{bar}
EOT;

        $ebi->defineFunction('json_encode');
        $ebi->compile('expr-elem-as', $src, 'expr-elem-as');
        $r = $ebi->render('expr-elem-as', ['bar' => '!']);

        $this->assertEquals('{"a":1,"b":2}!', $r);
    }
}
