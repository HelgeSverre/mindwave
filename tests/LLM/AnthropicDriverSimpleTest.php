<?php

use Mindwave\Mindwave\Facades\Mindwave;
use Mindwave\Mindwave\LLM\Drivers\Anthropic\AnthropicDriver;

it('resolves anthropic driver from facade', function () {
    config()->set('mindwave-llm.llms.anthropic.api_key', 'test-key');
    config()->set('mindwave-llm.llms.anthropic.model', 'claude-3-5-sonnet-20241022');
    config()->set('mindwave-llm.llms.anthropic.max_tokens', 4096);
    config()->set('mindwave-llm.llms.anthropic.temperature', 1.0);

    $driver = Mindwave::llm('anthropic');

    expect($driver)->toBeInstanceOf(AnthropicDriver::class);
});
