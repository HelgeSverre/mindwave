<?php

namespace Mindwave\Mindwave\Agents;

use Mindwave\Mindwave\Brain\Brain;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\Memory\ConversationBufferMemory;
use Mindwave\Mindwave\Prompts\PromptTemplate;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;

class QAWithSourcesAgent
{
    protected LLM $llm;

    protected ConversationBufferMemory $messageHistory;

    protected Brain $brain;

    public function __construct(LLM $llm, ConversationBufferMemory $messageHistory, Brain $brain)
    {
        $this->llm = $llm;
        $this->messageHistory = $messageHistory;
        $this->brain = $brain;
    }

    public function ask($input)
    {
        $this->messageHistory->addUserMessage($input);

        $relevantDocuments = $this->brain->search($input, count: 3);

        $summary = collect($relevantDocuments)
            ->map(function (VectorStoreEntry $entry) {
                return sprintf("Content: %s\nSource: %s", $entry->metadata['_mindwave_content'], $entry->id);
            })
            ->join("\n");

        $prompt = PromptTemplate::fromPath(
            __DIR__.'/../Prompts/Templates/qa_with_sources.txt',
        );

        $answer = $this->llm->predict($prompt->format([
            'question' => $input,
            'summaries' => $summary,
        ]));

        return $prompt->parse($answer);
    }
}
