<?php

namespace Mindwave\Mindwave\Agents;

use App\Robot\PromptTemplate;
use App\Robot\Tools\Tool;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Mindwave\Mindwave\Memory\ChatMessageHistory;
use OpenAI\Client;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponseMessage;

class Agent
{
    protected Client $client;

    /** @var Collection<Tool> */
    protected Collection $tools;

    protected ChatMessageHistory $messageHistory;

    public function __construct(Client $client, ChatMessageHistory $messageHistory, array $tools = [])
    {
        $this->client = $client;
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

        /** @var Tool $selectedTool */
        $selectedTool = $this->tools->first(fn (Tool $tool) => $tool->name() === $toolName);

        if (! $selectedTool) {
            return 'No tool found with that name';
        }

        return $selectedTool->run($input);

    }

    public function ask($input)
    {
        $this->messageHistory->addUserMessage($input);

        $initialPrompt = PromptTemplate::combine([
            file_get_contents(base_path('app/Robot/Prompts/1_prefix.txt')),
            PromptTemplate::from(base_path('app/Robot/Prompts/2_tools.txt'))->format([
                '[TOOL_DESCRIPTIONS]' => $this->tools->map(fn ($t) => sprintf('> %s: %s', $t->name(), $t->description()))->join("\n"),
                '[TOOL_LIST]' => $this->tools->map(fn (Tool $tool) => $tool->name())->join(', '),
            ]),
            PromptTemplate::from(base_path('app/Robot/Prompts/history.txt'))->format([
                '[HISTORY]' => $this->messageHistory->conversationAsString('Human', 'Turid'),
            ]),
            PromptTemplate::from(base_path('app/Robot/Prompts/3_input.txt'))->format([
                '[INPUT]' => $input,
            ]),
        ]);

        $response = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $initialPrompt,
                ],
            ],
        ]);

        /** @var CreateResponseMessage $message */
        $message = $response->choices[0]?->message;

        if (! $message) {
            dd($response);
        }

        dump($initialPrompt);
        dd($message);

        $parsed = $this->parseActionResponse($message->content);

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

        dd($finalPrompt);
        $response = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $finalPrompt,
                ],
            ],
        ]);

        /** @var CreateResponseMessage $message */
        $message = $response->choices[0]?->message;

        $parsed = $this->parseActionResponse($message->content);

        if ($parsed['action'] === 'Final Answer') {
            $this->messageHistory->addAiMessage($parsed['action_input']);

            return $parsed['action_input'];
        }
    }
}
