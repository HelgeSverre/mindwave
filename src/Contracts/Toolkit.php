<?php

namespace Mindwave\Mindwave\Contracts;

interface Toolkit
{
    /**
     * @return Tool[]
     */
    public function tools(): array;
}
