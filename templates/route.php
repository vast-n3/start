<?php

namespace Neoan3\Components;

use Neoan3\Frame\Vastn3;

/**
 * Class {{name}}
 * @package Neoan3\Components
 */
class {{name}} extends Vastn3
{
    /**
     * @var array
     */
    private $vueComponents = [];
    
    /**
     * init route 
     */
    function init()
    {
        $this
            ->hook('main', '{{name.camel}}', [])
            ->vueComponents($this->vueComponents, [])
            ->output();
    }
}
