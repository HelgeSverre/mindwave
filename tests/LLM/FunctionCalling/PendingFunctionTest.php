<?php

declare(strict_types=1);

use Mindwave\Mindwave\LLM\FunctionCalling\Attributes\Description;
use Mindwave\Mindwave\LLM\FunctionCalling\PendingFunction;

describe('PendingFunction', function () {
    describe('Construction', function () {
        it('creates with name only', function () {
            $func = new PendingFunction('test');

            $result = $func->build();
            expect($result['function']['name'])->toBe('test');
            expect($result['function']['description'])->toBe('');
        });

        it('creates with name and description', function () {
            $func = new PendingFunction('test', 'Test description');

            $result = $func->build();
            expect($result['function']['description'])->toBe('Test description');
        });

        it('converts null description to empty string', function () {
            $func = new PendingFunction('test', null);

            $result = $func->build();
            expect($result['function']['description'])->toBe('');
        });
    });

    describe('setDescription()', function () {
        it('sets description', function () {
            $func = new PendingFunction('test');
            $func->setDescription('New description');

            $result = $func->build();
            expect($result['function']['description'])->toBe('New description');
        });

        it('returns self for chaining', function () {
            $func = new PendingFunction('test');
            $returned = $func->setDescription('Description');

            expect($returned)->toBe($func);
        });

        it('overwrites existing description', function () {
            $func = new PendingFunction('test', 'Old description');
            $func->setDescription('New description');

            $result = $func->build();
            expect($result['function']['description'])->toBe('New description');
        });
    });

    describe('addParameter()', function () {
        it('adds string parameter', function () {
            $func = new PendingFunction('test');
            $func->addParameter('name', 'string', 'User name', true);

            $result = $func->build();
            expect($result['function']['parameters']['properties']['name'])->toBe([
                'type' => 'string',
                'description' => 'User name',
            ]);
        });

        it('adds integer parameter', function () {
            $func = new PendingFunction('test');
            $func->addParameter('age', 'integer', 'User age', false);

            $result = $func->build();
            expect($result['function']['parameters']['properties']['age']['type'])->toBe('integer');
        });

        it('adds number parameter', function () {
            $func = new PendingFunction('test');
            $func->addParameter('price', 'number', 'Item price', false);

            $result = $func->build();
            expect($result['function']['parameters']['properties']['price']['type'])->toBe('number');
        });

        it('adds boolean parameter', function () {
            $func = new PendingFunction('test');
            $func->addParameter('enabled', 'boolean', 'Is enabled', false);

            $result = $func->build();
            expect($result['function']['parameters']['properties']['enabled']['type'])->toBe('boolean');
        });

        it('adds array parameter', function () {
            $func = new PendingFunction('test');
            $func->addParameter('items', 'array', 'List of items', false);

            $result = $func->build();
            expect($result['function']['parameters']['properties']['items']['type'])->toBe('array');
        });

        it('adds object parameter', function () {
            $func = new PendingFunction('test');
            $func->addParameter('config', 'object', 'Configuration', false);

            $result = $func->build();
            expect($result['function']['parameters']['properties']['config']['type'])->toBe('object');
        });

        it('marks parameter as required', function () {
            $func = new PendingFunction('test');
            $func->addParameter('required', 'string', 'Required param', true);

            $result = $func->build();
            expect($result['function']['parameters']['required'])->toBe(['required']);
        });

        it('does not mark optional parameter as required', function () {
            $func = new PendingFunction('test');
            $func->addParameter('optional', 'string', 'Optional param', false);

            $result = $func->build();
            expect($result['function']['parameters']['required'])->toBe([]);
        });

        it('defaults to optional when isRequired not specified', function () {
            $func = new PendingFunction('test');
            $func->addParameter('param', 'string', 'Description');

            $result = $func->build();
            expect($result['function']['parameters']['required'])->toBe([]);
        });

        it('adds enum values to parameter', function () {
            $func = new PendingFunction('test');
            $func->addParameter('status', 'string', 'Status', true, ['active', 'inactive']);

            $result = $func->build();
            expect($result['function']['parameters']['properties']['status']['enum'])
                ->toBe(['active', 'inactive']);
        });

        it('omits enum when empty array provided', function () {
            $func = new PendingFunction('test');
            $func->addParameter('type', 'string', 'Type', false, []);

            $result = $func->build();
            expect($result['function']['parameters']['properties']['type'])->not->toHaveKey('enum');
        });

        it('returns self for chaining', function () {
            $func = new PendingFunction('test');
            $returned = $func->addParameter('param', 'string', 'Description');

            expect($returned)->toBe($func);
        });

        it('chains multiple parameters', function () {
            $func = new PendingFunction('test');
            $func->addParameter('param1', 'string', 'First', true)
                ->addParameter('param2', 'integer', 'Second', false)
                ->addParameter('param3', 'boolean', 'Third', true);

            $result = $func->build();
            expect($result['function']['parameters']['properties'])->toHaveKeys(['param1', 'param2', 'param3']);
            expect($result['function']['parameters']['required'])->toBe(['param1', 'param3']);
        });
    });

    describe('makeFromClosure()', function () {
        it('creates instance from closure', function () {
            $func = PendingFunction::makeFromClosure('test', function (string $name) {
                return $name;
            });

            expect($func)->toBeInstanceOf(PendingFunction::class);
        });

        it('extracts parameters from closure', function () {
            $func = PendingFunction::makeFromClosure('test', function (string $a, int $b) {
                return "$a $b";
            });

            $result = $func->build();
            expect($result['function']['parameters']['properties'])->toHaveKeys(['a', 'b']);
        });

        it('accepts optional description parameter', function () {
            $func = PendingFunction::makeFromClosure('test', function () {}, 'Test description');

            $result = $func->build();
            expect($result['function']['description'])->toBe('Test description');
        });
    });

    describe('fromClosure()', function () {
        it('extracts string type', function () {
            $func = new PendingFunction('test');
            $func->fromClosure(function (string $param) {});

            $result = $func->build();
            expect($result['function']['parameters']['properties']['param']['type'])->toBe('string');
        });

        it('extracts int type', function () {
            $func = new PendingFunction('test');
            $func->fromClosure(function (int $param) {});

            $result = $func->build();
            expect($result['function']['parameters']['properties']['param']['type'])->toBe('integer');
        });

        it('extracts float type', function () {
            $func = new PendingFunction('test');
            $func->fromClosure(function (float $param) {});

            $result = $func->build();
            expect($result['function']['parameters']['properties']['param']['type'])->toBe('number');
        });

        it('extracts bool type', function () {
            $func = new PendingFunction('test');
            $func->fromClosure(function (bool $param) {});

            $result = $func->build();
            expect($result['function']['parameters']['properties']['param']['type'])->toBe('bool');
        });

        it('extracts array type', function () {
            $func = new PendingFunction('test');
            $func->fromClosure(function (array $param) {});

            $result = $func->build();
            expect($result['function']['parameters']['properties']['param']['type'])->toBe('array');
        });

        it('handles untyped parameter as mixed', function () {
            $func = new PendingFunction('test');
            $func->fromClosure(function ($param) {});

            $result = $func->build();
            expect($result['function']['parameters']['properties']['param']['type'])->toBe('mixed');
        });

        it('marks parameters without defaults as required', function () {
            $func = new PendingFunction('test');
            $func->fromClosure(function (string $required, string $optional = 'default') {});

            $result = $func->build();
            expect($result['function']['parameters']['required'])->toBe(['required']);
        });

        it('handles all parameters with defaults', function () {
            $func = new PendingFunction('test');
            $func->fromClosure(function (string $a = 'x', int $b = 1) {});

            $result = $func->build();
            expect($result['function']['parameters']['required'])->toBe([]);
        });

        it('handles all parameters without defaults', function () {
            $func = new PendingFunction('test');
            $func->fromClosure(function (string $a, int $b, bool $c) {});

            $result = $func->build();
            expect($result['function']['parameters']['required'])->toBe(['a', 'b', 'c']);
        });

        it('extracts description from Description attribute', function () {
            $func = new PendingFunction('test');
            $func->fromClosure(function (
                #[Description('The username')]
                string $username
            ) {});

            $result = $func->build();
            expect($result['function']['parameters']['properties']['username']['description'])
                ->toBe('The username');
        });

        it('handles empty description when no attribute', function () {
            $func = new PendingFunction('test');
            $func->fromClosure(function (string $query) {});

            $result = $func->build();
            $description = $result['function']['parameters']['properties']['query']['description'];
            expect($description)->toBe('');
        });

        it('uses Description attribute when provided', function () {
            $func = new PendingFunction('test');
            $func->fromClosure(function (
                #[Description('Attribute description')]
                string $name
            ) {});

            $result = $func->build();
            expect($result['function']['parameters']['properties']['name']['description'])
                ->toBe('Attribute description');
        });

        it('returns self for chaining', function () {
            $func = new PendingFunction('test');
            $returned = $func->fromClosure(function () {});

            expect($returned)->toBe($func);
        });

        it('handles closure with no parameters', function () {
            $func = new PendingFunction('test');
            $func->fromClosure(function () {
                return 'no params';
            });

            $result = $func->build();
            expect($result['function']['parameters']['properties'])->toBe([]);
            expect($result['function']['parameters']['required'])->toBe([]);
        });
    });

    describe('getTypeFromParameter()', function () {
        it('converts int to integer', function () {
            $reflection = new ReflectionFunction(function (int $x) {});
            $param = $reflection->getParameters()[0];

            $type = PendingFunction::getTypeFromParameter($param);

            expect($type)->toBe('integer');
        });

        it('converts float to number', function () {
            $reflection = new ReflectionFunction(function (float $x) {});
            $param = $reflection->getParameters()[0];

            $type = PendingFunction::getTypeFromParameter($param);

            expect($type)->toBe('number');
        });

        it('keeps string as string', function () {
            $reflection = new ReflectionFunction(function (string $x) {});
            $param = $reflection->getParameters()[0];

            $type = PendingFunction::getTypeFromParameter($param);

            expect($type)->toBe('string');
        });

        it('keeps bool as bool', function () {
            $reflection = new ReflectionFunction(function (bool $x) {});
            $param = $reflection->getParameters()[0];

            $type = PendingFunction::getTypeFromParameter($param);

            expect($type)->toBe('bool');
        });

        it('keeps array as array', function () {
            $reflection = new ReflectionFunction(function (array $x) {});
            $param = $reflection->getParameters()[0];

            $type = PendingFunction::getTypeFromParameter($param);

            expect($type)->toBe('array');
        });

        it('returns mixed for untyped parameter', function () {
            $reflection = new ReflectionFunction(function ($x) {});
            $param = $reflection->getParameters()[0];

            $type = PendingFunction::getTypeFromParameter($param);

            expect($type)->toBe('mixed');
        });
    });

    describe('build()', function () {
        it('returns correct structure', function () {
            $func = new PendingFunction('test_func', 'Test description');
            $func->addParameter('param', 'string', 'Param desc', true);

            $result = $func->build();

            expect($result)->toHaveKeys(['type', 'function']);
            expect($result['type'])->toBe('function');
            expect($result['function'])->toHaveKeys(['name', 'description', 'parameters']);
        });

        it('includes parameters object', function () {
            $func = new PendingFunction('test');

            $result = $func->build();
            $params = $result['function']['parameters'];

            expect($params)->toHaveKeys(['type', 'properties', 'required']);
            expect($params['type'])->toBe('object');
        });

        it('handles function with no parameters', function () {
            $func = new PendingFunction('ping', 'Ping service');

            $result = $func->build();

            expect($result['function']['parameters']['properties'])->toBe([]);
            expect($result['function']['parameters']['required'])->toBe([]);
        });

        it('includes all added parameters', function () {
            $func = new PendingFunction('test');
            $func->addParameter('a', 'string', 'A', true);
            $func->addParameter('b', 'integer', 'B', false);
            $func->addParameter('c', 'boolean', 'C', true);

            $result = $func->build();

            expect($result['function']['parameters']['properties'])->toHaveCount(3);
            expect($result['function']['parameters']['required'])->toBe(['a', 'c']);
        });
    });

    describe('Edge Cases', function () {
        it('handles empty parameter name', function () {
            $func = new PendingFunction('test');
            $func->addParameter('', 'string', 'Empty name', false);

            $result = $func->build();
            expect($result['function']['parameters']['properties'])->toHaveKey('');
        });

        it('handles special characters in descriptions', function () {
            $func = new PendingFunction('test', 'Description with "quotes" and \'apostrophes\'');
            $func->addParameter('param', 'string', 'Param with <html> & special chars', true);

            $result = $func->build();
            expect($result['function']['description'])->toContain('"quotes"');
            expect($result['function']['parameters']['properties']['param']['description'])
                ->toContain('<html>');
        });

        it('handles unicode characters', function () {
            $func = new PendingFunction('test', '日本語の説明');
            $func->addParameter('param', 'string', 'パラメータの説明', true);

            $result = $func->build();
            expect($result['function']['description'])->toBe('日本語の説明');
            expect($result['function']['parameters']['properties']['param']['description'])
                ->toBe('パラメータの説明');
        });

        it('handles numeric enum values', function () {
            $func = new PendingFunction('test');
            $func->addParameter('code', 'integer', 'Status code', true, [200, 404, 500]);

            $result = $func->build();
            expect($result['function']['parameters']['properties']['code']['enum'])
                ->toBe([200, 404, 500]);
        });

        it('handles very long descriptions', function () {
            $longDesc = str_repeat('This is a very long description. ', 100);
            $func = new PendingFunction('test', $longDesc);

            $result = $func->build();
            expect($result['function']['description'])->toBe($longDesc);
        });

        it('handles many parameters', function () {
            $func = new PendingFunction('test');
            for ($i = 1; $i <= 50; $i++) {
                $func->addParameter("param$i", 'string', "Param $i", $i % 2 === 0);
            }

            $result = $func->build();
            expect($result['function']['parameters']['properties'])->toHaveCount(50);
            expect($result['function']['parameters']['required'])->toHaveCount(25);
        });
    });

    describe('Complex Closure Scenarios', function () {
        it('handles mixed parameter types', function () {
            $func = new PendingFunction('test');
            $func->fromClosure(function (
                string $name,
                int $age,
                float $height,
                bool $active,
                array $tags,
                $mixed
            ) {});

            $result = $func->build();
            $props = $result['function']['parameters']['properties'];

            expect($props['name']['type'])->toBe('string');
            expect($props['age']['type'])->toBe('integer');
            expect($props['height']['type'])->toBe('number');
            expect($props['active']['type'])->toBe('bool');
            expect($props['tags']['type'])->toBe('array');
            expect($props['mixed']['type'])->toBe('mixed');
        });

        it('handles nullable parameters', function () {
            $func = new PendingFunction('test');
            $func->fromClosure(function (?string $optional = null) {});

            $result = $func->build();
            // Nullable with default should not be required
            expect($result['function']['parameters']['required'])->toBe([]);
        });

        it('handles class type hints as default type name', function () {
            $func = new PendingFunction('test');
            $func->fromClosure(function (DateTime $date) {});

            $result = $func->build();
            expect($result['function']['parameters']['properties']['date']['type'])
                ->toBe('DateTime');
        });
    });
});
