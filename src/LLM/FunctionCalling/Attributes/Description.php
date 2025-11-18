<?php

namespace Mindwave\Mindwave\LLM\FunctionCalling\Attributes;

use Attribute;

#[Attribute]
class Description
{
    public function __construct(public ?string $description = null) {}
}
