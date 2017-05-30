<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi;


interface TemplateLoaderInterface {
    /**
     * Return the cache key of a component.
     *
     * @param string $component The name of the component.
     * @return string Returns the unique key of the component.
     */
    public function cacheKey($component);

    /**
     * Return the template source of a component.
     *
     * @param string $component The name of the component.
     * @return string Returns the template source of the component.
     */
    public function load($component);
}
