<?php

namespace Mindwave\Mindwave\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \Mindwave\Mindwave\Mindwave
 */
class Mindwave extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Mindwave\Mindwave\Mindwave::class;
    }
}
