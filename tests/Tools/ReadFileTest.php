<?php

use Mindwave\Mindwave\Contracts\Tool;
use Mindwave\Mindwave\Tools\ReadFile;

describe('ReadFile Tool', function () {
    describe('Tool Interface', function () {
        it('implements Tool interface', function () {
            $tool = new ReadFile;

            expect($tool)->toBeInstanceOf(Tool::class);
        });

        it('returns tool name', function () {
            $tool = new ReadFile;

            expect($tool->name())->toBe('File reader');
        });

        it('returns tool description', function () {
            $tool = new ReadFile;

            expect($tool->description())->toContain('Reads a file');
        });
    });

    describe('run()', function () {
        it('reads file contents successfully', function () {
            $tempFile = tempnam(sys_get_temp_dir(), 'mindwave_test_');
            file_put_contents($tempFile, 'Hello, World!');

            try {
                $tool = new ReadFile;
                $result = $tool->run($tempFile);

                expect($result)->toContain('File contents:');
                expect($result)->toContain('Hello, World!');
            } finally {
                unlink($tempFile);
            }
        });

        it('returns error message for non-existent file', function () {
            $tool = new ReadFile;
            $result = $tool->run('/non/existent/path/file.txt');

            expect($result)->toContain('There is no file');
            expect($result)->toContain('/non/existent/path/file.txt');
        });

        it('prefixes content with "File contents:"', function () {
            $tempFile = tempnam(sys_get_temp_dir(), 'mindwave_test_');
            file_put_contents($tempFile, 'test content');

            try {
                $tool = new ReadFile;
                $result = $tool->run($tempFile);

                expect($result)->toStartWith('File contents:');
            } finally {
                unlink($tempFile);
            }
        });

        it('handles empty files', function () {
            $tempFile = tempnam(sys_get_temp_dir(), 'mindwave_test_');
            file_put_contents($tempFile, '');

            try {
                $tool = new ReadFile;
                $result = $tool->run($tempFile);

                expect($result)->toBe('File contents: ');
            } finally {
                unlink($tempFile);
            }
        });

        it('handles multiline files', function () {
            $tempFile = tempnam(sys_get_temp_dir(), 'mindwave_test_');
            $content = "Line 1\nLine 2\nLine 3";
            file_put_contents($tempFile, $content);

            try {
                $tool = new ReadFile;
                $result = $tool->run($tempFile);

                expect($result)->toContain($content);
            } finally {
                unlink($tempFile);
            }
        });
    });
});
