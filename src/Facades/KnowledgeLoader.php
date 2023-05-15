<?php

namespace Mindwave\Mindwave\Facades;

use Illuminate\Support\Facades\Facade;
use Mindwave\Mindwave\Knowledge\KnowledgeLoader as Concrete;

class KnowledgeLoader extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Concrete::class;
    }
}