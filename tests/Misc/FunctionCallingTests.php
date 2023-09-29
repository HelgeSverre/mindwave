<?php

use Mindwave\Mindwave\LLM\Drivers\OpenAI\Functions\FunctionBuilder;

it('Can convert an anonymous function to a Function object', function () {

    /**
     * @param  string  $location The location to get the weather from
     * @param  string  $unit The unit to return the temperature in
     */
    $func = function (string $location, string $unit) {
        return "$location $unit";
    };

    $builder = FunctionBuilder::make();

    $result = $builder
        ->addFunction('get_current_weather')
        ->fromClosure($func)
        ->build();

    expect($builder)->toBeInstanceOf(FunctionBuilder::class)
        ->and($result)->toBeArray();

    $expectedParameters = [
        'role' => 'object',
        'properties' => [
            'location' => [
                'role' => 'string',
                'description' => 'The location to get the weather from',
            ],
            'unit' => [
                'role' => 'string',
                'description' => 'The unit to return the temperature in',
            ],
        ],
        'required' => ['location', 'unit'],
    ];

    expect($result['parameters'])->toEqual($expectedParameters);
});

it('can generate a schema from a closure', closure: function () {
    $function = function (string $location, string $unit) {
        return "$location $unit";
    };

    $schema = FunctionBuilder::make()
        ->addFunction('get_current_weather', 'Gets the current weather')
        ->fromClosure($function)
        ->build();

    // Adjusting the expected schema
    $expectedSchema = [
        'name' => 'get_current_weather',
        'description' => 'Gets the current weather',
        'parameters' => [
            'role' => 'object',
            'properties' => [
                'location' => [
                    'role' => 'string',
                    'description' => '', // todo: Assuming "blank" if not defined, instead of being omitted for now
                ],
                'unit' => [
                    'role' => 'string',
                    'description' => '', // todo Assuming "blank" if not defined, instead of being omitted for now
                ],
            ],
            'required' => ['location', 'unit'],
        ],
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
    expect($schema[0]['name'])->toEqual('get_current_weather');
    expect($schema[0]['description'])->toEqual('Get the current weather');
    expect($schema[0]['parameters']['role'])->toEqual('object');
    expect($schema[0]['parameters']['properties']['location']['role'])->toEqual('string');
    expect($schema[0]['parameters']['properties']['location']['description'])->toEqual('The city and state, e.g. San Francisco, CA');
    expect($schema[0]['parameters']['properties']['unit']['role'])->toEqual('string');
    expect($schema[0]['parameters']['properties']['unit']['enum'])->toEqual(['celsius', 'fahrenheit']);
    expect($schema[0]['parameters']['properties']['unit']['description'])->toEqual('The temperature unit to use. Infer this from the user\'s location.');
    expect($schema[0]['parameters']['required'])->toEqual(['location', 'unit']);

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
            'name' => 'get_current_weather',
            'description' => 'Get the current weather',
            'parameters' => [
                'role' => 'object',
                'properties' => [
                    'location' => [
                        'role' => 'string',
                        'description' => 'The city and state, e.g. San Francisco, CA',
                    ],
                    'format' => [
                        'role' => 'string',
                        'enum' => ['celsius', 'fahrenheit'],
                        'description' => 'The temperature unit to use. Infer this from the users location.',
                    ],
                ],
                'required' => ['location', 'format'],
            ],
        ],
        [
            'name' => 'get_n_day_weather_forecast',
            'description' => 'Get an N-day weather forecast',
            'parameters' => [
                'role' => 'object',
                'properties' => [
                    'location' => [
                        'role' => 'string',
                        'description' => 'The city and state, e.g. San Francisco, CA',
                    ],
                    'format' => [
                        'role' => 'string',
                        'enum' => ['celsius', 'fahrenheit'],
                        'description' => 'The temperature unit to use. Infer this from the users location.',
                    ],
                    'num_days' => [
                        'role' => 'integer',
                        'description' => 'The number of days to forecast',
                    ],
                ],
                'required' => ['location', 'format', 'num_days'],
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
    expect($schema[0]['name'])->toEqual('get_current_weather');
    expect($schema[0]['description'])->toEqual('');

});
