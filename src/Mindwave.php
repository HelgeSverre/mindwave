<?php

namespace Mindwave\Mindwave;

use InvalidArgumentException;
use Mindwave\Mindwave\Agents\Agent;
use Mindwave\Mindwave\Brain\Brain;
use Mindwave\Mindwave\Brain\QA;
use Mindwave\Mindwave\Contracts\Embeddings;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\Contracts\Memory;
use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\LLM\Drivers\OpenAI\Functions\FunctionBuilder;
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

    public function classify($input, $classes): ?string
    {

        if (is_array($classes)) {
            $values = $classes;
        } elseif (enum_exists($classes)) {
            $values = array_column($classes::cases(), 'value');
        } else {
            throw new InvalidArgumentException('classes provided is not an array, nor an enum.');
        }

        $builder = new FunctionBuilder();
        $builder
            ->addFunction(
                name: 'submit_classification',
                description: 'Provide a classification for the input',
            )
            ->addParameter(
                name: 'classification',
                type: 'string',
                description: 'The classification for the input',
                isRequired: true,
                enum: $values
            );

        $response = $this->llm->functionCall(
            prompt: "Classify '$input' into one of the provided classifications",
            functions: $builder,
            requiredFunction: 'submit_classification'
        );

        return $response->arguments['classification'] ?? null;
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
