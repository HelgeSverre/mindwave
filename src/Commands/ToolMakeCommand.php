<?php

namespace Mindwave\Mindwave\Commands;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'mindwave:tool')]
class ToolMakeCommand extends GeneratorCommand
{
    use CreatesMatchingTest;

    protected $name = 'mindwave:tool';

    protected $description = 'Create a new tool class';

    protected $type = 'Tool';

    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/tool.stub');
    }

    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    protected function buildClass($name)
    {
        $replace = [
            '{{ description }}' => $this->option('description') ?? '',
        ];

        return str_replace(
            array_keys($replace),
            array_values($replace),
            parent::buildClass($name)
        );
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Mindwave\Tools';
    }

    protected function getOptions()
    {
        return [
            ['description', 'd', InputOption::VALUE_OPTIONAL, 'Description of the tool'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the tool already exists'],
        ];
    }
}
