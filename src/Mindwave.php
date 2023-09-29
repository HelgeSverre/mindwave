<?php

namespace Mindwave\Mindwave;

use Mindwave\Mindwave\Agents\Agent;
use Mindwave\Mindwave\Brain\Brain;
use Mindwave\Mindwave\Brain\QA;
use Mindwave\Mindwave\Contracts\Embeddings;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\Contracts\Memory;
use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Memory\ConversationMemory;

class Mindwave
{
    protected Brain $brain;

    public function __construct(
        protected LLM $llm,
        protected Embeddings $embeddings,
        protected Vectorstore $vectorstore
    ) {
        $this->brain = new Brain(
            vectorstore: $vectorstore,
            embeddings: $embeddings,
        );
    }

    public function agent(
        Memory $memory = new ConversationMemory(),
        array $tools = []
    ): Agent {
        return new Agent(
            llm: $this->llm,
            brain: $this->brain,
            messageHistory: $memory,
            tools: $tools,
        );
    }

    public function qa(): QA
    {
        return new QA(
            llm: $this->llm,
            vectorstore: $this->vectorstore,
            embeddings: $this->embeddings,
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
