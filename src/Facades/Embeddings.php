<?php

namespace Mindwave\Mindwave\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \Mindwave\Mindwave\Contracts\Embeddings
 *
 * @see \Mindwave\Mindwave\Embeddings\EmbeddingsManager
 */
class Embeddings extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mindwave.embeddings.manager';
    }
}
