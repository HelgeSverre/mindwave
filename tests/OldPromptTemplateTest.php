<?php

use Mindwave\Mindwave\Prompts\OldPromptTemplate;

it('It can perform variable replacements', function () {
    $template = new OldPromptTemplate('This is a [REPLACE]');
    expect($template->format(['[REPLACE]' => 'test']))->toEqual('This is a test');
});

it('It can perform multiple variable replacements', function () {
    $template = new OldPromptTemplate('This is a [REPLACE] [ME]');
    expect($template->format([
        '[REPLACE]' => 'simple',
        '[ME]' => 'test',
    ]))->toEqual('This is a simple test');
});
