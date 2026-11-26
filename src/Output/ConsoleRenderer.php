<?php

declare(strict_types=1);

namespace Yousha\\PhpIniScanner\\Output;

use Symfony\\Component\\Console\\Formatter\\OutputFormatter;
use Symfony\\Component\\Console\\Helper\\Table;
use Symfony\\Component\\Console\\Output\\OutputInterface;

final class ConsoleRenderer
{
    /**
     * Renders scan results in a formatted table.
     *
     * @param array<int, array{setting: string, expected: string, current: string, pass: bool}> $results
     */
    public function render(OutputInterface $output, array $results, string $mode): void
    {
        $safeMode = OutputFormatter::escape($mode);
        $output->writeln(['', "<info>Scan result of php.ini for <comment>{$safeMode}</comment> environment...</info>", '']);

        $table = new Table($output);
        $table->setFooterTitle($safeMode);
        $table->setHeaders(['Configuration', 'Current', 'Expected', 'Status']);

        $passCount = 0;
        foreach ($results as $row) {
            $passed = $row['pass'];
            $status = $passed ? '<info>PASS</info>' : '<error>FAIL</error>';
            if ($passed) {
                $passCount++;
            }

            $table->addRow([
                OutputFormatter::escape($row['setting']),
                OutputFormatter::escape($row['current']),
                OutputFormatter::escape($row['expected']),
                $status,
            ]);
        }

        $table->render();
        $total = count($results);
        $failed = $total - $passCount;
        $output->writeln(['', "Checked: {$total} | Passed: <info>{$passCount}</info> | Failed: <error>{$failed}</error>", '']);
        $output->writeln('<info>Hint: It is recommended to use On/Off values instead of 0/1 or yes/no in your php.ini files.</info>');
    }
}
