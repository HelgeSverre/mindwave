<?php

namespace Mindwave\Mindwave\Agents;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Mindwave\Mindwave\Brain\Brain;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\Contracts\Tool;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Memory\ConversationBufferMemory;
use Mindwave\Mindwave\Prompts\OldPromptTemplate;

class Agent
{
    public function __construct(
        readonly public LLM $llm,
        readonly public ConversationBufferMemory $messageHistory,
        readonly public Brain $brain,
        readonly public array $tools = [])
    {
    }

    /**
     * @return Collection<Tool>
     */
    protected function toolCollection(): Collection
    {

    }

    public function parseActionResponse(string $text): ?array
    {
        $cleaned = Str::of($text)->between('```json', '```')->trim()->toString();

        $json = json_decode($cleaned, true);

        if (! $json) {
            throw new Exception("Could not parse response: $cleaned");
        }

        return $json;
    }

    public function runTool($action)
    {

        $toolName = $action['action'];

        $input = $action['action_input'];

        // TODO(16 May 2023) ~ Helge: Cleanup this
        /** @var null|Tool $selectedTool */
        $selectedTool = $this->toolCollection()->first(fn (Tool $tool) => $tool->name() === $toolName);

        if ($selectedTool) {
            return $selectedTool->run($input);
        }

        return 'No tool found with that name';
    }

    public function ask($input)
    {
        $this->messageHistory->addUserMessage($input);

        $relevantDocuments = $this->brain->search($input, count: 3);

        // TODO(18 mai 2023) ~ Helge: Abstract away
        $initialPrompt = OldPromptTemplate::combine([
            file_get_contents(__DIR__.'/../Prompts/Templates/prefix.txt'),
            $this->toolCollection()->isEmpty()
                ? OldPromptTemplate::from(__DIR__.'/../Prompts/Templates/no_tools.txt')->format()
                : OldPromptTemplate::from(__DIR__.'/../Prompts/Templates/tools.txt')->format([
                    '[TOOL_DESCRIPTIONS]' => $this->toolCollection()->map(fn ($t) => sprintf('> %s: %s', $t->name(), $t->description()))->join("\n"),
                    '[TOOL_LIST]' => $this->toolCollection()->map(fn (Tool $tool) => $tool->name())->join(', '),
                ]),
            OldPromptTemplate::from(__DIR__.'/../Prompts/Templates/relevant_documents.txt')->format([
                '[DOCUMENTS]' => collect($relevantDocuments)->map(fn (Document $document, $i) => "[$i] - ".$document->content())->join("\n"),
            ]),
            OldPromptTemplate::from(__DIR__.'/../Prompts/Templates/history.txt')->format([
                '[HISTORY]' => $this->messageHistory->conversationAsString('Human', 'Mindwave'),
            ]),
            OldPromptTemplate::from(__DIR__.'/../Prompts/Templates/input.txt')->format([
                '[INPUT]' => $input,
            ]),
        ]);

        $answer = $this->llm->predict($initialPrompt);

        if (! $answer) {
            // TODO(18 mai 2023) ~ Helge: Retry until "max retries" is exhausted?

            throw new Exception('No response');
        }

        // TODO(16 May 2023) ~ Helge: Output parser
        $parsed = $this->parseActionResponse($answer);

        if ($parsed['action'] === 'Final Answer') {
            $this->messageHistory->addAiMessage($parsed['action_input']);

            return $parsed['action_input'];
        }

        // TODO(18 mai 2023) ~ Helge: Put this in a loop until final answer found or max attempts is exhausted
        // ======================================================================================================

        $finalPrompt = OldPromptTemplate::combine([
            $initialPrompt,
            OldPromptTemplate::from(__DIR__.'/../Prompts/Templates/tool_response.txt')->format([
                '[TOOL_RESPONSE]' => $this->runTool($parsed),
            ]),
        ]);

        $answer = $this->llm->predict($finalPrompt);

        // TODO(16 May 2023) ~ Helge: Output parser
        $parsed = $this->parseActionResponse($answer);

        if ($parsed['action'] === 'Final Answer') {
            $this->messageHistory->addAiMessage($parsed['action_input']);

            return $parsed['action_input'];
        }
        // ======================================================================================================
    }
}
