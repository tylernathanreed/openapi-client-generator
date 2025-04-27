<?php

namespace Reedware\OpenApi;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Throwable;

class Kernel
{
    public function __construct(
        protected string $basePath
    ) {
    }

    public function handle(InputInterface $input, OutputInterface $output): int
    {
        $app = $this->getApplication();

        try {
            return $app->run($input, $output);
        } catch (Throwable $e) {
            $app->renderThrowable($e, $output);

            return 1;
        }
    }

    protected function version(): string
    {
        $process = Process::fromShellCommandline(
            'git describe --tags --abbrev=0',
            $this->basePath
        );

        $process->run();

        return trim($process->getOutput()) ?: 'unreleased';
    }

    protected function getApplication(): Application
    {
        $app = new Application('OpenApi Client', $this->version());

        foreach ($app->all() as $command) {
            if ($command instanceof ListCommand) {
                $command->setHidden(true);
            }
        }

        $commands = [
            Commands\GenerateCommand::class,
        ];

        foreach ($commands as $command) {
            $instance = new $command();

            $instance->setBasePath($this->basePath);

            $app->add($instance);
        }

        return $app;
    }
}
