<?php

namespace Mindwave\Mindwave\Prompts;

use Closure;
use Illuminate\Support\Str;
use Mindwave\Mindwave\Contracts\OutputParser;
use Mindwave\Mindwave\Prompts\OutputParsers\TextOutputParser;

class PromptTemplate
{
    protected string $template;

    protected OutputParser $outputParser;

    protected ?Closure $placeholderFormatter = null;

    public function __construct(string $template, OutputParser $outputParser = null)
    {
        $this->template = $template;
        $this->outputParser = $outputParser ?? new TextOutputParser();
    }

    public static function create(string $template, OutputParser $outputParser = null): self
    {
        return new self($template, $outputParser);
    }

    public static function fromPath(string $filepath, OutputParser $outputParser = null): self
    {
        return new self(file_get_contents($filepath), $outputParser);
    }

    public function formatPlaceholder($variable): string
    {
        if ($this->placeholderFormatter) {
            return call_user_func($this->placeholderFormatter, $variable);
        }

        return '{'.$variable.'}';
    }

    public function format(array $inputVariables = []): string
    {
        $formattedVariables = [];
        foreach ($inputVariables as $key => $value) {
            $formattedVariables[$this->formatPlaceholder($key)] = $value;
        }

        return Str::of($this->template)
            ->swap($formattedVariables)
            ->append($this->outputParser->getFormatInstructions())
            ->trim()
            ->toString();
    }

    public function withOutputParser(OutputParser $outputParser): self
    {
        $this->outputParser = $outputParser;

        return $this;
    }

    public function withPlaceholderFormatter(Closure $formatter): self
    {
        $this->placeholderFormatter = $formatter;

        return $this;
    }

    public function parse(string $response): mixed
    {
        return $this->outputParser->parse($response);
    }
}
