<?php

namespace Mindwave\Mindwave\Crew;

use Mindwave\Mindwave\Tools\SimpleTool;
use Throwable;

class AgentTools
{
    /**
     * @param  Agent[]  $agents
     */
    public function __construct(protected array $agents)
    {
    }

    protected function coworkersAsStringList(): string
    {
        return implode(', ', array_map(fn ($agent) => $agent->role, $this->agents));
    }

    public function tools(): array
    {
        return [
            new SimpleTool(
                name: 'Delegate work to co-worker',
                description: 'Useful to delegate a specific task to one of the following co-workers: ['.$this->coworkersAsStringList().']. '
                .'The input to this tool should be a pipe (|) separated text of length three, '
                .'representing the co-worker you want to ask it to (one of the options), '
                .'the task and all actual context you have for the task. '
                .'For example, `coworker|task|context`.',
                callback: $this->execute(...),
            ),
            new SimpleTool(
                name: 'Ask question to co-worker',
                description: 'Useful to ask a question, opinion, or take from one of the following co-workers: ['.$this->coworkersAsStringList().'].
                .The input to this tool should be a pipe (|) separated text of length three,
                .representing the co-worker you want to ask it to (one of the options), the question,
                .and all actual context you have for the question.
                .For example, `coworker|question|context`.',
                callback: $this->execute(...),
            ),
        ];
    }

    protected function execute($command)
    {
        try {
            [$agent, $task, $context] = explode('|', $command);
        } catch (Throwable $e) {
            return "\nError executing tool. Missing exactly 3 pipe (|) separated values. For example, `coworker|task|context`. I need to make sure to pass context as context\n";
        }

        if (! $agent || ! $task || ! $context) {
            return "\nError executing tool. Missing exactly 3 pipe (|) separated values. For example, `coworker|task|context`. I need to make sure to pass context as context.\n";
        }

        $agent = array_filter($this->agents, fn ($availableAgent) => $availableAgent->role === $agent);

        if (empty($agent)) {
            return "\nError executing tool. Co-worker mentioned on the Action Input not found, it must be one of the following options: ".$this->coworkersAsStringList().".\n";
        }

        $agent = $agent[0];

        return $agent->executeTask($task, $context);
    }
}
