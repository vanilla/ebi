<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Ebi\Tests;

use Ebi\Ebi;

class TestEbi extends Ebi {
    public $loader;

    public function __construct($class) {
        $this->loader = new TestTemplateLoader();
        $cachePath = __DIR__.'/cache/'.trim(strrchr(is_object($class) ? get_class($class) : $class, '\\'), '\\');
        parent::__construct($this->loader, $cachePath);
    }
}
