<?php

namespace Mindwave\Mindwave\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Mindwave\Mindwave\LLM\LLMManager
 */
class LLM extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mindwave.llm.manager';
    }
}
