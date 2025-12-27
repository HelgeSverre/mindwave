<?php

declare(strict_types=1);

use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;

describe('EmbeddingVector', function () {
    describe('Construction', function () {
        it('creates vector from array', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);

            expect($vector->values)->toBe([0.1, 0.2, 0.3]);
        });

        it('creates empty vector', function () {
            $vector = new EmbeddingVector([]);

            expect($vector->values)->toBe([]);
            expect($vector->count())->toBe(0);
        });

        it('preserves float precision', function () {
            $values = [0.123456789, 0.987654321];
            $vector = new EmbeddingVector($values);

            expect($vector[0])->toBe(0.123456789);
            expect($vector[1])->toBe(0.987654321);
        });

        it('handles large vectors', function () {
            $values = array_fill(0, 1536, 0.5);
            $vector = new EmbeddingVector($values);

            expect($vector->count())->toBe(1536);
        });
    });

    describe('Countable', function () {
        it('counts elements', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3, 0.4, 0.5]);

            expect(count($vector))->toBe(5);
            expect($vector->count())->toBe(5);
        });
    });

    describe('ArrayAccess', function () {
        it('checks if offset exists', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);

            expect(isset($vector[0]))->toBeTrue();
            expect(isset($vector[2]))->toBeTrue();
            expect(isset($vector[3]))->toBeFalse();
        });

        it('gets value at offset', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);

            expect($vector[0])->toBe(0.1);
            expect($vector[1])->toBe(0.2);
            expect($vector[2])->toBe(0.3);
        });

        it('returns null for non-existent offset', function () {
            $vector = new EmbeddingVector([0.1, 0.2]);

            expect($vector[99])->toBeNull();
        });

        it('throws on set attempt', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);

            expect(fn () => $vector[0] = 0.5)
                ->toThrow(RuntimeException::class, 'Cannot modify a read-only EmbeddingVector');
        });

        it('throws on unset attempt', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);

            expect(function () use ($vector) {
                unset($vector[0]);
            })->toThrow(RuntimeException::class, 'Cannot modify a read-only EmbeddingVector');
        });
    });

    describe('IteratorAggregate', function () {
        it('iterates over values', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);
            $result = [];

            foreach ($vector as $key => $value) {
                $result[$key] = $value;
            }

            expect($result)->toBe([0.1, 0.2, 0.3]);
        });
    });

    describe('Arrayable', function () {
        it('converts to array via toArray()', function () {
            $values = [0.1, 0.2, 0.3];
            $vector = new EmbeddingVector($values);

            expect($vector->toArray())->toBe($values);
        });

        it('converts to array via __toArray()', function () {
            $values = [0.1, 0.2, 0.3];
            $vector = new EmbeddingVector($values);

            expect($vector->__toArray())->toBe($values);
        });
    });

    describe('Jsonable', function () {
        it('serializes to JSON string', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);

            expect($vector->toJson())->toBe('[0.1,0.2,0.3]');
        });

        it('accepts JSON options', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);

            expect($vector->toJson(JSON_PRETTY_PRINT))->toContain("\n");
        });
    });

    describe('JsonSerializable', function () {
        it('implements jsonSerialize', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);

            expect($vector->jsonSerialize())->toBe('[0.1,0.2,0.3]');
        });

        it('works with json_encode', function () {
            $vector = new EmbeddingVector([0.1, 0.2, 0.3]);

            expect(json_encode($vector))->toBe('"[0.1,0.2,0.3]"');
        });
    });
});
