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
        $this->renderTemplate('discussion.html', new FakeData());
    }

    public function testEachAs() {
        $this->renderTemplate('each-as.html', new FakeData());
    }

    /**
     *
     */
    public function testVerbTense() {
        $fakeData = new FakeData();

        foreach ($fakeData['articles'] as $article) {
            echo $article['headline']."\n";
//            echo $article['question']."\n";
        }
    }
}
