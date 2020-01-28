<?php

namespace Neoan3\Components;

use Neoan3\Frame\Vastn3;

/**
 * Class some
 * @package Neoan3\Components
 */
class {{name}} extends Vastn3
{
    /**
     * @var array of dependencies as strings
     * NOTE: only global params can be passed in
     */
    private static array $requiredComponents = [];

    /**
     * This function is called by the vast-n3 frame
     *
     * @return array
     */
    static function dependencies()
    {
        return self::$requiredComponents;
    }
}

