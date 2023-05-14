<?php

namespace Mindwave\Mindwave\Brain;

use Mindwave\Mindwave\Contracts\Embeddings;
use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Knowledge\Data\Knowledge;

class Brain
{
    protected Vectorstore $vectorstore;

    protected Embeddings $embeddings;

    public function __construct(Vectorstore $vectorstore, Embeddings $embeddings)
    {
        $this->vectorstore = $vectorstore;
        $this->embeddings = $embeddings;
    }

    public function consume(Knowledge $knowledge): self
    {
        return $this;
    }
}
