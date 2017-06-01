<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi;


class FilesystemLoader implements TemplateLoaderInterface {
    /**
     * @var string
     */
    private $basePath;

    public function __construct($basePath) {
        $this->basePath = $basePath;
    }

    /**
     * Return the cache key of a component.
     *
     * @param string $component The name of the component.
     * @return string Returns the unique key of the component.
     */
    public function cacheKey($component) {
        $subpath = $this->componentPath($component, false);

        if (empty($subpath)) {
            return null;
        } else {
            return str_replace(DIRECTORY_SEPARATOR, '.', $subpath);
        }
    }

    /**
     * Return the template source of a component.
     *
     * @param string $component The name of the component.
     * @return string Returns the template source of the component.
     */
    public function load($component) {
        $path = $this->componentPath($component, true);

        if (empty($path)) {
            return null;
        } else {
            return file_get_contents($path);
        }
    }

    private function componentPath($component, $full = true) {
        $subpath = str_replace('.', DIRECTORY_SEPARATOR, $component);

        do {
            $path = "{$this->basePath}/$subpath.html";
            if (file_exists($path)) {
                return $full ? $path : $subpath;
            }

        } while (!empty($subpath));

        return '';
    }

    /**
     * Get the basePath.
     *
     * @return string Returns the basePath.
     */
    public function getBasePath() {
        return $this->basePath;
    }

    /**
     * Set the basePath.
     *
     * @param string $basePath
     * @return $this
     */
    public function setBasePath($basePath) {
        $this->basePath = $basePath;
        return $this;
    }
}
