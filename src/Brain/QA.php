<?php

namespace Mindwave\Mindwave\Brain;

use Mindwave\Mindwave\Contracts\Embeddings;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\Contracts\Vectorstore;

class QA
{
    // TODO: move into pre-defined prompt template
    protected string $systemMessageTemplate =
        'Use the following pieces of context to answer the question of the user.'.
        "If you don't know the answer, just say that you don't know, don't try to make up an answer.".
        "\n\n{context}.";

    // TODO: Inject brain?
    public function __construct(
        protected LLM $llm,
        protected Vectorstore $vectorstore,
        protected Embeddings $embeddings,

    ) {}

    public function answerQuestion(string $question): string
    {
        $systemMessage = $this->searchDocumentAndCreateSystemMessage($question);

        $this->llm->setSystemMessage($systemMessage);

        return $this->llm->generateText($question);
    }

    protected function searchDocumentAndCreateSystemMessage(string $question): string
    {
        $embedding = $this->embeddings->embedText($question);

        $entries = $this->vectorstore->similaritySearch($embedding);

        if ($entries === []) {
            return "I don't know. I didn't find any document to answer the question";
        }

        $context = '';
        foreach ($entries as $document) {
            $context .= $document->document->content()."\n\n\n---\n\n\n";
        }

        return $this->createSystemMessage($context);
    }

    protected function createSystemMessage(string $context): string
    {
        return str_replace('{context}', $context, $this->systemMessageTemplate);
    }
}
