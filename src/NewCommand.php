<?php

namespace Unite\UnisysInstaller;

use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NewCommand extends Command
{
    const TYPE_API  = 'api';
    const TYPE_FRONTEND   = 'frontend';

    protected $commands = [];

    protected $processes = [];

    protected $pathToComposer;

    protected $projectDir;

    protected $types = [self::TYPE_API, self::TYPE_FRONTEND];

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Create a new UniSys app skeleton.')
            ->addArgument('name', InputArgument::OPTIONAL)
            ->addOption(
                'type',
                null,
                InputOption::VALUE_REQUIRED,
                'Which type of skeleton you want?',
                null)
        ;
    }

    /**
     * Execute the command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->pathToComposer = $this->findComposer();

        $this->projectDir = "\"".$input->getArgument('name') ?: '.'."\"";

        $skeletonType = $this->aksForType($input, $output);

        $output->writeln('<info>Creating UniSys '.$skeletonType.' skeleton ...</info>');

        switch($skeletonType) {
            case self::TYPE_API:
                $this->handleApiInstallation();
                break;
            case self::TYPE_FRONTEND:
                $this->handleFrontendInstallation();
            break;
        }

        $this->prepareProcesses();

        $this->executeProcesses($output);

        $output->writeln('<comment>UniSys skeleton for '.$skeletonType.' was generated. Save Earth!</comment>');
    }

    protected function aksForType(InputInterface $input, OutputInterface $output)
    {
        if($input->getOption('type')) {
            return $input->getOption('type');
        }

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Which type of skeleton you want?',
            $this->types,
            0
        );
        $question->setErrorMessage('Type %s is invalid.');

        return $helper->ask($input, $output, $question);
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        if (file_exists(getcwd().'/composer.phar')) {
            return '"'.PHP_BINARY.'" composer.phar';
        }

        return 'composer';
    }

    protected function handleApiInstallation()
    {
        $this->pushToCommands([
            $this->pathToComposer.' create-project --prefer-dist laravel/laravel '.$this->projectDir.' "5.6.*" ',
            'cd '.$this->projectDir,
            $this->pathToComposer.' require "weareunite/unisys-api"',
            '"'.PHP_BINARY.'" artisan unisys-api:init-env',
            '"'.PHP_BINARY.'" artisan unisys-api:install'
        ]);
    }

    protected function handleFrontendInstallation()
    {
        $this->pushToCommands([
            $this->pathToComposer.' require "weareunite/unisys-frontend"',
            'npm install -g @angular/cli',
            'npm install',
            'ng new '.$this->projectDir,
            'cd '.$this->projectDir,
            'ng serve --open'
        ]);
    }

    protected function pushToCommands($commands)
    {
        if(is_array($commands)) {
            foreach ($commands as $command) {
                array_push($this->commands, $command);
            }
        } else {
            array_push($this->commands, $commands);
        }

        return $this;
    }

    protected function executeProcesses(OutputInterface $output)
    {
        foreach ($this->processes as $process) {
            $process->run(function ($type, $line) use ($output) {
                $output->write($line);
            });
        }
    }

    protected function prepareProcesses()
    {
        $isTty = false;

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $isTty = true;
        }

        foreach ($this->commands as $command) {
            $process = new Process($command, null, null, null, null);

            if($isTty) {
                $process->setTty(true);
            }

            $this->processes[] = $process;
        }
    }
}

