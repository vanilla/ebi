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
}
