<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi\Tests;


use Ebi\Compiler;
use Ebi\Ebi;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase {
    public function provideSpecTests($file) {
        $path = __DIR__."/specs/$file";
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("YAML file does not exist: $path", 500);
        }
        $data = yaml_parse_file($path);
        if ($data === false) {
            throw new \InvalidArgumentException("Invalid YAML file: $path", 500);
        }

        $context = ['name' => basename($file, '.yml'), 'template' => '', 'data' => '', 'expected' => ''];

        $r = $this->unwindSpec($data, $context);
        return $r;
    }

    public function renderTemplate($filename, $data) {
        $template = file_get_contents(__DIR__."/fixtures/$filename");
        $compiler = new Compiler();

        $php = $compiler->compile($template);
        $templateComment = "/*\n".str_replace('*/', '❄/', trim($template))."\n*/";

        $cachePath = __DIR__."/cache/fixtures/$filename.php";
        if (!file_exists(dirname($cachePath))) {
            mkdir(dirname($cachePath), 0777, true);
        }

        file_put_contents($cachePath, "<?php\n$templateComment\nreturn $php");

        $fn = require $cachePath;
        $this->assertInstanceOf(\Closure::class, $fn);

        $hat = new Ebi();
        $fn = \Closure::bind($fn, $hat, Ebi::class);

        ob_start();
        $errs = error_reporting(error_reporting() & ~E_NOTICE & ~E_WARNING);
        $fn($data);
        error_reporting($errs);
        $rendered = ob_get_clean();

        $renderedPath = __DIR__."/cache/fixtures/rendered/$filename";
        if (!file_exists(dirname($renderedPath))) {
            mkdir(dirname($renderedPath), 0777, true);
        }
        file_put_contents($renderedPath, $rendered);
    }

    public function doTest($name, $template, $data, $expected) {
        $compiler = new Compiler();

        $php = $compiler->compile($template);
        $templateComment = "/*\n".str_replace('*/', '❄/', trim($template))."\n*/";

        $cachePath = __DIR__."/cache/specs/$name.php";
        if (!file_exists(dirname($cachePath))) {
            mkdir(dirname($cachePath), 0777, true);
        }

        file_put_contents($cachePath, "<?php\n$templateComment\nreturn $php");
        $fn = require $cachePath;
        $this->assertInstanceOf(\Closure::class, $fn);

        $hat = new Ebi();
        $fn = \Closure::bind($fn, $hat, Ebi::class);

        ob_start();
        $errs = error_reporting(error_reporting() & ~E_NOTICE & ~E_WARNING);
        $fn($data);
        error_reporting($errs);
        $rendered = ob_get_clean();

        $this->assertEquals($expected, $rendered);
    }

    private function unwindSpec($data, array $context) {
        // Look for context stuff.
        if (isset($data['name'])) {
            $context['name'] .= ' '.$data['name'];
        }
        if (isset($data['template'])) {
            $context['template'] = $data['template'];
        }
        if (isset($data['data'])) {
            $context['data'] = $data['data'];
        }
        if (isset($data['expected'])) {
            $context['expected'] = $data['expected'];
        }

        if (empty($data['tests'])) {
            return [$context['name'] => $context];
        } else {
            $result = [];
            foreach ($data['tests'] as $i => &$test) {
                if (empty($test['name'])) {
                    $test['name'] = (string)$i;
                }
                $result = array_merge($result, $this->unwindSpec($test, $context));
            }
            return $result;
        }
    }
}
