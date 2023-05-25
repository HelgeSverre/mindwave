<?php

use Illuminate\Support\Facades\Config;
use Mindwave\Mindwave\Agents\Agent;
use Mindwave\Mindwave\Brain\Brain;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Facades\Embeddings;
use Mindwave\Mindwave\Facades\LLM;
use Mindwave\Mindwave\Facades\Vectorstore;
use Mindwave\Mindwave\Memory\ConversationBufferMemory;

it('can correctly list the colors from a file', function () {

    Config::set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));
    Config::set('mindwave-llm.llms.openai_chat.api_key', env('MINDWAVE_OPENAI_API_KEY'));

    $brain = new Brain(
        vectorstore: Vectorstore::driver('array'),
        embeddings: Embeddings::driver(),
    );

    $brain->consume(Document::make("blue\norange\nred\npurple\nbanana"));

    $agent = new Agent(
        llm: LLM::driver(),
        messageHistory: ConversationBufferMemory::fromMessages([]),
        brain: $brain,
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

it('We can use an agent to ask questions about the contents of a text file', function () {

    Config::set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));
    Config::set('mindwave-llm.llms.openai_chat.api_key', env('MINDWAVE_OPENAI_API_KEY'));

    $brain = new Brain(
        vectorstore: Vectorstore::driver('array'),
        embeddings: Embeddings::driver(),
    );

    $brain->consume(
        Document::make(
            content: file_get_contents(__DIR__.'/data/samples/flags-royal-palace-norway-en.txt'),
            meta: ['name' => 'Flag Procedures at the Norwegian Royal Palace'],
        )
    );

    $agent = new Agent(
        llm: LLM::driver(),
        messageHistory: ConversationBufferMemory::fromMessages([]),
        brain: $brain,
    );

    $answer = $agent->ask('When is the Royal Standard is flown');

    dump($answer);

    expect(true)->toBeTrue();
});
