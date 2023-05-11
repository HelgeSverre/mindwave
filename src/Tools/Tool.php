<?php

namespace App\Robot\Tools;

interface Tool
{
    public function name(): string;

    public function description(): string;

    public function run($input): string;
}
