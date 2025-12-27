<?php

declare(strict_types=1);

use Mindwave\Mindwave\LLM\FunctionCalling\Attributes\Description;
use Mindwave\Mindwave\LLM\FunctionCalling\FunctionBuilder;
use Mindwave\Mindwave\LLM\FunctionCalling\PendingFunction;

describe('FunctionBuilder', function () {
    describe('Construction', function () {
        it('creates empty builder with make()', function () {
            $builder = FunctionBuilder::make();

            expect($builder)->toBeInstanceOf(FunctionBuilder::class);
            expect($builder->toArray())->toBe([]);
        });

        it('creates builder with constructor', function () {
            $builder = new FunctionBuilder;

            expect($builder)->toBeInstanceOf(FunctionBuilder::class);
        });

        it('creates builder with predefined functions', function () {
            $pending = new PendingFunction('test', 'description');
            $builder = new FunctionBuilder([$pending]);

            expect($builder->toArray())->toHaveCount(1);
        });
    });

    describe('add()', function () {
        it('adds function from closure', function () {
            $builder = FunctionBuilder::make();

            $builder->add('search', function (string $query) {
                return "Searching for: $query";
            });

            $result = $builder->toArray();
            expect($result)->toHaveCount(1);
            expect($result[0]['function']['name'])->toBe('search');
        });

        it('returns self for method chaining', function () {
            $builder = FunctionBuilder::make();

            $result = $builder->add('test', fn () => null);

            expect($result)->toBe($builder);
        });

        it('extracts parameters from closure', function () {
            $builder = FunctionBuilder::make();

            $builder->add('calculate', function (int $a, int $b) {
                return $a + $b;
            });

            $result = $builder->toArray();
            expect($result[0]['function']['parameters']['properties'])->toHaveKeys(['a', 'b']);
        });

        it('marks non-optional parameters as required', function () {
            $builder = FunctionBuilder::make();

            $builder->add('greet', function (string $name, string $greeting = 'Hello') {
                return "$greeting, $name!";
            });

            $result = $builder->toArray();
            expect($result[0]['function']['parameters']['required'])->toBe(['name']);
        });
    });

    describe('addFunction()', function () {
        it('adds function with name only', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('test');

            $result = $builder->toArray();
            expect($result)->toHaveCount(1);
            expect($result[0]['function']['name'])->toBe('test');
        });

        it('adds function with name and description', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('search', 'Search the web for information');

            $result = $builder->toArray();
            expect($result[0]['function']['description'])->toBe('Search the web for information');
        });

        it('adds function with closure', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('add', 'Add two numbers', function (int $a, int $b) {
                return $a + $b;
            });

            $result = $builder->toArray();
            expect($result[0]['function']['parameters']['properties'])->toHaveKeys(['a', 'b']);
        });

        it('returns PendingFunction for further configuration', function () {
            $builder = FunctionBuilder::make();

            $pending = $builder->addFunction('test');

            expect($pending)->toBeInstanceOf(PendingFunction::class);
        });

        it('allows fluent API for parameter definition', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('search', 'Search for content')
                ->addParameter('query', 'string', 'The search query', true)
                ->addParameter('limit', 'integer', 'Maximum results', false);

            $result = $builder->toArray();
            expect($result[0]['function']['parameters']['properties']['query'])->toBe([
                'type' => 'string',
                'description' => 'The search query',
            ]);
            expect($result[0]['function']['parameters']['required'])->toBe(['query']);
        });
    });

    describe('Parameter Types', function () {
        it('handles string parameters', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('test')
                ->addParameter('name', 'string', 'A name', true);

            $result = $builder->toArray();
            expect($result[0]['function']['parameters']['properties']['name']['type'])->toBe('string');
        });

        it('handles integer parameters', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('test')
                ->addParameter('count', 'integer', 'Count', true);

            $result = $builder->toArray();
            expect($result[0]['function']['parameters']['properties']['count']['type'])->toBe('integer');
        });

        it('handles number parameters', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('test')
                ->addParameter('price', 'number', 'Price', true);

            $result = $builder->toArray();
            expect($result[0]['function']['parameters']['properties']['price']['type'])->toBe('number');
        });

        it('handles boolean parameters', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('test')
                ->addParameter('enabled', 'boolean', 'Is enabled', false);

            $result = $builder->toArray();
            expect($result[0]['function']['parameters']['properties']['enabled']['type'])->toBe('boolean');
        });

        it('handles array parameters', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('test')
                ->addParameter('items', 'array', 'List of items', false);

            $result = $builder->toArray();
            expect($result[0]['function']['parameters']['properties']['items']['type'])->toBe('array');
        });

        it('handles object parameters', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('test')
                ->addParameter('config', 'object', 'Configuration object', false);

            $result = $builder->toArray();
            expect($result[0]['function']['parameters']['properties']['config']['type'])->toBe('object');
        });
    });

    describe('Enum Parameters', function () {
        it('adds enum values to parameter', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('test')
                ->addParameter('status', 'string', 'Status', true, ['active', 'inactive', 'pending']);

            $result = $builder->toArray();
            expect($result[0]['function']['parameters']['properties']['status']['enum'])->toBe(['active', 'inactive', 'pending']);
        });

        it('handles empty enum array', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('test')
                ->addParameter('type', 'string', 'Type', true, []);

            $result = $builder->toArray();
            expect($result[0]['function']['parameters']['properties']['type'])->not->toHaveKey('enum');
        });
    });

    describe('Required vs Optional Parameters', function () {
        it('marks required parameters', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('test')
                ->addParameter('required1', 'string', 'Required', true)
                ->addParameter('optional1', 'string', 'Optional', false)
                ->addParameter('required2', 'string', 'Required', true);

            $result = $builder->toArray();
            expect($result[0]['function']['parameters']['required'])->toBe(['required1', 'required2']);
        });

        it('defaults to optional when not specified', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('test')
                ->addParameter('param', 'string', 'Description');

            $result = $builder->toArray();
            expect($result[0]['function']['parameters']['required'])->toBe([]);
        });
    });

    describe('Multiple Functions', function () {
        it('adds multiple functions via add()', function () {
            $builder = FunctionBuilder::make();

            $builder->add('func1', fn (string $a) => $a)
                ->add('func2', fn (int $b) => $b);

            $result = $builder->toArray();
            expect($result)->toHaveCount(2);
            expect($result[0]['function']['name'])->toBe('func1');
            expect($result[1]['function']['name'])->toBe('func2');
        });

        it('adds multiple functions via addFunction()', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('search', 'Search')
                ->addParameter('query', 'string', 'Query', true);

            $builder->addFunction('calculate', 'Calculate')
                ->addParameter('expression', 'string', 'Expression', true);

            $result = $builder->toArray();
            expect($result)->toHaveCount(2);
        });

        it('mixes add() and addFunction()', function () {
            $builder = FunctionBuilder::make();

            $builder->add('closureFunc', fn (string $x) => $x);
            $builder->addFunction('manualFunc')->addParameter('y', 'string', 'Y param', true);

            $result = $builder->toArray();
            expect($result)->toHaveCount(2);
            expect($result[0]['function']['name'])->toBe('closureFunc');
            expect($result[1]['function']['name'])->toBe('manualFunc');
        });
    });

    describe('toArray() Output Format', function () {
        it('uses correct OpenAI function format', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('test_func', 'Test description')
                ->addParameter('param1', 'string', 'Param description', true);

            $result = $builder->toArray();
            expect($result[0])->toHaveKeys(['type', 'function']);
            expect($result[0]['type'])->toBe('function');
            expect($result[0]['function'])->toHaveKeys(['name', 'description', 'parameters']);
        });

        it('includes parameters object with correct structure', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('test')
                ->addParameter('param', 'string', 'Description', true);

            $result = $builder->toArray();
            $params = $result[0]['function']['parameters'];

            expect($params)->toHaveKeys(['type', 'properties', 'required']);
            expect($params['type'])->toBe('object');
            expect($params['properties'])->toBeArray();
            expect($params['required'])->toBeArray();
        });

        it('handles function with no parameters', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('ping', 'Ping the service');

            $result = $builder->toArray();
            expect($result[0]['function']['parameters']['properties'])->toBe([]);
            expect($result[0]['function']['parameters']['required'])->toBe([]);
        });
    });

    describe('build()', function () {
        it('returns same output as toArray()', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('test')
                ->addParameter('param', 'string', 'Test', true);

            expect($builder->build())->toBe($builder->toArray());
        });
    });

    describe('toJson()', function () {
        it('returns valid JSON string', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('test', 'Test function')
                ->addParameter('param', 'string', 'Parameter', true);

            $json = $builder->toJson();

            expect($json)->toBeString();
            expect(json_decode($json, true))->not->toBeNull();
        });

        it('uses pretty print format', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('test');

            $json = $builder->toJson();

            // Pretty printed JSON should contain newlines
            expect($json)->toContain("\n");
        });

        it('matches toArray() content', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('test', 'Test')
                ->addParameter('x', 'integer', 'X value', true);

            $fromArray = $builder->toArray();
            $fromJson = json_decode($builder->toJson(), true);

            expect($fromJson)->toBe($fromArray);
        });
    });

    describe('Closure Reflection', function () {
        it('extracts parameter types from closure', function () {
            $builder = FunctionBuilder::make();

            $builder->add('test', function (string $str, int $num, float $decimal, bool $flag, array $list) {
                // Empty function body
            });

            $result = $builder->toArray();
            $props = $result[0]['function']['parameters']['properties'];

            expect($props['str']['type'])->toBe('string');
            expect($props['num']['type'])->toBe('integer');
            expect($props['decimal']['type'])->toBe('number');
            expect($props['flag']['type'])->toBe('bool');
            expect($props['list']['type'])->toBe('array');
        });

        it('handles mixed type parameters', function () {
            $builder = FunctionBuilder::make();

            $builder->add('test', function ($untyped) {
                return $untyped;
            });

            $result = $builder->toArray();
            expect($result[0]['function']['parameters']['properties']['untyped']['type'])->toBe('mixed');
        });

        it('marks parameters without defaults as required', function () {
            $builder = FunctionBuilder::make();

            $builder->add('test', function (string $required, string $optional = 'default') {
                return "$required $optional";
            });

            $result = $builder->toArray();
            expect($result[0]['function']['parameters']['required'])->toBe(['required']);
        });

        it('extracts description from Description attribute', function () {
            $builder = FunctionBuilder::make();

            $builder->add('test', function (
                #[Description('The user name')]
                string $name
            ) {
                return $name;
            });

            $result = $builder->toArray();
            expect($result[0]['function']['parameters']['properties']['name']['description'])
                ->toBe('The user name');
        });

        it('handles empty description when no attribute provided', function () {
            $builder = FunctionBuilder::make();

            $builder->add('test', function (string $query) {
                return $query;
            });

            $result = $builder->toArray();
            $desc = $result[0]['function']['parameters']['properties']['query']['description'];
            expect($desc)->toBe('');
        });

        it('uses Description attribute when provided', function () {
            $builder = FunctionBuilder::make();

            $builder->add('test', function (
                #[Description('Attribute description')]
                string $name
            ) {
                return $name;
            });

            $result = $builder->toArray();
            expect($result[0]['function']['parameters']['properties']['name']['description'])
                ->toBe('Attribute description');
        });
    });

    describe('Edge Cases', function () {
        it('handles empty function name', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('');

            $result = $builder->toArray();
            expect($result[0]['function']['name'])->toBe('');
        });

        it('handles function with many parameters', function () {
            $builder = FunctionBuilder::make();

            $pending = $builder->addFunction('many_params');
            for ($i = 1; $i <= 20; $i++) {
                $pending->addParameter("param$i", 'string', "Parameter $i", false);
            }

            $result = $builder->toArray();
            expect($result[0]['function']['parameters']['properties'])->toHaveCount(20);
        });

        it('handles special characters in parameter descriptions', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('test')
                ->addParameter('param', 'string', 'Description with "quotes" and \'apostrophes\'', true);

            $result = $builder->toArray();
            expect($result[0]['function']['parameters']['properties']['param']['description'])
                ->toContain('"quotes"');
        });

        it('handles unicode in descriptions', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('test', '日本語の説明')
                ->addParameter('param', 'string', 'パラメータ説明', true);

            $result = $builder->toArray();
            expect($result[0]['function']['description'])->toBe('日本語の説明');
            expect($result[0]['function']['parameters']['properties']['param']['description'])
                ->toBe('パラメータ説明');
        });

        it('handles null description', function () {
            $builder = FunctionBuilder::make();

            $builder->addFunction('test', null);

            $result = $builder->toArray();
            expect($result[0]['function']['description'])->toBe('');
        });
    });

    describe('Implements Arrayable', function () {
        it('implements Arrayable interface', function () {
            $builder = FunctionBuilder::make();

            expect($builder)->toBeInstanceOf(\Illuminate\Contracts\Support\Arrayable::class);
        });
    });
});
