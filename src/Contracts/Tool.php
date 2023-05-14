<?php

namespace Mindwave\Mindwave\Contracts;

interface Tool
{
    public function name(): string;

    public function description(): string;

    public function run($input): string;
}
