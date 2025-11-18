<?php

use Mindwave\Mindwave\PromptComposer\Tokenizer\TiktokenTokenizer;

it('can count tokens for GPT-4', function () {
    $tokenizer = new TiktokenTokenizer();

    $text = 'Hello, how are you today?';
    $count = $tokenizer->count($text, 'gpt-4');

    expect($count)->toBeGreaterThan(0);
    expect($count)->toBe(7); // Known token count for this phrase
});

it('can count tokens for GPT-3.5', function () {
    $tokenizer = new TiktokenTokenizer();

    $text = 'The quick brown fox jumps over the lazy dog';
    $count = $tokenizer->count($text, 'gpt-3.5-turbo');

    expect($count)->toBeGreaterThan(0);
    expect($count)->toBe(9); // Actual token count
});

it('can encode text into tokens', function () {
    $tokenizer = new TiktokenTokenizer();

    $text = 'Hello world';
    $tokens = $tokenizer->encode($text, 'gpt-4');

    expect($tokens)->toBeArray();
    expect($tokens)->not()->toBeEmpty();
    expect(count($tokens))->toBe($tokenizer->count($text, 'gpt-4'));
});

it('can decode tokens back to text', function () {
    $tokenizer = new TiktokenTokenizer();

    $originalText = 'The quick brown fox';
    $tokens = $tokenizer->encode($originalText, 'gpt-4');
    $decodedText = $tokenizer->decode($tokens, 'gpt-4');

    expect($decodedText)->toBe($originalText);
});

it('returns correct context window for GPT-4', function () {
    $tokenizer = new TiktokenTokenizer();

    $contextWindow = $tokenizer->getContextWindow('gpt-4');

    expect($contextWindow)->toBe(8192);
});

it('returns correct context window for GPT-4 Turbo', function () {
    $tokenizer = new TiktokenTokenizer();

    $contextWindow = $tokenizer->getContextWindow('gpt-4-turbo');

    expect($contextWindow)->toBe(128_000);
});

it('returns correct context window for Claude 3.5', function () {
    $tokenizer = new TiktokenTokenizer();

    $contextWindow = $tokenizer->getContextWindow('claude-3-5-sonnet');

    expect($contextWindow)->toBe(200_000);
});

it('supports OpenAI models', function () {
    $tokenizer = new TiktokenTokenizer();

    expect($tokenizer->supports('gpt-4'))->toBeTrue();
    expect($tokenizer->supports('gpt-3.5-turbo'))->toBeTrue();
});

it('counts tokens consistently', function () {
    $tokenizer = new TiktokenTokenizer();

    $text = 'This is a test sentence with multiple words.';
    $count1 = $tokenizer->count($text, 'gpt-4');
    $count2 = $tokenizer->count($text, 'gpt-4');

    expect($count1)->toBe($count2);
});

it('handles empty strings', function () {
    $tokenizer = new TiktokenTokenizer();

    $count = $tokenizer->count('', 'gpt-4');

    expect($count)->toBe(0);
});

it('handles long text', function () {
    $tokenizer = new TiktokenTokenizer();

    $longText = str_repeat('This is a sentence. ', 1000);
    $count = $tokenizer->count($longText, 'gpt-4');

    expect($count)->toBeGreaterThan(1000);
});

it('handles unicode characters', function () {
    $tokenizer = new TiktokenTokenizer();

    $text = 'Hello 👋 World 🌍';
    $count = $tokenizer->count($text, 'gpt-4');

    expect($count)->toBeGreaterThan(0);
});

it('handles special characters', function () {
    $tokenizer = new TiktokenTokenizer();

    $text = "Special chars: @#$%^&*()_+-=[]{}|;':\",./<>?";
    $count = $tokenizer->count($text, 'gpt-4');

    expect($count)->toBeGreaterThan(0);
});

it('handles newlines and tabs', function () {
    $tokenizer = new TiktokenTokenizer();

    $text = "Line 1\nLine 2\tTabbed";
    $count = $tokenizer->count($text, 'gpt-4');

    expect($count)->toBeGreaterThan(0);
});

it('counts different encodings correctly', function () {
    $tokenizer = new TiktokenTokenizer();

    $text = 'Hello world';

    // GPT-4 uses cl100k_base
    $gpt4Count = $tokenizer->count($text, 'gpt-4');

    // GPT-3.5 also uses cl100k_base
    $gpt35Count = $tokenizer->count($text, 'gpt-3.5-turbo');

    // Should be the same since they use the same encoding
    expect($gpt4Count)->toBe($gpt35Count);
});

it('can handle multiple consecutive calls efficiently', function () {
    $tokenizer = new TiktokenTokenizer();

    $texts = [
        'First text',
        'Second text',
        'Third text',
    ];

    foreach ($texts as $text) {
        $count = $tokenizer->count($text, 'gpt-4');
        expect($count)->toBeGreaterThan(0);
    }
});
