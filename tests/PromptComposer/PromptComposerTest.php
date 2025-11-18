<?php

use Mindwave\Mindwave\PromptComposer\PromptComposer;
use Mindwave\Mindwave\PromptComposer\Tokenizer\TiktokenTokenizer;

beforeEach(function () {
    $this->tokenizer = new TiktokenTokenizer;
    $this->composer = new PromptComposer($this->tokenizer);
});

it('can create a prompt composer', function () {
    expect($this->composer)->toBeInstanceOf(PromptComposer::class);
});

it('can add sections', function () {
    $this->composer->section('system', 'You are a helpful assistant');

    expect($this->composer->getSections())->toHaveCount(1);
});

it('can add multiple sections', function () {
    $this->composer
        ->section('system', 'You are helpful')
        ->section('user', 'Hello');

    expect($this->composer->getSections())->toHaveCount(2);
});

it('can set model', function () {
    $this->composer->model('gpt-4');

    expect($this->composer)->toBeInstanceOf(PromptComposer::class);
});

it('can reserve output tokens', function () {
    $this->composer->reserveOutputTokens(500);

    expect($this->composer->getAvailableTokens())->toBeLessThan(
        $this->tokenizer->getContextWindow('gpt-4')
    );
});

it('converts to messages array', function () {
    $this->composer
        ->section('system', 'You are helpful')
        ->section('user', 'Hello');

    $messages = $this->composer->toMessages();

    expect($messages)->toBeArray();
    expect($messages)->toHaveCount(2);
    expect($messages[0])->toHaveKey('role');
    expect($messages[0])->toHaveKey('content');
});

it('converts to plain text', function () {
    $this->composer
        ->section('intro', 'Introduction text')
        ->section('body', 'Body text');

    $text = $this->composer->toText();

    expect($text)->toBeString();
    expect($text)->toContain('Introduction text');
    expect($text)->toContain('Body text');
});

it('automatically fits prompt', function () {
    $this->composer
        ->model('gpt-4')
        ->section('system', 'You are helpful')
        ->section('user', 'Hello')
        ->fit();

    expect($this->composer->isFitted())->toBeTrue();
});

it('calculates token count', function () {
    $this->composer
        ->section('user', 'Hello world');

    $count = $this->composer->getTokenCount();

    expect($count)->toBeGreaterThan(0);
});

it('sorts sections by priority', function () {
    $this->composer
        ->section('low', 'Low priority', priority: 10)
        ->section('high', 'High priority', priority: 100)
        ->section('medium', 'Medium priority', priority: 50);

    $this->composer->fit();
    $messages = $this->composer->toMessages();

    // High priority should be first
    expect($messages[0]['content'])->toBe('High priority');
});

it('can use context convenience method', function () {
    $this->composer->context('Some context data');

    $sections = $this->composer->getSections();

    expect($sections)->toHaveCount(1);
    expect($sections[0]->name)->toBe('context');
    expect($sections[0]->shrinker)->toBe('truncate');
});

it('shrinks sections when over budget', function () {
    $longText = str_repeat('This is a long sentence. ', 1000);

    $this->composer
        ->model('gpt-4')
        ->reserveOutputTokens(7000)
        ->section('content', $longText, priority: 50, shrinker: 'truncate')
        ->fit();

    $text = $this->composer->toText();

    // Should be shorter than original
    expect(strlen($text))->toBeLessThan(strlen($longText));
});

it('preserves non-shrinkable sections', function () {
    $systemMessage = 'You are a helpful assistant.';
    $longText = str_repeat('Context data. ', 1000);

    $this->composer
        ->model('gpt-4')
        ->reserveOutputTokens(7000)
        ->section('system', $systemMessage, priority: 100) // No shrinker
        ->section('context', $longText, priority: 50, shrinker: 'truncate')
        ->fit();

    $text = $this->composer->toText();

    // System message should be intact
    expect($text)->toContain($systemMessage);
});

it('throws exception when non-shrinkable sections exceed budget', function () {
    $veryLongText = str_repeat('This is a very long non-shrinkable text. ', 10000);

    $this->composer
        ->model('gpt-4')
        ->section('content', $veryLongText, priority: 100); // No shrinker

    expect(fn () => $this->composer->fit())
        ->toThrow(\RuntimeException::class);
});

it('distributes tokens among shrinkable sections', function () {
    $text1 = str_repeat('Text one. ', 500);
    $text2 = str_repeat('Text two. ', 500);

    $this->composer
        ->model('gpt-4')
        ->reserveOutputTokens(7000)
        ->section('first', $text1, priority: 50, shrinker: 'truncate')
        ->section('second', $text2, priority: 50, shrinker: 'truncate')
        ->fit();

    $sections = $this->composer->getSections();

    // Both should be shrunk
    foreach ($sections as $section) {
        $content = $section->getContentAsString();
        expect(strlen($content))->toBeLessThan(strlen($text1));
    }
});

it('handles empty sections', function () {
    $this->composer->section('empty', '');

    $text = $this->composer->toText();

    expect($text)->toBe('');
});

it('handles unicode content', function () {
    $this->composer->section('unicode', 'Hello 👋 World 🌍');

    $text = $this->composer->toText();

    expect($text)->toContain('👋');
    expect($text)->toContain('🌍');
});

it('supports compress shrinker', function () {
    $markdownText = "# Header\n\n**Bold text** and *italic* text.\n\n```code block```\n\nNormal text.";

    $this->composer
        ->section('content', $markdownText, shrinker: 'compress')
        ->fit();

    expect($this->composer->isFitted())->toBeTrue();
});

it('automatically fits when converting to messages', function () {
    $this->composer->section('user', 'Hello');

    $messages = $this->composer->toMessages();

    expect($this->composer->isFitted())->toBeTrue();
    expect($messages)->toBeArray();
});

it('automatically fits when converting to text', function () {
    $this->composer->section('content', 'Some content');

    $text = $this->composer->toText();

    expect($this->composer->isFitted())->toBeTrue();
    expect($text)->toBeString();
});

it('handles messages array content', function () {
    $messages = [
        ['role' => 'system', 'content' => 'You are helpful'],
        ['role' => 'user', 'content' => 'Hello'],
    ];

    $this->composer->section('conversation', $messages);

    $result = $this->composer->toMessages();

    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
});

it('calculates available tokens correctly', function () {
    $contextWindow = $this->tokenizer->getContextWindow('gpt-4'); // 8192
    $reserved = 500;

    $this->composer
        ->model('gpt-4')
        ->reserveOutputTokens($reserved);

    $available = $this->composer->getAvailableTokens();

    expect($available)->toBe($contextWindow - $reserved);
});

it('refits when sections change after initial fit', function () {
    $this->composer
        ->section('first', 'First content')
        ->fit();

    expect($this->composer->isFitted())->toBeTrue();

    $this->composer->section('second', 'Second content');

    expect($this->composer->isFitted())->toBeFalse();
});

it('maintains section metadata', function () {
    $this->composer->section(
        'custom',
        'Content',
        metadata: ['source' => 'database', 'id' => 123]
    );

    $sections = $this->composer->getSections();

    expect($sections[0]->metadata)->toBe(['source' => 'database', 'id' => 123]);
});
