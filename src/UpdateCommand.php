<?php

namespace Helpflow\Installer;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Process\Exception\ProcessFailedException;

class UpdateCommand extends Command
{
    protected $path;
    protected $type;
    protected $progressBar;

    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Update to the latest version of Helpflow')
            ->addArgument('path', InputArgument::REQUIRED, 'The absolute path to the root laravel directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select your admin integration type (defaults to Laravel Spark)',
            ['Laravel Spark'],
            0
        );
        $this->type = $helper->ask($input, $output, $question);
        $this->path = rtrim($input->getArgument('path'), '/');
        $this->progressBar = new ProgressBar($output, 2);

        // update helpflow git
        $this->updateRepo($output);

        // composer update
        $this->composerUpdate($output);

        $completeMsg = [
            '<info>Helpflow updated successfully!</info>',
        ];

        // display message about assets

        $output->writeln($completeMsg);
    }

    public function updateRepo($output)
    {
        $output->writeln([
            '<comment>Starting repo update</comment>',
            '<comment>==================</comment>'
        ]);
        $process = new Process('cd ' . $this->path . '/helpflow && git pull origin master');
        $process
            ->setTimeout(null)
            ->run(function ($type, $line) use ($output) {
                $output->write($line);
            });

        $this->progressBar->advance();
        $output->writeln('');
    }

    public function composerUpdate($output)
    {
        if ($this->type === 'Laravel Spark') {
            $adminPackage = 'helpflow/spark-admin';
        }

        $output->writeln([
            '<comment>Running composer update</comment>',
            '<comment>=======================</comment>'
        ]);
        $process = new Process('cd ' . $this->path . ' && composer update helpflow/helpflow ' . $adminPackage);
        $process
            ->setTimeout(null)
            ->run(function ($type, $line) use ($output) {
                $output->write($line);
            });

        $this->progressBar->advance();
        $output->writeln('');
    }
}