<?php

use Mindwave\Mindwave\Contracts\Tool;
use Mindwave\Mindwave\Tools\WriteFile;

describe('WriteFile Tool', function () {
    describe('Constructor', function () {
        it('accepts path in constructor', function () {
            $tool = new WriteFile('/tmp/test.txt');

            expect($tool)->toBeInstanceOf(WriteFile::class);
        });
    });

    describe('Tool Interface', function () {
        it('implements Tool interface', function () {
            $tool = new WriteFile('/tmp/test.txt');

            expect($tool)->toBeInstanceOf(Tool::class);
        });

        it('returns tool name', function () {
            $tool = new WriteFile('/tmp/test.txt');

            expect($tool->name())->toBe('Write text to a text file');
        });

        it('returns tool description', function () {
            $tool = new WriteFile('/tmp/test.txt');

            expect($tool->description())->toContain('Writes text to a text file');
            expect($tool->description())->toContain("created if it doesn't exist");
        });
    });

    describe('run()', function () {
        it('writes content to existing file', function () {
            $tempFile = tempnam(sys_get_temp_dir(), 'mindwave_test_');

            try {
                $tool = new WriteFile($tempFile);
                $result = $tool->run('New content');

                expect($result)->toContain('Successfully wrote');
                expect(file_get_contents($tempFile))->toBe('New content');
            } finally {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
        });

        it('creates file if it does not exist', function () {
            $tempDir = sys_get_temp_dir();
            $tempFile = $tempDir.'/mindwave_test_'.uniqid().'.txt';

            try {
                expect(file_exists($tempFile))->toBeFalse();

                $tool = new WriteFile($tempFile);
                $result = $tool->run('Created content');

                expect(file_exists($tempFile))->toBeTrue();
                expect(file_get_contents($tempFile))->toBe('Created content');
            } finally {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
        });

        it('returns success message with path', function () {
            $tempFile = tempnam(sys_get_temp_dir(), 'mindwave_test_');

            try {
                $tool = new WriteFile($tempFile);
                $result = $tool->run('test');

                expect($result)->toContain('Successfully wrote');
                expect($result)->toContain($tempFile);
            } finally {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
        });

        it('overwrites existing content', function () {
            $tempFile = tempnam(sys_get_temp_dir(), 'mindwave_test_');
            file_put_contents($tempFile, 'Original content');

            try {
                $tool = new WriteFile($tempFile);
                $tool->run('Overwritten content');

                expect(file_get_contents($tempFile))->toBe('Overwritten content');
            } finally {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
        });

        it('handles empty content', function () {
            $tempFile = tempnam(sys_get_temp_dir(), 'mindwave_test_');

            try {
                $tool = new WriteFile($tempFile);
                $result = $tool->run('');

                expect($result)->toContain('Successfully wrote');
                expect(file_get_contents($tempFile))->toBe('');
            } finally {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
        });
    });
});
