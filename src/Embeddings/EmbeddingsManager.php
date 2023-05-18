<?php

namespace Mindwave\Mindwave\Embeddings;

use Illuminate\Support\Manager;

class EmbeddingsManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->config->get('mindwave-embeddings.default');
    }
}
