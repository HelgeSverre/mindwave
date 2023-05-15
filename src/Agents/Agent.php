<?php

namespace Mindwave\Mindwave\Agents;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\Contracts\Tool;
use Mindwave\Mindwave\Memory\ChatMessageHistory;
use Mindwave\Mindwave\PromptTemplate;

class Agent
{
    protected LLM $llm;

    /** @var Collection<Tool> */
    protected Collection $tools;

    protected ChatMessageHistory $messageHistory;

    public function __construct(LLM $llm, ChatMessageHistory $messageHistory, array $tools = [])
    {
        $this->llm = $llm;
        $this->messageHistory = $messageHistory;
        $this->tools = collect($tools);
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
        $selectedTool = $this->tools->first(fn (Tool $tool) => $tool->name() === $toolName);

        if ($selectedTool) {
        return $selectedTool->run($input);
        }

        return 'No tool found with that name';
    }

    public function ask($input)
    {
        $this->messageHistory->addUserMessage($input);

        $initialPrompt = PromptTemplate::combine([
            file_get_contents(__DIR__.'/../Prompts/1_prefix.txt'),
            PromptTemplate::from(__DIR__.'/../Prompts/2_tools.txt')->format([
                '[TOOL_DESCRIPTIONS]' => $this->tools->map(fn ($t) => sprintf('> %s: %s', $t->name(), $t->description()))->join("\n"),
                '[TOOL_LIST]' => $this->tools->map(fn (Tool $tool) => $tool->name())->join(', '),
            ]),
            PromptTemplate::from(__DIR__.'/../Prompts/history.txt')->format([
                '[HISTORY]' => $this->messageHistory->conversationAsString('Human', 'Turid'),
            ]),
            PromptTemplate::from(__DIR__.'/../Prompts/3_input.txt')->format([
                '[INPUT]' => $input,
            ]),
        ]);

        $answer = $this->llm->predict($initialPrompt);

        if (! $answer) {
            throw new Exception('No response');
        }

        // TODO(16 May 2023) ~ Helge: Output parser
        $parsed = $this->parseActionResponse($answer);

        if ($parsed['action'] === 'Final Answer') {
            $this->messageHistory->addAiMessage($parsed['action_input']);

            return $parsed['action_input'];
        }

        $finalPrompt = PromptTemplate::combine([
            $initialPrompt,
            PromptTemplate::from(base_path('app/Robot/Prompts/no/4_tool_response.txt'))->format([
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
    }
}
