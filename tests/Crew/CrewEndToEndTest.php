<?php

use Mindwave\Mindwave\Crew\Agent;
use Mindwave\Mindwave\Crew\Crew;
use Mindwave\Mindwave\Crew\Task;
use Mindwave\Mindwave\Tools\PhonebookSearch;
use Mindwave\Mindwave\Tools\SimpleTool;
use Mindwave\Mindwave\Tools\WriteFile;

it('an agent can read and modify file contents', function () {

    $fileContents = "File contents: The secret word is 'banana'";

    // Fake tool for reading a file
    $fakeReadFileTool = new SimpleTool(
        name: 'Read file',
        description: 'Read file',
        callback: fn ($input) => $fileContents
    );
    // Fake tool for reading a file
    $fakeWriteFileTool = new SimpleTool(
        name: 'Write file',
        description: 'Write text to the file',
        callback: function ($input) use (&$fileContents) {
            $fileContents = $input;

            return 'File saved successfully';
        },
    );

    // Tool for writing to a file
    $writeFileTool = new WriteFile(realpath(__DIR__.'/../data/write-file-tool-output.txt'));

    // Define an Agent with a specific role and goal
    $agent = new Agent(
        role: 'a helpful assistant',
        tools: [$fakeReadFileTool, $writeFileTool],
    );

    dump($agent->executeTask('Read the file, replace "banana" with "apple", and write the result'));

});

it('it can run a crew', function () {
    $searchTool = new PhonebookSearch();
    $writeFileTool = new WriteFile(realpath(__DIR__.'/../data/write-file-tool-output.txt'));

    // Define Agents with Specific Role Names and Backstories
    $searcherAgent = new Agent(
        role: 'Phone number finder',
        goal: 'Find contact information using the phonebook lookup tool',
        backstory: 'A veteran in digital research, you have mastered the art of extracting precise contact details from vast databases, making you the go-to expert for phone data mining.',
        tools: [$searchTool],

    );

    $emailWriterAgent = new Agent(
        role: 'Creative Email Strategist',
        goal: 'Create a tailored outreach email',
        backstory: 'With a blend of creativity and marketing acumen, you specialize in designing outreach emails that not only capture attention but also drive engagement.',
        tools: [$writeFileTool],

    );

    $emailReviewerAgent = new Agent(
        role: 'Content Quality Supervisor',
        goal: 'Review and provide feedback on the email content',
        backstory: 'With your extensive experience in content editing and quality control, you are adept at fine-tuning email drafts to perfection, ensuring they meet the highest standards of clarity and impact.',

    );

    // Define Tasks
    $taskFindContact = new Task(
        description: 'Find phone number for a Helge Sverre Hessevik Liseth in Bergen',
        agent: $searcherAgent
    );

    $taskComposeEmail = new Task(
        description: 'Compose an outreach email based on website content',
        agent: $emailWriterAgent
    );

    $taskReviewEmail = new Task(
        description: 'Review the composed email and provide feedback',
        agent: $emailReviewerAgent
    );

    // Instantiate the crew
    $crew = new Crew(
        tasks: [$taskFindContact, $taskComposeEmail, $taskReviewEmail],
        agents: [$searcherAgent, $emailWriterAgent, $emailReviewerAgent],

    );

    // Execute tasks
    $result = $crew->kickoff();

    dump($result);

});
