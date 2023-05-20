<?php

namespace Mindwave\Mindwave\Contracts;

interface Toolkit
{
    public function getTool($name): ?Tool;
}
