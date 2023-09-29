<?php

use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Facades\Mindwave;
use Mindwave\Mindwave\Memory\ConversationMemory;
use Mindwave\Mindwave\Message\Role;
use Mindwave\Mindwave\Tools\SimpleTool;

it('can correctly list the colors from a file', function () {
    $agent = Mindwave::agent();
    Mindwave::brain()->consume(
        Document::make("blue\norange\nred\npurple\nbanana")
    );
    $answer = $agent->ask('Give me a list of the colors only.');
    expect($answer)
        ->toBeString()
        ->toContain('blue')
        ->toContain('orange')
        ->toContain('red')
        ->toContain('purple')
        ->not()->toContain('banana');
});

it('can remember previous conversations', function () {
    $agent = Mindwave::agent(
        memory: ConversationMemory::fromMessages([
            ['role' => Role::user->value, 'content' => 'remember the word "banana"'],
            ['role' => Role::ai->value, 'content' => 'ok'],
            ['role' => Role::user->value, 'content' => 'whats your name?'],
            ['role' => Role::ai->value, 'content' => 'mindwave'],
            ['role' => Role::user->value, 'content' => 'in one word, what is "green"?'],
            ['role' => Role::ai->value, 'content' => 'color'],
        ])
    );
    $finalAnswer = $agent->ask('Which word did i tell you to remember?');
    dump($finalAnswer);
    expect($finalAnswer)->toBeString()->toContain('banana');
});

it('We can use an agent to ask questions about the contents of a text file', function () {
    Mindwave::brain()->consume(
        Document::make(
            content: file_get_contents(__DIR__.'/../data/samples/secrets.txt'),
            meta: ['name' => 'Secret words'],
        )
    );
    $answer = Mindwave::agent()->ask('What is the first secret word?');
    expect($answer)->toContain('mindwave_123');
});

it('The agent will use an appropriate tool', function () {
    $agent = Mindwave::agent(
        tools: [
            new SimpleTool(
                name: 'Lookup',
                description: 'Use this to lookup information you dont know',
                callback: fn ($input) => "The secret word is 'mindwave_rocks_123'",
            ),
        ]);
    $answer = $agent->ask('Lookup the secret word using the lookup tool');
    expect($answer)->toContain('mindwave_rocks_123');
});
