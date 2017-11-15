<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi\Tests;

use Ebi\Tests\Faker\FakeData;

class TemplateTest extends AbstractTest {
    public function testDiscussion() {
        $this->renderFixture('discussion', new FakeData());
    }

    public function testEachAs() {
        $this->renderFixture('each-as', new FakeData());
    }

    public function testComponentInclude() {
        $data = ['name' => 'foo'];

        $rendered = $this->renderFixture('Parent', $data);

        $this->assertEquals("<div>child: {$data['name']}</div>", trim($rendered));
    }

    public function testComponentRegister() {
        $data = ['dateInserted' => '2001-01-01'];

        $rendered = $this->renderFixture('x-component', $data);

        $this->assertEquals('<time datetime="2001-01-01T00:00:00-05:00">Mon, 01 Jan 2001 00:00:00 -0500</time>', trim($rendered));
    }

    /**
     * Test a specific breadcrumbs example.
     */
    public function testWithAsBreadcrumbsExample() {
        $r = $this->renderFixture('breadcrumbs-test', []);
        $this->assertEquals('3', $r);
    }

    /**
     * Test the hasChildren function.
     */
    public function testHasChildren() {
        $r = $this->renderFixture('has-children');
        $this->assertEquals('<div>child!</div>', trim($r));
    }

    /**
     * Script tags should be parsed like any other tag.
     */
    public function testScriptCompile() {
        $r = $this->renderFixture('debug', ['hi']);
        $this->assertEquals('<script>console.log(["hi"]);</script>', trim($r));
    }

    /**
     * This was a test that ended up boiling down to a bug where only the x-tag attribute was specified.
     */
    public function testBread2() {
        $r = $this->renderFixture('bread2', [['name' => 'a', 'url' => '#1'], ['name' => 'b', 'url' => '#2']]);

        $expected = <<<EOT
<nav><ol class="breadcrumbs-list" itemscope itemtype="http://schema.org/BreadcrumbList"><li class="breadcrumb-item" itemscope itemtype="http://schema.org/ListItem"><a href="#1">a</a></li><li class="breadcrumb-item" itemscope itemtype="http://schema.org/ListItem"><span>b</span></li></ol></nav>
EOT;

        $this->assertEquals($expected, $r);

    }

    /**
     * Multiple script assignments should work.
     *
     * There was a bug where the context was getting polluted.
     */
    public function testMultipleScriptAs() {
        $r = $this->renderFixture('multiple-script-as');
        $this->assertEquals('1,2', trim($r));
    }

    /**
     * Test a basic error expression.
     */
    public function testExprAttributeError() {
        $r = $this->renderFixture('expr-error');

        $this->assertContains('Error compiling expr-error near line 1.', $r);
    }

    /**
     * Test an error in a `<script type="ebi">` tag.
     */
    public function testExprScriptError() {
        $r = $this->renderFixture('expr-error-script');
        $this->assertContains('Error compiling expr-error-script near', $r);
    }

    /**
     * Test an error from an inline expression between `{...}`.
     */
    public function testExprInlineError() {
        $r = $this->renderFixture('expr-error-inline');
        $this->assertContains('Error compiling expr-error-inline near', $r);
    }

    /**
     * Test a basic valueless HTML5 attribute.
     */
    public function testHtml5Attribute() {
        $r = $this->renderFixture('html5-attribute');
        $this->assertEquals('<input type="checkbox" checked />', $r);
    }

    /**
     * Since style tags may parse differently than other tags lets do a basic test.
     */
    public function testStyleTag() {
        $r = $this->renderFixture('style-tag');

        $expected = <<<EOT
<style>.foo {
        color: #000;
}</style>
EOT;

        $this->assertHtmlEquals($expected, $r);
    }

    /**
     * An invalid identifier in `<script x-as>` should result in an error.
     */
    public function testScriptAsError() {
        $r = $this->renderFixture('script-as-error');
        $this->assertContains("Invalid identifier in x-as attribute.", $r);
    }

    /**
     * In invalid identifier in `x-with x-as` should result in an error.
     */
    public function testWithAsError() {
        $r = $this->renderFixture('with-as-error');
        $this->assertContains("Invalid identifier in x-as attribute.", $r);
    }

    /**
     * In invalid identifier in `x-each x-as` should result in an error.
     */
    public function testEachAsError() {
        $r = $this->renderFixture('each-as-error');
        $this->assertContains("Invalid identifier in x-as attribute.", $r);
    }

    /**
     * Expression assignments cannot be declared inside child blocks.
     */
    public function testExprBlockError() {
        $r = $this->renderFixture('expr-block-error');
        $this->assertContains("Expressions with x-as assignments cannot be declared inside child blocks.", $r);
    }

    /**
     * Test basic data escaping.
     */
    public function testEscaping() {
        $r = $this->renderFixture('escaping', [
            [1, 2, 3],
            $this,
            new \DateTime('2011-11-11', new \DateTimeZone('UTC')),
            '<>'
        ]);

        $this->assertEquals('|[array]|{object}|2011-11-11T00:00:00+00:00|&lt;&gt;|', $r);
    }

    /**
     * Test an x-block attribute error.
     */
    public function testBlockError() {
        $r = $this->renderFixture('block-error');
        $this->assertContains('Blocks must be direct descendants of component includes.', $r);
    }

    /**
     * Multiple blocks with the same name should all render.
     */
    public function testMultipleBlocks() {
        $r = $this->renderFixture('multiple-blocks');
        $this->assertHtmlEquals('<div><div>a</div><div>b</div></div>', $r);
    }

    /**
     * Test bug with dot syntax inside functions.
     */
    public function testFunctionDotSyntax() {
        $r = $this->renderFixture('dot-syntax', ['foo' => ['bar' => [1, 2, 3]]]);
        $this->assertEquals('3', $r);
    }

    /**
     * Test compilation of a function call with multiple arguments.
     */
    public function testMultipleFunctionArgsCall() {
        $r = $this->renderFixture('multiple-call-args');
        $this->assertEquals('123', $r);
    }

    public function testEachSpecial() {
        $r = $this->renderFixture('each-special', ['a' => 1, 'b' => 2]);

        $expected = <<<EOT
<ul>
<li id="a" data-index="0" class="first">1</li>
<li id="b" data-index="1" class="last">2</li>
</ul>
EOT;

        $this->assertHtmlEquals($expected, $r);
    }

    /**
     * Test a bug from production that resulted from odd caching behavior.
     */
    public function testRepro01() {
        $r = $this->renderFixture('repro01', ['data' => [1, 2]]);

        $expected = <<<EOT
{"name":"a","data":[1,2]}
{"name":"b","data":null}

{"name":"c","data":1}
EOT;

        $this->assertEquals($expected, $r);
    }

    /**
     *
     */
//    public function testVerbTense() {
//        $fakeData = new FakeData();
//
//        foreach ($fakeData['articles'] as $article) {
//            echo $article['headline']."\n";
//            echo $article['question']."\n";
//        }
//    }
}
