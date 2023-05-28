<?php

use Illuminate\Support\Facades\Config;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Facades\Mindwave;
use Mindwave\Mindwave\Memory\ConversationBufferMemory;
use Mindwave\Mindwave\Tools\SimpleTool;

it('can correctly list the colors from a file', function () {

    Config::set('mindwave-vectorstore.default', 'array');
    Config::set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));
    Config::set('mindwave-llm.llms.openai_chat.api_key', env('MINDWAVE_OPENAI_API_KEY'));

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

    Config::set('mindwave-vectorstore.default', 'array');
    Config::set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));
    Config::set('mindwave-llm.llms.openai_chat.api_key', env('MINDWAVE_OPENAI_API_KEY'));

    $agent = Mindwave::agent(
        memory: ConversationBufferMemory::fromMessages([
            ['role' => 'user', 'content' => 'remember the word "banana"'],
            ['role' => 'ai', 'content' => 'ok'],

            ['role' => 'user', 'content' => 'whats your name?'],
            ['role' => 'ai', 'content' => 'mindwave'],

            ['role' => 'user', 'content' => 'in one word, what is "green"?'],
            ['role' => 'ai', 'content' => 'color'],
        ])
    );

    $finalAnswer = $agent->ask('Which word did i tel you to remember?');

    dump($finalAnswer);

    expect($finalAnswer)->toBeString()->toContain('banana');
});

it('We can use an agent to ask questions about the contents of a text file', function () {

    Config::set('mindwave-vectorstore.default', 'array');
    Config::set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));
    Config::set('mindwave-llm.llms.openai_chat.api_key', env('MINDWAVE_OPENAI_API_KEY'));

    $agent = Mindwave::agent();

    Mindwave::brain()->consume(
        Document::make(
            content: file_get_contents(__DIR__.'/data/samples/secrets.txt'),
            meta: ['name' => 'Secret words'],
        )
    );

    $answer = $agent->ask('What is the first secret word?');

    expect($answer)->toContain('mindwave_123');
});

it('The agent will use an appropriate tool', function () {

    Config::set('mindwave-vectorstore.default', 'array');
    Config::set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));
    Config::set('mindwave-llm.llms.openai_chat.api_key', env('MINDWAVE_OPENAI_API_KEY'));

    $agent = Mindwave::agentWithTools([
        new SimpleTool(
            name: 'Lookup',
            description: 'Use this to lookup information you dont know',
            callback: fn ($input) => "The secret word is 'mindwave_rocks_123'",
        ),
    ]);

    $answer = $agent->ask('Lookup the secret word using the lookup tool');

    expect($answer)->toContain('mindwave_rocks_123');
});
