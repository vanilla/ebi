<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Ebi;


interface ComponentLoaderInterface {
    /**
     * Load a component.
     *
     * @param string $component The name of the component to load.
     * @param Ebi $ebi The engine loading the component.
     * @return callable|null Returns the component.
     */
    public function load($component, Ebi $ebi);
}
