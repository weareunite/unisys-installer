<?php

namespace Unite\UnisysInstaller;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends Command
{

    protected $commands = [];

    protected $processes = [];

    protected $pathToComposer;

    protected $projectDir;

    const UNISYS_PACKAGES = [
        "weareunite/unisys-api",
        "weareunite/unisys-contacts",
        "weareunite/unisys-transactions",
        "weareunite/unisys-expenses",
        "weareunite/unisys-tags"
    ];

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Update local UniSys packages to latest versions.');
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

        $output->writeln('<info>Updating ...</info>');

        $this->pushToCommands([
            $this->pathToComposer.' update ' . implode(' ', self::UNISYS_PACKAGES),
        ]);

        $this->prepareProcesses();

        $this->executeProcesses($output);

        $output->writeln('<comment>UniSys packages was updated</comment>');
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

        $process = new Process(implode(' && ', $this->commands), null, null, null, null);

        if($isTty) {
            $process->setTty(true);
        }

        $this->processes[] = $process;
    }
}