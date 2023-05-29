<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Mindwave\Mindwave\Facades\Mindwave;
use Mindwave\Mindwave\Prompts\OutputParsers\StructuredOutputParser;
use Mindwave\Mindwave\Prompts\PromptTemplate;

it('can use a structured output parser', function () {
    Config::set('mindwave-vectorstore.default', 'array');
    Config::set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));
    Config::set('mindwave-llm.llms.openai_chat.api_key', env('MINDWAVE_OPENAI_API_KEY'));

    class Person
    {
        public string $name;

        public ?int $age;

        public ?bool $hasBusiness;

        public ?array $interests;

        public ?Collection $tags;
    }

    $model = Mindwave::llm();
    $parser = new StructuredOutputParser(Person::class);

    $result = $model->run(PromptTemplate::create(
        'Generate random details about a fictional person', $parser
    ));

    expect($result)->toBeInstanceOf(Person::class);

    dump($result);
});

it('We can parse a small recipe into an object', function () {
    Config::set('mindwave-vectorstore.default', 'array');
    Config::set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));
    Config::set('mindwave-llm.llms.openai_chat.api_key', env('MINDWAVE_OPENAI_API_KEY'));
    Config::set('mindwave-llm.llms.openai_chat.max_tokens', 2500);
    Config::set('mindwave-llm.llms.openai_chat.temperature', 0.2);

    class Recipe
    {
        public string $dishName;

        public ?string $description;

        public ?int $portions;

        public ?array $steps;
    }

    // Source: https://sugarspunrun.com/the-best-pizza-dough-recipe/
    $rawRecipeText = file_get_contents(__DIR__.'/data/samples/pizza-recipe.txt');

    $template = PromptTemplate::create(
        template: 'Extract details from this recipe: {recipe}',
        outputParser: new StructuredOutputParser(Recipe::class)
    );

    $result = Mindwave::llm()->run($template, [
        'recipe' => $rawRecipeText,
    ]);

    expect($result)->toBeInstanceOf(Recipe::class);

    dump($result);
});
