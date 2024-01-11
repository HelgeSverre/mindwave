<?php

use Mindwave\Mindwave\Crew\Agent;
use Mindwave\Mindwave\Crew\Crew;
use Mindwave\Mindwave\Crew\Task;
use Mindwave\Mindwave\Tools\PhonebookSearch;
use Mindwave\Mindwave\Tools\WriteFile;

it('it can run a crew', function () {
    $searchTool = new PhonebookSearch();
    $writeFileTool = new WriteFile(realpath(__DIR__.'/../data/write-file-tool-output.txt'));

    // Define Agents with Specific Role Names and Backstories
    $searcherAgent = new Agent(
        role: 'Phone number finder',
        goal: 'Find contact information using the phonebook lookup tool',
        backstory: 'A veteran in digital research, you have mastered the art of extracting precise contact details from vast databases, making you the go-to expert for phone data mining.',
        tools: [$searchTool],
        verbose: true
    );

    $emailWriterAgent = new Agent(
        role: 'Creative Email Strategist',
        goal: 'Create a tailored outreach email',
        backstory: 'With a blend of creativity and marketing acumen, you specialize in designing outreach emails that not only capture attention but also drive engagement.',
        tools: [$writeFileTool],
        verbose: true
    );

    $emailReviewerAgent = new Agent(
        role: 'Content Quality Supervisor',
        goal: 'Review and provide feedback on the email content',
        backstory: 'With your extensive experience in content editing and quality control, you are adept at fine-tuning email drafts to perfection, ensuring they meet the highest standards of clarity and impact.',
        verbose: true
    );

    // Define Tasks
    $taskFindContact = new Task(
        description: 'Find phone number for a specific person',
        agent: [$searcherAgent]
    );

    $taskComposeEmail = new Task(
        description: 'Compose an outreach email based on website content',
        agent: [$emailWriterAgent]
    );

    $taskReviewEmail = new Task(
        description: 'Review the composed email and provide feedback',
        agent: [$emailReviewerAgent]
    );

    // Instantiate the crew
    $crew = new Crew(
        tasks: [$taskFindContact, $taskComposeEmail, $taskReviewEmail],
        agents: [$searcherAgent, $emailWriterAgent, $emailReviewerAgent],
        verbose: true
    );

    // Execute tasks
    $result = $crew->kickoff();

});
