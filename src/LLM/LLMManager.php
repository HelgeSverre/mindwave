<?php

namespace Mindwave\Mindwave\LLM;

use Illuminate\Support\Manager;

class LLMManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->config->get('mindwave-llm.default');
    }
}
