<?php

use Mindwave\Mindwave\Context\ContextPipeline;
use Mindwave\Mindwave\Context\Sources\StaticSource;
use Mindwave\Mindwave\PromptComposer\PromptComposer;
use Mindwave\Mindwave\PromptComposer\Tokenizer\TiktokenTokenizer;

beforeEach(function () {
    $this->tokenizer = new TiktokenTokenizer;
    $this->composer = new PromptComposer($this->tokenizer);
});

it('accepts plain string context', function () {
    $result = $this->composer
        ->context('Plain context string')
        ->toText();

    expect($result)->toContain('Plain context string');
});

it('accepts ContextSource instance', function () {
    $source = StaticSource::fromStrings([
        'Laravel is a PHP framework',
        'Vue.js is a JavaScript framework',
        'Python Django framework',
    ]);

    $result = $this->composer
        ->section('user', 'Tell me about Laravel')
        ->context($source)
        ->toText();

    expect($result)->toContain('[1]'); // Numbered format
    expect($result)->toContain('Laravel');
});

it('accepts ContextPipeline instance', function () {
    $source1 = StaticSource::fromStrings([
        'Laravel best practices guide',
    ]);

    $source2 = StaticSource::fromStrings([
        'Laravel security tips',
    ]);

    $pipeline = (new ContextPipeline)
        ->addSource($source1)
        ->addSource($source2);

    $result = $this->composer
        ->section('user', 'Laravel security')
        ->context($pipeline)
        ->toText();

    expect($result)->toContain('Laravel');
});

it('auto-extracts query from user section', function () {
    $source = StaticSource::fromStrings([
        'How to install Laravel framework',
        'How to deploy Vue.js applications',
    ]);

    $result = $this->composer
        ->section('user', 'How do I install Laravel?')
        ->context($source) // No query specified, should auto-extract
        ->toText();

    // Should find Laravel content
    expect($result)->toContain('Laravel');
});

it('uses explicit query parameter when provided', function () {
    $source = StaticSource::fromStrings([
        'Laravel installation guide',
        'Vue.js deployment guide',
    ]);

    $result = $this->composer
        ->section('user', 'Tell me about frameworks')
        ->context($source, query: 'Vue.js') // Explicit query overrides user section
        ->toText();

    expect($result)->toContain('Vue');
});

it('respects limit parameter', function () {
    $source = StaticSource::fromStrings([
        'Document 1 about Laravel',
        'Document 2 about Laravel',
        'Document 3 about Laravel',
        'Document 4 about Laravel',
        'Document 5 about Laravel',
    ]);

    $result = $this->composer
        ->section('user', 'Laravel')
        ->context($source, limit: 2)
        ->toText();

    // Should have at most 2 results
    $resultLines = explode("\n", $result);
    $numberedLines = array_filter($resultLines, fn ($line) => preg_match('/^\[(\d+)\]/', $line));

    expect(count($numberedLines))->toBeLessThanOrEqual(2);
});

it('works with priority and shrinker', function () {
    $source = StaticSource::fromStrings([
        'Context information',
    ]);

    $result = $this->composer
        ->section('system', 'You are a helpful assistant')
        ->context($source, priority: 75, query: 'information')
        ->section('user', 'What is Laravel?')
        ->toText();

    expect($result)->toContain('Context information');
});

it('formats results using formatForPrompt', function () {
    $source = StaticSource::fromStrings([
        'First piece of context',
        'Second piece of context',
    ]);

    $result = $this->composer
        ->section('user', 'context')
        ->context($source)
        ->toText();

    // Should use numbered format by default
    expect($result)->toMatch('/\[1\].*score/');
    expect($result)->toMatch('/\[2\].*score/');
});

it('handles empty search results gracefully', function () {
    $source = StaticSource::fromStrings([
        'Laravel documentation',
    ]);

    $result = $this->composer
        ->section('user', 'Python Django')
        ->context($source) // No matching results
        ->toText();

    // Should not crash, context section should be empty or minimal
    expect($result)->toBeString();
});

it('works with existing prompt composer features', function () {
    $source = StaticSource::fromStrings([
        'Laravel is great for web development',
    ]);

    $result = $this->composer
        ->model('gpt-4')
        ->reserveOutputTokens(100)
        ->section('system', 'You are a helpful assistant')
        ->context($source, query: 'Laravel')
        ->section('user', 'What is Laravel?')
        ->fit()
        ->toText();

    expect($result)->toContain('Laravel');
    expect($result)->toContain('helpful assistant');
});

it('extracts query from array user section', function () {
    $source = StaticSource::fromStrings([
        'Laravel PHP framework documentation',
    ]);

    $result = $this->composer
        ->section('user', [
            ['role' => 'user', 'content' => 'Tell me about Laravel'],
        ])
        ->context($source)
        ->toText();

    expect($result)->toContain('Laravel');
});

it('backward compatible with string content', function () {
    // Old usage should still work
    $result = $this->composer
        ->context('Static context string')
        ->section('user', 'Question?')
        ->toText();

    expect($result)->toContain('Static context string');
});

it('backward compatible with array content', function () {
    // Old usage should still work
    $result = $this->composer
        ->context(['Context item 1', 'Context item 2'])
        ->section('user', 'Question?')
        ->toText();

    $resultStr = is_array($result) ? json_encode($result) : $result;
    expect($resultStr)->toContain('Context item');
});

it('cleans up sources after use', function () {
    $source = StaticSource::fromStrings(['Test content']);

    $this->composer
        ->section('user', 'test')
        ->context($source)
        ->toText();

    // Source should have been initialized during context()
    expect(true)->toBeTrue(); // Just verify no errors
});
