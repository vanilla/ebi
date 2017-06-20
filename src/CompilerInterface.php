<?php

/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi;

interface CompilerInterface {
    /**
     * Compile a template into PHP code.
     *
     * @param string $template The template to compile.
     * @param array $options Options for the compilation.
     * @return string Returns the compiled template.
     */
    public function compile($template, array $options = []);
}
