<?php

namespace Mindwave\Mindwave\Agents;

use Mindwave\Mindwave\Agents\Actions\AgentAction;
use Mindwave\Mindwave\Agents\Actions\AgentFinish;
use Mindwave\Mindwave\Agents\Actions\AgentMaxIterationsReached;
use Mindwave\Mindwave\Contracts\Toolkit;

class AgentExecutor
{
    protected AgentInterface $agent;

    protected Toolkit $toolkit;

    private ?int $maxIterations;

    public function __construct(AgentInterface $agent, Toolkit $toolkit, ?int $maxIterations = null)
    {
        // TODO(20 mai 2023) ~ Helge: Max iterations
        $this->agent = $agent;
        $this->toolkit = $toolkit;
        $this->maxIterations = $maxIterations;
    }

    public function execute($userInput)
    {
        $history = [];
        $response = null;

        $iterations = 0;

        do {
            if ($iterations >= $this->maxIterations) {
                return new AgentMaxIterationsReached("Max iterations of {$this->maxIterations} reached");
            }

            $result = $this->agent->handleUserInput($userInput, $history);

            if ($result instanceof AgentAction) {
                $tool = $this->toolkit->getTool($result->tool);
                // TODO(20 mai 2023) ~ Helge: Handle tool missing

                $observation = $tool->execute($result->toolInput);
                $history[] = [
                    'tool' => $result->tool,
                    'toolInput' => $result->toolInput,
                    'observation' => $observation,
                ];
            }

            if ($result instanceof AgentFinish) {
                return $result->response;
            }

            $iterations++;
        } while (true);
    }
}
