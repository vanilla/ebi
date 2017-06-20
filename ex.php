<?php

return function ($context = [], $hat = null) {
    $fn = function () {
        return $this;
    };

    return $fn();
};

function ($context) {
    if (empty($context['items'])) {
        echo '<p>There are no items!!!</p>';
    }
}
