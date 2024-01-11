<?php

namespace Mindwave\Mindwave\Tools;

use HelgeSverre\Telefonkatalog\Facades\Telefonkatalog;
use Mindwave\Mindwave\Contracts\Tool;

class PhonebookSearch implements Tool
{
    public function name(): string
    {
        return 'Norwegian Phonebook Search';
    }

    public function description(): string
    {
        return 'Searches the Norwegian Phonebook for phone numbers and names of people residing in norway, search by name or phone number';
    }

    public function run($input): string
    {
        return Telefonkatalog::search($input)->toJson();
    }
}
