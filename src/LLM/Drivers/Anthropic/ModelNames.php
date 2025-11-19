<?php

namespace Mindwave\Mindwave\LLM\Drivers\Anthropic;

class ModelNames
{
    const DEFAULT = 'claude-sonnet-4-5-20250929';

    // Claude 4.5 Models (Latest - Recommended)
    const CLAUDE_SONNET_4_5 = 'claude-sonnet-4-5-20250929';
    const CLAUDE_SONNET_4_5_LATEST = 'claude-sonnet-4-5';
    const CLAUDE_HAIKU_4_5 = 'claude-haiku-4-5-20251001';
    const CLAUDE_HAIKU_4_5_LATEST = 'claude-haiku-4-5';

    // Claude 4.1 Models (Latest)
    const CLAUDE_OPUS_4_1 = 'claude-opus-4-1-20250805';
    const CLAUDE_OPUS_4_1_LATEST = 'claude-opus-4-1';

    // Legacy Claude 3.5 Models (Deprecated - use 4.5 instead)
    const CLAUDE_3_5_SONNET = 'claude-3-5-sonnet-20241022';
    const CLAUDE_3_5_HAIKU = 'claude-3-5-haiku-20241022';

    // Legacy Claude 3 Models (Deprecated)
    const CLAUDE_3_OPUS = 'claude-3-opus-20240229';
    const CLAUDE_3_SONNET = 'claude-3-sonnet-20240229';
    const CLAUDE_3_HAIKU = 'claude-3-haiku-20240307';

    // Legacy Claude 2 Models (Deprecated)
    const CLAUDE_2_1 = 'claude-2.1';
    const CLAUDE_2_0 = 'claude-2.0';
    const CLAUDE_INSTANT_1_2 = 'claude-instant-1.2';
}
