<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi\Tests;

use Ebi\Ebi;
use Ebi\FilesystemLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractTest extends TestCase {
    /**
     * Assert that two HTML strings are functionally equivalent, accounting for variances in whitespace.
     *
     * @param string $a
     * @param string $b
     * @param $message
     */
    public function assertHtmlEquals($a, $b, $message = '') {
        $a1 = $this->normalizeHtml($a);
        $b1 = $this->normalizeHtml($b);

        $this->assertEquals($a1, $b1, $message);
    }

    /**
     * Normalize an HTML string.
     *
     * @param $html
     * @return mixed|string
     */
    protected function normalizeHtml($html) {
        // Remove multiple whitespace characters.
        $html = preg_replace('`\s+`', ' ', $html);
        // Tidy the html.
        $opts = array(
            'tidy' => '2s2n'
        );

        $html = trim(\Htmlawed::filter($html, $opts));
        return $html;
    }

    public function provideSpecTests($file) {
        $path = __DIR__."/specs/$file";
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("YAML file does not exist: $path", 500);
        }
        $data = Yaml::parse(file_get_contents($path));
        if ($data === false) {
            throw new \InvalidArgumentException("Invalid YAML file: $path", 500);
        }

        $context = ['name' => basename($file, '.yml'), 'template' => '', 'data' => '', 'expected' => ''];

        $r = $this->unwindSpec($data, $context);
        return $r;
    }

    public function renderFixture($component, $data = []) {
        $ebi = new Ebi(new FilesystemLoader(__DIR__.'/fixtures'), __DIR__.'/cache/fixtures');
        $ebi->defineFunction('json_encode');

        $rendered = $ebi->render($component, $data);

        $renderedPath = __DIR__."/cache/fixtures/rendered/$component.html";
        if (!file_exists(dirname($renderedPath))) {
            mkdir(dirname($renderedPath), 0777, true);
        }
        file_put_contents($renderedPath, $rendered);

        return $rendered;
    }

    public function doTest($name, $template, $data, $expected) {
//        if ($name !== '02-components x-children nested') {
//            return;
//        }

        $loader = new TestTemplateLoader();
        $ebi = new Ebi($loader, __DIR__.'/cache/specs');

        $loader->addTemplate($name, $template);
        $rendered = $ebi->render($name, $data);

        $this->assertEquals($expected, $rendered, "The \"$name\" test does not match.");
    }

    private function unwindSpec($data, array $context) {
        // Look for context stuff.
        if (isset($data['name'])) {
            $context['name'] .= ' '.$data['name'];
        }
        if (isset($data['template'])) {
            $context['template'] = $data['template'];
        }
        if (array_key_exists('data', $data)) {
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
