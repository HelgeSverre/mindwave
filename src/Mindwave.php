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
use Mindwave\Mindwave\LLM\FunctionCalling\FunctionBuilder;
use Mindwave\Mindwave\LLM\Streaming\StreamedTextResponse;
use Mindwave\Mindwave\Memory\ConversationMemory;
use Mindwave\Mindwave\PromptComposer\PromptComposer;
use Mindwave\Mindwave\PromptComposer\Tokenizer\TokenizerInterface;

class Mindwave
{
    protected Brain $brain;

    public function __construct(
        protected LLM $llm,
        protected Embeddings $embeddings,
        protected Vectorstore $vectorstore,
        protected ?TokenizerInterface $tokenizer = null,
    ) {
        $this->brain = new Brain(
            vectorstore: $vectorstore,
            embeddings: $embeddings,
        );

        $this->tokenizer = $tokenizer ?? app(TokenizerInterface::class);
    }

    public function agent(
        Memory $memory = new ConversationMemory,
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

    public function classify($input, $classes)
    {
        if (is_array($classes)) {
            $values = $classes;
            $isEnum = false;
        } elseif (enum_exists($classes)) {
            $values = array_column($classes::cases(), 'value');
            $isEnum = true;
        } else {
            throw new InvalidArgumentException('classes provided is not an array, nor an enum.');
        }

        $builder = new FunctionBuilder;
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

        $classification = $response->arguments['classification'] ?? null;

        if ($isEnum) {
            return $classes::tryFrom($classification);
        }

        return $classification;
    }

    public function brain(): Brain
    {
        return $this->brain;
    }

    public function embeddings(): Embeddings
    {
        // TODO: accept driver, return driver
        return $this->embeddings;
    }

    public function vectorStore(): Vectorstore
    {
        return $this->vectorstore;
    }

    public function llm(?string $driver = null): LLM
    {
        if ($driver === null) {
            return $this->llm;
        }

        return app('mindwave.llm.manager')->driver($driver);
    }

    /**
     * Create a new prompt composer for auto-fitting prompts to context windows.
     */
    public function prompt(): PromptComposer
    {
        return new PromptComposer($this->tokenizer, $this->llm);
    }

    /**
     * Stream text generation from the LLM with SSE support.
     *
     * This method provides a convenient way to create streaming text responses
     * that can be consumed by web clients using Server-Sent Events (SSE).
     *
     * Usage in a Laravel controller:
     * ```php
     * public function chat(Request $request)
     * {
     *     return Mindwave::stream($request->input('prompt'))
     *         ->toStreamedResponse();
     * }
     * ```
     *
     * Client-side consumption:
     * ```javascript
     * const eventSource = new EventSource('/api/chat?q=Hello');
     * eventSource.addEventListener('message', (event) => {
     *     console.log('Received:', event.data);
     * });
     * eventSource.addEventListener('done', () => {
     *     eventSource.close();
     * });
     * ```
     *
     * @param  string  $prompt  The prompt to send to the LLM
     * @return StreamedTextResponse A helper for converting the stream to HTTP responses
     */
    public function stream(string $prompt): StreamedTextResponse
    {
        $generator = $this->llm->streamText($prompt);

        return new StreamedTextResponse($generator);
    }
}
