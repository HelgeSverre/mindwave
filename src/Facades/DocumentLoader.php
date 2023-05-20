<?php

namespace Mindwave\Mindwave\Facades;

use Illuminate\Support\Facades\Facade;
use Mindwave\Mindwave\Document\Loader as Concrete;

class DocumentLoader extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Concrete::class;
    }
}
