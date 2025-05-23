<?php

namespace Laris\Commands;

use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DocsCommand extends Command
{
    protected static $defaultName = 'laris:docs';

    protected function configure(): void
    {
        $this
        ->setName('laris:docs')
            ->setDescription('Generate automatic API documentation from controller PHPDoc')
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'Path to controllers directory', getcwd() . '/app/Http/Controllers')
            ->addOption('output', null, InputOption::VALUE_OPTIONAL, 'Output file path (HTML or MD)', getcwd() . '/docs/api-docs.md');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $controllersPath = $input->getOption('path');
        $outputFile = $input->getOption('output');

        if (!is_dir($controllersPath)) {
            $io->error("Path $controllersPath does not exist or is not a directory.");
            return Command::FAILURE;
        }

        $io->title('Generating API documentation...');
        $docs = [];

        $files = glob($controllersPath . '/*.php');
        foreach ($files as $file) {
            $className = $this->getClassFullNameFromFile($file);
            if (!$className) {
                continue;
            }

            if (!class_exists($className)) {
                require_once $file;
            }

            $reflection = new ReflectionClass($className);

            // فقط کلاس‌هایی که کنترلر هستند
            if (!str_contains($reflection->getName(), 'Controller')) {
                continue;
            }

            $classDoc = $reflection->getDocComment() ?: '';

            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                if ($method->class !== $reflection->getName()) {
                    continue;
                }

                $methodDoc = $method->getDocComment() ?: '';

                $docs[] = [
                    'controller' => $reflection->getName(),
                    'method' => $method->getName(),
                    'classDoc' => $classDoc,
                    'methodDoc' => $methodDoc,
                ];
            }
        }

        $md = "# API Documentation\n\n";
        foreach ($docs as $doc) {
            $md .= "## Controller: {$doc['controller']}\n";
            $md .= "### Method: {$doc['method']}\n\n";
            $md .= "```\n{$doc['methodDoc']}\n```\n\n";
        }

        file_put_contents($outputFile, $md);
        $io->success("Documentation generated at $outputFile");

        return Command::SUCCESS;
    }

    private function getClassFullNameFromFile(string $file): ?string
    {
        $content = file_get_contents($file);

        if (preg_match('#^namespace\s+(.+?);#sm', $content, $m)) {
            $namespace = $m[1];
        } else {
            $namespace = '';
        }

        if (preg_match('#class\s+(\w+)#sm', $content, $m)) {
            $class = $m[1];
        } else {
            return null;
        }

        return $namespace ? $namespace . '\\' . $class : $class;
    }
}
