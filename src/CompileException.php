<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Ebi;

class CompileException extends \LogicException {
    private $context = [];

    public function __construct($message = "", $context = [], $previous = null) {
        parent::__construct($message, 500, $previous);
        $this->context = $context;
    }

    /**
     * Get the context.
     *
     * @return array Returns the context.
     */
    public function getContext() {
        return $this->context;
    }
}
