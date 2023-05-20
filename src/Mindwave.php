<?php

namespace Mindwave\Mindwave;

use Mindwave\Mindwave\Agents\Agent;
use Mindwave\Mindwave\Brain\Brain;
use Mindwave\Mindwave\Contracts\Embeddings;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Memory\BaseChatMessageHistory;
use Mindwave\Mindwave\Memory\ConversationBufferMemory;

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

    public function agent(?BaseChatMessageHistory $memory = null): Agent
    {
        return new Agent(
            llm: $this->llm,
            messageHistory: $memory ?? ConversationBufferMemory::fromMessages([]),
            brain: $this->brain,
        );
    }

    public function agentWithTools(array $tools): Agent
    {
        return new Agent(
            llm: $this->llm,
            messageHistory: ConversationBufferMemory::fromMessages([]),
            brain: $this->brain,
            tools: $tools
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
