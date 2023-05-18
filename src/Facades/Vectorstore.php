<?php

namespace Mindwave\Mindwave\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Mindwave\Mindwave\Vectorstore\VectorstoreManager
 */
class Vectorstore extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mindwave.vectorstore.manager';
    }
}
