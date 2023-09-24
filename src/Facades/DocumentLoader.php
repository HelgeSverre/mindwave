<?php

namespace Mindwave\Mindwave\Facades;

use Illuminate\Support\Facades\Facade;
use Mindwave\Mindwave\Document\Loader;
use Mindwave\Mindwave\Document\Loader as Concrete;

/**
 * @mixin Loader
 */
class DocumentLoader extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Concrete::class;
    }
}
