<?php

use Mindwave\Mindwave\Contracts\Tool;
use Mindwave\Mindwave\Contracts\Toolkit as ToolkitContract;
use Mindwave\Mindwave\Tools\SimpleTool;
use Mindwave\Mindwave\Tools\Toolkit;

describe('Toolkit', function () {
    describe('Constructor', function () {
        it('accepts array of tools', function () {
            $tools = [
                new SimpleTool('tool1', 'desc1', fn ($i) => $i),
                new SimpleTool('tool2', 'desc2', fn ($i) => $i),
            ];

            $toolkit = new Toolkit($tools);

            expect($toolkit)->toBeInstanceOf(Toolkit::class);
        });

        it('accepts Collection of tools', function () {
            $tools = collect([
                new SimpleTool('tool1', 'desc1', fn ($i) => $i),
                new SimpleTool('tool2', 'desc2', fn ($i) => $i),
            ]);

            $toolkit = new Toolkit($tools);

            expect($toolkit)->toBeInstanceOf(Toolkit::class);
        });

        it('converts Collection to array internally', function () {
            $tools = collect([
                new SimpleTool('tool1', 'desc1', fn ($i) => $i),
            ]);

            $toolkit = new Toolkit($tools);
            $result = $toolkit->tools();

            expect($result)->toBeArray();
        });
    });

    describe('Contract', function () {
        it('implements Toolkit contract', function () {
            $toolkit = new Toolkit([]);

            expect($toolkit)->toBeInstanceOf(ToolkitContract::class);
        });
    });

    describe('fromTools()', function () {
        it('creates new instance from tools', function () {
            $existingToolkit = new Toolkit([]);

            $tools = [
                new SimpleTool('new_tool', 'New tool description', fn ($i) => $i),
            ];

            $newToolkit = $existingToolkit->fromTools($tools);

            expect($newToolkit)->toBeInstanceOf(Toolkit::class);
            expect($newToolkit)->not->toBe($existingToolkit);
        });

        it('accepts Collection in fromTools', function () {
            $toolkit = new Toolkit([]);
            $tools = collect([
                new SimpleTool('tool', 'desc', fn ($i) => $i),
            ]);

            $newToolkit = $toolkit->fromTools($tools);

            expect($newToolkit->tools())->toHaveCount(1);
        });
    });

    describe('tools()', function () {
        it('returns array of tools', function () {
            $tools = [
                new SimpleTool('tool1', 'desc1', fn ($i) => $i),
                new SimpleTool('tool2', 'desc2', fn ($i) => $i),
            ];

            $toolkit = new Toolkit($tools);

            expect($toolkit->tools())->toBeArray();
            expect($toolkit->tools())->toHaveCount(2);
        });

        it('returns empty array when no tools', function () {
            $toolkit = new Toolkit([]);

            expect($toolkit->tools())->toBe([]);
        });

        it('preserves tool order', function () {
            $tool1 = new SimpleTool('first', 'First tool', fn ($i) => $i);
            $tool2 = new SimpleTool('second', 'Second tool', fn ($i) => $i);
            $tool3 = new SimpleTool('third', 'Third tool', fn ($i) => $i);

            $toolkit = new Toolkit([$tool1, $tool2, $tool3]);
            $tools = $toolkit->tools();

            expect($tools[0]->name())->toBe('first');
            expect($tools[1]->name())->toBe('second');
            expect($tools[2]->name())->toBe('third');
        });

        it('returns tools that implement Tool interface', function () {
            $tool = new SimpleTool('test', 'desc', fn ($i) => $i);
            $toolkit = new Toolkit([$tool]);

            $tools = $toolkit->tools();

            expect($tools[0])->toBeInstanceOf(Tool::class);
        });
    });

    describe('Edge Cases', function () {
        it('handles single tool', function () {
            $tool = new SimpleTool('single', 'Only tool', fn ($i) => $i);
            $toolkit = new Toolkit([$tool]);

            expect($toolkit->tools())->toHaveCount(1);
        });

        it('handles large number of tools', function () {
            $tools = [];
            for ($i = 0; $i < 50; $i++) {
                $tools[] = new SimpleTool("tool_{$i}", "Tool $i", fn ($input) => $input);
            }

            $toolkit = new Toolkit($tools);

            expect($toolkit->tools())->toHaveCount(50);
        });
    });
});
