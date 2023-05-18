<?php

namespace Mindwave\Mindwave\Embeddings;

use Illuminate\Support\Manager;
use Mindwave\Mindwave\Contracts\Embeddings;
use Mindwave\Mindwave\Embeddings\Drivers\OpenAIEmbeddings;
use OpenAI;

class EmbeddingsManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->config->get('mindwave-embeddings.default');
    }

    public function createOpenaiDriver(): Embeddings
    {
        return new OpenAIEmbeddings(
            client: OpenAI::client(
                apiKey: $this->config->get('mindwave-embeddings.embeddings.openai.api_key'),
                organization: $this->config->get('mindwave-embeddings.embeddings.openai.org_id')
            ),
            model: $this->config->get('mindwave-embeddings.embeddings.openai.model')
        );
    }
}
