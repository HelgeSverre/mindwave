<?php

use Mindwave\Mindwave\Facades\LLM;
use Mindwave\Mindwave\LLM\FunctionCalling\Attributes\Description;
use Mindwave\Mindwave\LLM\FunctionCalling\FunctionBuilder;
use Mindwave\Mindwave\LLM\FunctionCalling\PendingFunction;
use OpenAI\Responses\Chat\CreateResponse;
use OpenAI\Testing\ClientFake;

it('Can convert an anonymous function to a Function object', function () {

    $result = PendingFunction::makeFromClosure(
        name: 'get_current_weather',
        closure: fn (
            #[Description('The location to get the weather from')]
            string $location,
            #[Description('The unit to return the temperature in')]
            string $unit,
            #[Description('The conversion factor to use')]
            float $conversionFactor = 1.0,
            #[Description('for testing')]
            int $someInteger = 66,
            #[Description('Should we include participation information with the weather report?')]
            bool $includeParticipation = false,

        ) => "$location $unit")
        ->build();

    $expectedParameters = [
        'type' => 'object',
        'properties' => [
            'location' => [
                'type' => 'string',
                'description' => 'The location to get the weather from',
            ],
            'unit' => [
                'type' => 'string',
                'description' => 'The unit to return the temperature in',
            ],
            'conversionFactor' => [
                'type' => 'number',
                'description' => 'The conversion factor to use',
            ],
            'someInteger' => [
                'type' => 'integer',
                'description' => 'for testing',
            ],
            'includeParticipation' => [
                'type' => 'bool',
                'description' => 'Should we include participation information with the weather report?',
            ],
        ],
        'required' => ['location', 'unit'],
    ];

    expect($result['function']['parameters'])->toEqual($expectedParameters);
});

it('can generate a schema from a closure', closure: function () {
    $function = function (string $location, #[Description('Unit of measurement')] string $unit) {
        return "$location $unit";
    };

    $schema = FunctionBuilder::make()
        ->addFunction('get_current_weather', 'Gets the current weather')
        ->fromClosure($function)
        ->build();

    // Adjusting the expected schema
    $expectedSchema = [
        'type' => 'function',
        'function' => [

            'name' => 'get_current_weather',
            'description' => 'Gets the current weather',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'location' => [
                        'type' => 'string',
                        'description' => '',
                    ],
                    'unit' => [
                        'type' => 'string',
                        'description' => 'Unit of measurement',
                    ],
                ],
                'required' => ['location', 'unit'],
            ], ],
    ];

    expect($schema)->toEqual($expectedSchema);
});

it('Builds correct schema for get_current_weather function', function () {

    $builder = FunctionBuilder::make();

    $builder
        ->addFunction('get_current_weather')
        ->setDescription('Get the current weather')
        ->addParameter('location', 'string', 'The city and state, e.g. San Francisco, CA', true)
        ->addParameter('unit', 'string', 'The temperature unit to use. Infer this from the user\'s location.', true, ['celsius', 'fahrenheit']);

    $schema = $builder->build();

    expect($schema)->toBeArray()->and($schema)->toHaveCount(1);
    expect($schema[0]['function']['name'])->toEqual('get_current_weather');
    expect($schema[0]['function']['description'])->toEqual('Get the current weather');
    expect($schema[0]['function']['parameters']['type'])->toEqual('object');
    expect($schema[0]['function']['parameters']['properties']['location']['type'])->toEqual('string');
    expect($schema[0]['function']['parameters']['properties']['location']['description'])->toEqual('The city and state, e.g. San Francisco, CA');
    expect($schema[0]['function']['parameters']['properties']['unit']['type'])->toEqual('string');
    expect($schema[0]['function']['parameters']['properties']['unit']['enum'])->toEqual(['celsius', 'fahrenheit']);
    expect($schema[0]['function']['parameters']['properties']['unit']['description'])->toEqual('The temperature unit to use. Infer this from the user\'s location.');
    expect($schema[0]['function']['parameters']['required'])->toEqual(['location', 'unit']);

});

it('can generate the correct schema for functions', function () {
    $builder = FunctionBuilder::make();

    // Define the get_current_weather function
    $builder->addFunction('get_current_weather')
        ->setDescription('Get the current weather')
        ->addParameter('location', 'string', 'The city and state, e.g. San Francisco, CA', true)
        ->addParameter('format', 'string', 'The temperature unit to use. Infer this from the users location.', true, ['celsius', 'fahrenheit']);

    // Define the get_n_day_weather_forecast function
    $builder->addFunction('get_n_day_weather_forecast')
        ->setDescription('Get an N-day weather forecast')
        ->addParameter('location', 'string', 'The city and state, e.g. San Francisco, CA', true)
        ->addParameter('format', 'string', 'The temperature unit to use. Infer this from the users location.', true, ['celsius', 'fahrenheit'])
        ->addParameter('num_days', 'integer', 'The number of days to forecast', true);

    $expectedSchema = [
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_current_weather',
                'description' => 'Get the current weather',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'location' => [
                            'type' => 'string',
                            'description' => 'The city and state, e.g. San Francisco, CA',
                        ],
                        'format' => [
                            'type' => 'string',
                            'enum' => ['celsius', 'fahrenheit'],
                            'description' => 'The temperature unit to use. Infer this from the users location.',
                        ],
                    ],
                    'required' => ['location', 'format'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_n_day_weather_forecast',
                'description' => 'Get an N-day weather forecast',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'location' => [
                            'type' => 'string',
                            'description' => 'The city and state, e.g. San Francisco, CA',
                        ],
                        'format' => [
                            'type' => 'string',
                            'enum' => ['celsius', 'fahrenheit'],
                            'description' => 'The temperature unit to use. Infer this from the users location.',
                        ],
                        'num_days' => [
                            'type' => 'integer',
                            'description' => 'The number of days to forecast',
                        ],
                    ],
                    'required' => ['location', 'format', 'num_days'],
                ],
            ],
        ],
    ];

    expect($builder->build())->toEqual($expectedSchema);
});

it('Description is empty string by default', function () {

    $builder = FunctionBuilder::make();

    $builder
        ->addFunction('get_current_weather');

    $schema = $builder->build();

    expect($schema)->toBeArray()->and($schema)->toHaveCount(1);
    expect($schema[0]['type'])->toEqual('function');
    expect($schema[0]['function']['name'])->toEqual('get_current_weather');
    expect($schema[0]['function']['description'])->toEqual('');

});

it('can call functions', closure: function () {
    $builder = FunctionBuilder::make();
    $builder->addFunction(
        name: 'get_current_weather',
        description: 'Gets the current weather',
        closure: function (string $location, string $unit) {
            return "$location $unit";
        }
    );

    $builder->addFunction(
        name: 'get_temperature',
        description: 'Gets the latest news headlines',
        closure: function (string $newsOrganization, int $count) {
            return "Latest $count news headlines from $newsOrganization";
        }
    );

    $client = new ClientFake([
        CreateResponse::fake(override: [
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => null,
                        'function_call' => null,
                        'tool_calls' => [
                            [
                                'id' => 'call_fake',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'get_current_weather',
                                    'arguments' => '{"location":"Bergen, Norway","unit":"celcius"}',
                                ],
                            ],
                        ],
                    ],
                ],
                'finish_reason' => 'tool_calls',
            ],
        ]),
    ]);

    $response = LLM::createOpenAIDriver($client)->functionCall('What is the weather in bergen, norway in celcius?', $builder);

    expect($response->name)->toEqual('get_current_weather')
        ->and($response->arguments)->toHaveKeys(['location', 'unit'])
        ->and($response->arguments['location'])->toEqual('Bergen, Norway');

});
