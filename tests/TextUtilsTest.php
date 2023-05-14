<?php

use Mindwave\Mindwave\Support\TextUtils;

it('normalizes whitespace', function () {
    $input = '   Hello   World!   ';
    $expectedOutput = 'Hello World!';

    $output = TextUtils::normalizeWhitespace($input);

    expect($output)->toBe($expectedOutput);
});

it('removes unwanted elements from HTML', function () {
    $html = '
        <html>
            <head>
                <title>Test Page</title>
                <script>alert("Hello!");</script>
                <style>body { background: red; }</style>
            </head>
            <body>
                <p>This is a test page.</p>
                <script>alert("World!");</script>
            </body>
        </html>
    ';
    $expectedOutput = 'This is a test page.';

    $output = TextUtils::cleanHtml($html);

    expect($output)->toBe($expectedOutput);
});

it('keeps whitespaces in HTML', function () {
    $html = '
        <p>Hello   World!</p>
    ';
    $expectedOutput = 'Hello   World!';

    $output = TextUtils::cleanHtml($html, [], true, false);

    expect($output)->toBe($expectedOutput);
});

it('removes comments from HTML', function () {
    $html = '
        <html>
            <head>
                <title>Test Page</title>
                <!-- This is a comment -->
            </head>
            <body>
                <p>This is a test page.</p>
                <!-- Another comment -->
            </body>
        </html>
    ';
    $expectedOutput = 'Test Page This is a test page.';

    $output = TextUtils::cleanHtml($html);

    expect($output)->toBe($expectedOutput);
});
