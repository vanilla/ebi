<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Ebi\Tests;

use Ebi\Ebi;
use Ebi\FilesystemLoader;

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

    /**
     * The **componentExists()** method should return true for an already defined component.
     */
    public function testComponentExists() {
        $ebi = new TestEbi($this);

        $this->assertFalse($ebi->componentExists('c-exists'));

        // Test with a custom defined component.
        $fn = function ($props, $children = []) {
        };
        $ebi->defineComponent('c-exists', $fn);
        $this->assertTrue($ebi->componentExists('c-exists', false));
    }

    /**
     * The **componentExists()** method should look to the loader for a component that isn't defined.
     */
    public function testComponentExistsWithLoader() {
        $basePath = __DIR__;

        $ebi = new Ebi(
            new FilesystemLoader("$basePath/fixtures"),
            "$basePath/cache/RuntimeTest2"
        );

        $this->assertFalse($ebi->componentExists('child', false));
        $this->assertTrue($ebi->componentExists('child'));
    }

    /**
     * A cached component should return **true** for **cacheKeyExists()**.
     */
    public function testCacheKeyExists() {
        $ebi = new TestEbi($this);

        $fn = $ebi->compile('cc-exists', 'Hello world!', 'cc-exists1');
        $this->assertTrue($ebi->cacheKeyExists('cc-exists1'));
    }

    /**
     * Trying to write a component that doesn't exist should output the "@component-not-found" component.
     */
    public function testComponentNotFound() {
        $ebi = new TestEbi($this);

        $this->expectOutputString('<!-- Ebi component "foo" not found. -->');
        $ebi->write('foo');
    }

    /**
     * A specific tag function should be called on attributes of that tag, but not other tags.
     */
    public function testTagFunction() {
        $ebi = new TestEbi($this);

        $tpl = <<<EOT
<link href="foo.css" rel="stylesheet"/><a href="bar.html'" />
EOT;

        $ebi->defineFunction('@link:href', function ($v) {
            return '/'.$v;
        });

        $ebi->compile('test-tag-function', $tpl, 'test-tag-function');

        $r = $ebi->render('test-tag-function');

        $this->assertSame('<link href="/foo.css" rel="stylesheet" /><a href="bar.html\'" />', $r);
    }

    /**
     * A generic exception encountered when writing a component should write out the "@exception" component.
     */
    public function testComponentException() {
        $ebi = new TestEbi($this);
        $ebi->defineComponent('foo', function () {
            throw new \Exception('This is my exception!');
        });

        $this->expectOutputString('
<!--
Ebi exception in component "foo".
This is my exception!
-->
');
        $ebi->write('foo');
    }
}
