<?php

declare(strict_types=1);

namespace Yousha\PhpIniScanner\Commands;

use Throwable;
use Yousha\PhpIniScanner\Config\DevelopmentRules;
use Yousha\PhpIniScanner\Config\ProductionRules;
use Yousha\PhpIniScanner\Output\ConsoleRenderer;
use Yousha\PhpIniScanner\Scanner\ScannerEngine;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ScanCommand extends Command
{
    private ScannerEngine $scanner;

    private ConsoleRenderer $renderer;

    /** Initializes the scanner and renderer. */
    public function __construct()
    {
        $this->scanner = new ScannerEngine();
        $this->renderer = new ConsoleRenderer();
        parent::__construct('scan');
    }

    /** Configures command options. */
    protected function configure(): void
    {
        $this->setDescription('Scans a php.ini file for development or production standards.')
            ->addOption('development', 'd', InputOption::VALUE_NONE, 'Check against Development standards.')
            ->addOption('production', 'p', InputOption::VALUE_NONE, 'Check against Production standards.')
            ->addOption('ini-path', 'i', InputOption::VALUE_REQUIRED, 'Path to the php.ini file to scan.');
    }

    /** Executes the configuration scan. */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isDev = (bool) $input->getOption('development');
        $isProd = (bool) $input->getOption('production');
        $errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        if ($isDev === $isProd) {
            $errorOutput->writeln('<error>Error: Specify exactly one mode: -d or -p.</error>');
            return Command::FAILURE;
        }

        $phpIniPath = $input->getOption('ini-path');
        if (!$phpIniPath) {
            $phpIniPath = php_ini_loaded_file();
            if ($phpIniPath === false) {
                $errorOutput->writeln('<error>Error: No php.ini is loaded. Specify --ini-path.</error>');
                return Command::FAILURE;
            }

            $output->writeln('<comment>Using loaded PHP configuration: ' . $phpIniPath . '</comment>');
            $additionalFiles = php_ini_scanned_files();
            if ($additionalFiles !== false && trim($additionalFiles) !== '') {
                $errorOutput->writeln('<comment>Warning: Additional INI files are loaded by PHP but are not included in this single-file scan.</comment>');
            }
        }

        if (!is_string($phpIniPath) || !is_file($phpIniPath) || !is_readable($phpIniPath)) {
            $errorOutput->writeln('<error>Error: A readable INI file is required via --ini-path or -i.</error>');
            return Command::FAILURE;
        }

        $rules = $isProd ? new ProductionRules() : new DevelopmentRules();

        try {
            $results = $this->scanner->scanFile($phpIniPath, $rules);
            $this->renderer->render($output, $results, $rules->getName());
        } catch (Throwable $exception) {
            $errorOutput->writeln('<error>Error: ' . $exception->getMessage() . '</error>');
            return Command::FAILURE;
        }

        foreach ($results as $result) {
            if ($result['pass'] === false) {
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
