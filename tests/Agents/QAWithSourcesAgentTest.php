<?php

use Illuminate\Support\Facades\Config;
use Mindwave\Mindwave\Agents\QAWithSourcesAgent;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Facades\Mindwave;
use Mindwave\Mindwave\Memory\ConversationBufferMemory;

it('can correctly list the colors from a file', function () {

    Config::set('mindwave-vectorstore.default', 'array');
    Config::set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));
    Config::set('mindwave-llm.llms.openai_chat.api_key', env('MINDWAVE_OPENAI_API_KEY'));

    $agent = new QAWithSourcesAgent(
        llm: Mindwave::llm(),
        messageHistory: ConversationBufferMemory::fromMessages([]),
        brain: Mindwave::brain()
            ->consume(Document::make("The secret word is 'banana'"))
            ->consume(Document::make("'Matheus' is the name of a greek horse."))
            ->consume(Document::make('Mindwave is a Laravel package for building AI powered applications'))
            ->consume(Document::make('Mindwave is also the name of a startup working on mind-reading technology')),
    );

    $answer = $agent->ask('What is mindwave?');

    dd($answer);

});
