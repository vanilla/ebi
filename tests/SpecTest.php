<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi\Tests;


class SpecTest extends AbstractTest {
    /**
     * @param $name
     * @param $template
     * @param $data
     * @param $expected
     * @dataProvider provideLanguageTests
     */
    public function testLanguage($name, $template, $data, $expected) {
        $this->doTest($name, $template, $data, $expected);
    }

    public function provideLanguageTests() {
        $r = $this->provideSpecTests('01-language.yml');
        return $r;
    }

    /**
     * @param $name
     * @param $template
     * @param $data
     * @param $expected
     * @dataProvider provideHtmlUtilTests
     */
    public function testHtmlUtilities($name, $template, $data, $expected) {
        $this->doTest($name, $template, $data, $expected);
    }

    public function provideHtmlUtilTests() {
        $r = $this->provideSpecTests('03-html-utils.yml');
        return $r;
    }
}
