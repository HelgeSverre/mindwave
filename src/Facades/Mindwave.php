<?php

namespace Mindwave\Mindwave\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Mindwave\Mindwave\Mindwave
 */
class Mindwave extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mindwave';
    }
}
