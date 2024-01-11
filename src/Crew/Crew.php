<?php

namespace Mindwave\Mindwave\Crew;

use Exception;

class Crew
{
    protected string $id;

    /**
     * @param  Task[]  $tasks
     * @param  Agent[]  $agents
     */
    public function __construct(
        protected array $tasks,
        protected array $agents,
        protected Process $process = Process::Sequential,
        protected bool $verbose = false,
    ) {
        $this->id = uniqid(); // Generate a unique ID
    }

    public function kickoff(): string
    {
        return match ($this->process) {
            Process::Sequential => $this->executeTasksSequentially(),
            default => throw new Exception('Invalid process type'),
        };
    }

    protected function executeTasksSequentially(): string
    {
        // Executes tasks sequentially
        $taskOutput = null;
        foreach ($this->tasks as $task) {
            // Prepare and execute each task
            $taskOutput = $task->execute($taskOutput);
            // Logging as per verbosity level
        }

        return $taskOutput;
    }
}
