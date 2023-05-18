<?php

namespace Mindwave\Mindwave\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Mindwave\Mindwave\Embeddings\EmbeddingsManager
 */
class Embeddings extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mindwave.embeddings.manager';
    }
}
