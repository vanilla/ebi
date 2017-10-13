<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Ebi\Tests;

use Ebi\Ebi;

class RuntimeTest extends AbstractTest {
    public function testMeta() {
        $ebi = new TestEbi($this);

        $ebi->loader->addTemplate('test', '{@foo.bar}');
        $ebi->setMeta('foo', ['bar' => 'bar']);

        $this->assertEquals('bar', $ebi->render('test'));
    }

    /**
     * A closure that is added with **defineFunction** should be accessible with **call**.
     */
    public function testCallClosure() {
        $ebi = new TestEbi($this);

        $ebi->defineFunction('foo', function ($a, $b) {
            return $a.$b.'!';
        });

        $v = $ebi->call('foo', 'bar', 'baz');
        $this->assertEquals('barbaz!', $v);

    }

    /**
     * A missing function should throw a **RuntimeException**.
     *
     * @expectedException \Ebi\RuntimeException
     */
    public function testCallMissingFunction() {
        $ebi = new TestEbi($this);

        $ebi->call('missing', 'foo');
    }

    /**
     * A missing component should not be cached.
     */
    public function testCacheMissingComponent() {
        $ebi = new TestEbi($this);

        $component = 'fooz';
        $cacheKey = $ebi->getTemplateLoader()->cacheKey($component);
        $cachePath = $ebi->getCachePath()."/$cacheKey.php";

        $this->assertFileNotExists($cachePath);

        $ebi->lookup($component);

        $this->assertFileNotExists($cachePath);
    }

    /**
     * A defined component should be returned when looked up with any namespace.
     */
    public function testComponentNamespaceStripping() {
        $ebi = new TestEbi($this);

        $fn = function ($props, $children = []) {

        };

        $ebi->defineComponent('foo', $fn);

        $component = $ebi->lookup('bar:foo');
        $this->assertSame($fn, $component);
    }
}
