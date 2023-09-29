<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Mindwave\Mindwave\Facades\Mindwave;
use Mindwave\Mindwave\LLM\Drivers\OpenAI\Model;
use Mindwave\Mindwave\Prompts\OutputParsers\StructuredOutputParser;
use Mindwave\Mindwave\Prompts\PromptTemplate;

it('can use a structured output parser', function () {

    class Person
    {
        public string $name;

        public ?int $age;

        public ?bool $hasBusiness;

        public ?array $interests;

        public ?Collection $tags;
    }

    $result = Mindwave::llm()->generate(PromptTemplate::create(
        'Generate random details about a fictional person',
        new StructuredOutputParser(Person::class)
    ));

    expect($result)->toBeInstanceOf(Person::class);

    dump($result);
});

it('We can parse a small recipe into an object', function () {
    Config::set('mindwave-llm.llms.openai.model', Model::turbo16k);
    Config::set('mindwave-llm.llms.openai.max_tokens', 2600);
    Config::set('mindwave-llm.llms.openai.temperature', 0.2);

    class Recipe
    {
        public string $dishName;

        public ?string $description;

        public ?int $portions;

        public ?array $steps;
    }

    // Source: https://sugarspunrun.com/the-best-pizza-dough-recipe/
    $rawRecipeText = file_get_contents(test_root('/data/samples/pizza-recipe.txt'));

    $template = PromptTemplate::create(
        template: 'Extract details from this recipe: {recipe}',
        outputParser: new StructuredOutputParser(Recipe::class)
    );

    $result = Mindwave::llm()->generate($template, [
        'recipe' => $rawRecipeText,
    ]);

    expect($result)->toBeInstanceOf(Recipe::class);

})->skip('Takes too long to run');
