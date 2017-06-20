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

        $rendered = $this->renderFixture('bi-component', $data);

        $this->assertEquals('<time datetime="2001-01-01T00:00:00-05:00">2001-01-01T00:00:00-05:00</time>', trim($rendered));
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
