<?php

use Mindwave\Mindwave\Facades\DocumentLoader;
use Mindwave\Mindwave\Facades\Mindwave;

it('can correctly list the colors from a file', function () {

    config()->set('mindwave-vectorstore.default', 'array');
    //
    Mindwave::brain()->consumeAll([
        // Tabloid news homepage
        DocumentLoader::fromUrl('https://www.nettavisen.no/'),

        // Phonebook search result
        DocumentLoader::fromUrl('https://www.gulesider.no/helge+sverre+liseth/personer'),

        // House owners association - articles of association
        DocumentLoader::fromUrl('https://fellesbygg.no/vedtekter'),

        // Information about preemption when buying a house
        DocumentLoader::fromUrl('https://www.nbbl.no/for-borettslag-og-sameier/jus/hva-er-forkjopsrett/'),
    ]);

    dump(Mindwave::qa()->answerQuestion("What is helge sverre's phone number?"));
    dump(Mindwave::qa()->answerQuestion('Kort fortalt, hva er forkjøpsrett'));

});
