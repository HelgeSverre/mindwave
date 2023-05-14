<?php

namespace Mindwave\Mindwave\Contracts;

interface LLM
{
    // TODO(11 May 2023) ~ Helge: make an interface that makes sense

    public function predict(string $prompt): ?string;
}
