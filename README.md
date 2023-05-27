![Mindwave](./art/header.png)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mindwave/mindwave.svg?style=flat-square)](https://packagist.org/packages/mindwave/mindwave)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mindwave/mindwave/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mindwave/mindwave/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/mindwave/mindwave/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/mindwave/mindwave/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mindwave/mindwave.svg?style=flat-square)](https://packagist.org/packages/mindwave/mindwave)

# Mindwave: AI Chatbots, Agents & Document Q&A in Laravel Simplified.

## <span style="color:red;">WARNING: This package is NOT ready to be used yet!</span>

Please follow [@helgesverre](https://twitter.com/helgesverre) for updates, and keep an eye on [TODO.md(/todo.md)] to
track progress.

## Installation

You can install the package via composer:

```bash
composer require mindwave/mindwave
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="mindwave-config"
```

## What is Mindwave?

Mindwave is a Laravel package that lets you easily build AI-powered chatbots, agents, and document question and
answering (Q&A) functionality into your application.

With Mindwave, you can incorporate the power of OpenAI's state-of-the-art language models, Pinecone's vector search
capabilities and your own custom "tools" to create intelligent software applications.

## Example

![Code Example](./art/code.png)

```php
$mindwave = new Mindwave\Mindwave();
// TODO: Implement example
```

```php
// TODO: Remove this old example code, its for "API" reference
<?php

$client = OpenAI::client(config('mindwave.openai.api_key'));


$robot = Mindwave\Mindwave::agent()->make(
    client: $openAI,
    brain: Brain::fromPinecone("api-key")
        ->consume(Knowledge::fromPdf(
            data: File::get("uploads/important-document.pdf"),
            meta: ["name" => "Important document"],
        ))
        ->consume(Knowledge::fromUrl(
            data: "https://docs.langchain.com/docs/",
            meta: ["name" => "Langchain introduction"],
        ))
        ->consume(Knowledge::make("My name is Helge Sverre")),
    messageHistory: ChatMessageHistory::fromSession(),
    tools: [
        new WebSearchTool("google", language: "en"),
        new EloquentQueryTool(table: "articles"),
        new EloquentQueryTool(
            table: "events",
            query: fn(Builder $q) => $q->where("date", ">", now())
        ),
    ]
);

$robot->ask("When was our latest article published?");

$robot->ask("When is the next board meeting scheduled?");
```

## Use Cases

- 💬 **Chatbots**: Building AI-powered chatbots to provide support to customers.
- 🤖 **Agents**: Developing intelligent agents to automate tasks within an application.
- ❓ **Document Q&A**: Creating document question and answering (Q&A) systems to extract insights from text.

## Documentation

[Full documentation can be found here](https://mindwave.no).

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Helge Sverre](https://github.com/helgesverre)
- [Probots.io](https://github.com/probots-io) for the [Pinecone PHP Client](https://github.com/probots-io/pinecone-php)
- [Tim Kleyersburg](https://github.com/timkley) for the [Weaviate PHP Client](https://github.com/timkley/weaviate-php)
- [PGVector team](https://github.com/pgvector/pgvector-php/graphs/contributors) for
  the [PGVector PHP package](https://github.com/pgvector/pgvector-php)
- [Yethee](https://github.com/yethee) for the [Tiktoken PHP Package](https://github.com/yethee/tiktoken-php)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
