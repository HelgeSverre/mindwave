<?php

namespace Mindwave\Mindwave;

use Mindwave\Mindwave\Agents\Agent;
use Mindwave\Mindwave\Brain\Brain;
use Mindwave\Mindwave\Contracts\Embeddings;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\Contracts\Vectorstore;

class Mindwave
{
    // TODO(11 May 2023) ~ Helge: implement driver/manager to get a brain, agent, add tools, etc
    protected ?Brain $brain = null;

    public function brain(): Brain
    {
        if ($this->brain == null) {
            $this->brain = new Brain(
                vectorstore: $this->vectorStore(),
                embeddings: $this->embeddings(),
            );
        }

        return $this->brain;
    }

    public function agent(): Agent
    {
        // TODO(14 mai 2023) ~ Helge: implement
    }

    public function embeddings(): Embeddings
    {
    }

    public function vectorStore(): Vectorstore
    {
    }

    public function llm(): LLM
    {
    }
}
