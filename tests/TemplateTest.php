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
