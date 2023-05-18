<?php

namespace Mindwave\Mindwave\Vectorstore;

use Illuminate\Support\Manager;

class VectorstoreManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->config->get('mindwave-vectorstore.default');
    }
}
