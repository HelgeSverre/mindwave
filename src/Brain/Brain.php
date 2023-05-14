<?php

namespace Mindwave\Mindwave\Brain;

use Mindwave\Mindwave\Knowledge\Knowledge;

class Brain
{
    public function consume(Knowledge $knowledge): self
    {

        return $this;
    }
}
