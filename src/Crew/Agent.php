<?php

namespace Mindwave\Mindwave\Crew;

use Mindwave\Mindwave\Agents\Actions\AgentAction;
use Mindwave\Mindwave\Agents\Actions\AgentFinish;
use Mindwave\Mindwave\Contracts\Tool;
use Mindwave\Mindwave\Facades\Mindwave;
use Mindwave\Mindwave\Prompts\PromptTemplate;
use Spatie\Regex\Regex;

class Agent
{
    protected string $id;

    /**
     * @param Tool[] $tools
     */
    public function __construct(
        protected string         $role,
        protected ?string        $goal = null,
        protected ?string        $backstory = null,
        protected readonly array $tools = [],
    )
    {
        $this->id = uniqid();
    }

    protected function buildRolePrompt(): string
    {
        $rolePrompt = "You are {$this->role}.\n";

        if ($this->backstory) {
            $rolePrompt .= "{$this->backstory}\n";
        }

        if ($this->goal) {
            $rolePrompt .= "\nYour personal goal is: {$this->goal}\n";
        }

        return $rolePrompt;
    }

    protected function buildToolExecutionPrompt(array $tools = []): string
    {
        if (empty($tools)) {
            return '';
        }

        $toolNames = collect($tools)->map(fn(Tool $tool) => $tool->name())->join(', ');
        $toolDescriptions = collect($tools)->map(
            fn(Tool $tool) => sprintf('%s: %s', $tool->name(), $tool->description())
        )->join("\n");

        $toolPrompt = PromptTemplate::fromPath(__DIR__ . '/../Prompts/Templates/crew/tool.txt')->format([
            'tools' => $toolDescriptions,
            'tool_names' => $toolNames,
        ]);


        return $toolPrompt;

    }

    public function executeTask(
        string  $task,
        ?string $context = null,
        ?array  $tools = null
    ): string
    {

        // TODO: Context should be inside Task
        if ($context) {
            $task .= "\nThis is the context you are working with: $context";
        }

        $combined = implode("\n", [
            $this->buildRolePrompt(),
            $this->buildToolExecutionPrompt($tools = $tools ?: $this->tools),
            "Begin! This is VERY important to you, your job depends on it!\n",
            "Current Task: {$task}",
        ]);


        $result = $this->planStep($combined);

        dd($result);

        if ($result instanceof AgentAction) {

            /** @var Tool $tool */
            $tool = collect($tools)->first(fn(Tool $tool) => $tool->name() === $result->tool);

            // TODO(20 mai 2023) ~ Helge: Handle tool missing

            $observation = $tool->run($result->toolInput);
            $history[] = [
                'tool' => $result->tool,
                'toolInput' => $result->toolInput,
                'observation' => $observation,
            ];
        }

        if ($result instanceof AgentFinish) {
            return $result->response;
        }

        //
        //
        //yields if action is AgentFinish
        //
        //// Can potentially return multiple actions
        //
        //```
        //For each action
        //    if AgentAction is tool
        //        execute tool
        //        record observation
        //    yield ActionStep with AgentAction and Observation
        //```
        //
        //Task is now "complete" and we loop around to the Crew "for task in tasks" loop

        return $output;
    }

    private function planStep(string $combined): AgentFinish|AgentAction
    {
        $result = Mindwave::llm()->generateText($combined);

        $action = $this->parseToolOutput($result);

        // TODO: Parse result into AgentAction  or AgentFinish

        return $action;
    }

    protected function parseToolOutput($text)
    {

        // Regular expression for parsing 'Action' and 'Action Input'
        $actionRegex = '/Action\s*:\s*(?P<action>.*?)\s*Action\s*Input\s*:\s*(?P<actionInput>.*)/s';
        $actionMatch = Regex::match($actionRegex, $text);

        // Regular expression for parsing 'Final Answer'
        $finalAnswerRegex = '/Final Answer:\s*(?P<finalAnswer>.+)/s';
        $finalAnswerMatch = Regex::match($finalAnswerRegex, $text);

        if ($actionMatch->hasMatch()) {
            return new AgentAction(
                tool: trim($actionMatch->group('action')),
                toolInput: trim($actionMatch->group('actionInput'), ' "')
            );
        }

        if ($finalAnswerMatch->hasMatch()) {
            return new AgentFinish(trim($finalAnswerMatch->group('finalAnswer')));
        }

        // No match found
        return null;
    }

}
