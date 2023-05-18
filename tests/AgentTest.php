<?php

use Mindwave\Mindwave\Facades\Mindwave;

//
//it('Can correctly list the colors from a file', function () {
//
//    $knowledge = Document::fromText(
//        data: "blue\norange\nred\npurple\nbanana"
//    );
//
//    $agent = Mindwave::agent(
//        client: new \Mindwave\Mindwave\LLM\Fake('test response'),
//        brain: Brain::fromArray([])->consume($knowledge),
//    );
//
//    $answer = $agent->ask('list the color names only.');
//
//    expect($answer)
//        ->toContain(['blue', 'orange', 'red', 'purple'])
//        ->not()->toContain('banana');
//});
//
//it('We can use an agent to ask questions about the contents of a text file', function () {
//
//    $brain = Brain::fromArray([])->consume(Document::fromFile(
//        data: __DIR__ . 'data/data/flags-royal-palace-norway-en.txt',
//        meta: ['name' => 'Flag Procedures at the Norwegian Royal Palace'],
//    ));
//
//    $agent = Mindwave::agent(
//        client: new \Mindwave\Mindwave\LLM\Fake('test response'),
//        brain: $brain,
//    );
//
//    $answer = $agent->ask('When is the Royal Standard is flown');
//
//    expect(true)->toBeTrue();
//});
