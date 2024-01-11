<?php

namespace Mindwave\Mindwave\Crew;

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Mindwave\Mindwave\Agent;
use Mindwave\Mindwave\Crew;
use Mindwave\Mindwave\Task;
use Mindwave\Mindwave\Tools\SimpleTool;

// Example usage of DuckDuckGoSearch tool
$searchTool = new SimpleTool(
    name: 'Search',
    description: 'Search the web for information',
    callback: function (string $query) {
        // use http laravel client
        $response = Http::get('https://api.duckduckgo.com/', [
            'q' => $query,
            'format' => 'json',
            'pretty' => 1,
        ]);

        return $response->json('AbstractText') ?? $response->json('RelatedTopics.0.Text');
    }
);

// Define agents
$researcher = new Agent(
    role: 'Senior Research Analyst',
    goal: 'Uncover cutting-edge developments in AI and data science',
    backstory: 'Your expertise lies in identifying emerging trends...',
    tools: $searchTool,
    verbose: true,
    allowDelegation: false
);

$writer = new Agent(
    role: 'Tech Content Strategist',
    goal: 'Craft compelling content on tech advancements',
    backstory: 'You are a renowned Content Strategist...',
    tools: null,
    verbose: true,
    allowDelegation: true
);

// Create tasks
$task1 = new Task(
    description: 'Conduct a comprehensive analysis of the latest advancements in AI in 2024...',
    agent: $researcher
);

$task2 = new Task(
    description: 'Using the insights provided, develop an engaging blog post...',
    agent: $writer
);

// Instantiate crew
$crew = new Crew(
    agents: [$researcher, $writer],
    tasks: [$task1, $task2],
    verbose: 2
);

// Execute tasks
$result = $crew->kickoff();

echo "######################\n";
echo $result;
