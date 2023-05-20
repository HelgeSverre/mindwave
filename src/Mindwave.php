<?php

namespace Mindwave\Mindwave;

use Mindwave\Mindwave\Brain\Brain;
use Mindwave\Mindwave\Contracts\Embeddings;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\Contracts\Vectorstore;

class Mindwave
{
    protected LLM $llm;
    protected Embeddings $embeddings;
    protected Vectorstore $vectorstore;
    protected Brain $brain;

    public function __construct(LLM $llm, Embeddings $embeddings, Vectorstore $vectorstore)
    {
        $this->llm = $llm;
        $this->embeddings = $embeddings;
        $this->vectorstore = $vectorstore;
        $this->brain = new Brain(
            vectorstore: $vectorstore,
            embeddings: $embeddings,
        );
    }

    public function brain(): Brain
    {
        return $this->brain;
    }

    public function embeddings(): Embeddings
    {
        return $this->embeddings;
    }

    public function vectorStore(): Vectorstore
    {
        return $this->vectorstore;
    }

    public function llm(): LLM
    {
        return $this->llm;
    }
}
