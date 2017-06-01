<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi;


class CompilingLoader implements ComponentLoaderInterface {
    /**
     * @var TemplateLoaderInterface
     */
    private $templateLoader;

    /**
     * @var string
     */
    private $cachePath;

    /**
     * @var Compiler
     */
    private $compiler;

    public function __construct(TemplateLoaderInterface $templateLoader, $cachePath) {
        $this->templateLoader = $templateLoader;
        $this->cachePath = $cachePath;
        $this->compiler = new Compiler();
    }

    /**
     * A safe version of {@link file_put_contents()} that also clears op caches.
     *
     * @param string $path The path to save to.
     * @param string $contents The contents of the file.
     * @return bool Returns **true** on success or **false** on failure.
     */
    private function filePutContents($path, $contents) {
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        $tmpPath = tempnam(dirname($path), 'ebi-');
        $r = false;
        if (file_put_contents($tmpPath, $contents) !== false) {
            chmod($tmpPath, 0664);
            $r = rename($tmpPath, $path);
        }

        if (function_exists('apc_delete_file')) {
            // This fixes a bug with some configurations of apc.
            @apc_delete_file($path);
        } elseif (function_exists('opcache_invalidate')) {
            @opcache_invalidate($path);
        }

        return $r;
    }

    /**
     * Load a component.
     *
     * @param string $component The name of the component to load.
     * @param Ebi $ebi The engine loading the component.
     * @return callable|null Returns the component.
     */
    public function load($component, Ebi $ebi) {
        $cacheKey = $this->templateLoader->cacheKey($component);
        $cachePath = "{$this->cachePath}/$cacheKey.php";

        if (!file_exists($cachePath)) {
            $src = $this->templateLoader->load($component);

            $php = $this->compiler->compile($src, ['basename' => $component]);
            $comment = "/*\n".str_replace('*/', '❄/', trim($src))."\n*/";

            $this->filePutContents($cachePath, "<?php\n$comment\n$php");
        }

        $fn = $ebi->requireFile($cachePath);

        if (is_callable($fn) && basename($cacheKey, '.php') === $component) {
            $ebi->register($component, $fn);
        }
    }
}
