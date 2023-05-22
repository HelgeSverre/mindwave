<?php

namespace Mindwave\Mindwave\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \Mindwave\Mindwave\Contracts\Vectorstore
 *
 * @see \Mindwave\Mindwave\Vectorstore\VectorstoreManager
 */
class Vectorstore extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mindwave.vectorstore.manager';
    }
}
