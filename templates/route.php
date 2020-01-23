<?php

namespace Neoan3\Components;

use Neoan3\Frame\Vastn3;

class {{name}} extends Vastn3
{
    function init()
    {
        $this
            ->hook('main', '{{name.camel}}', [])
            ->output();
    }
}
