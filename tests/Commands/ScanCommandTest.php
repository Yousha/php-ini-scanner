<?php

declare(strict_types=1);

namespace Yousha\PhpIniScanner\Tests\Commands;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Yousha\PhpIniScanner\Commands\ScanCommand;
use Yousha\PhpIniScanner\Config\DevelopmentRules;
use Yousha\PhpIniScanner\Config\ProductionRules;

/** Tests scan command behavior. */
final class ScanCommandTest extends TestCase
{
    private string $tempIni;

    /** Prepares test fixtures. */
    protected function setUp(): void
    {
        $this->tempIni = tempnam(sys_get_temp_dir(), 'test_cli_');
        file_put_contents($this->tempIni, "display_errors = Off\nlog_errors = On");
    }

    /** Cleans up test fixtures. */
    protected function tearDown(): void
    {
        if (is_file($this->tempIni)) {
            unlink($this->tempIni);
        }
    }

    /** Tests command fails without mode. */
    public function testCommandFailsWithoutMode(): void
    {
        // Arrange
        // Act
        [$statusCode, $content] = $this->runCommand(['command' => 'scan', '-i' => $this->tempIni]);

        // Assert
        self::assertSame(1, $statusCode);
        self::assertStringContainsString('Specify exactly one mode', $content);
    }

    /** Tests command rejects both modes. */
    public function testCommandRejectsBothModes(): void
    {
        // Arrange
        // Act
        [$statusCode, $content] = $this->runCommand([
            'command' => 'scan',
            '-d' => true,
            '-p' => true,
            '-i' => $this->tempIni,
        ]);

        // Assert
        self::assertSame(1, $statusCode);
        self::assertStringContainsString('Specify exactly one mode', $content);
    }

    /** Tests command returns failure when rules do not pass. */
    public function testCommandReturnsFailureWhenRulesDoNotPass(): void
    {
        // Arrange
        // Act
        [$statusCode, $content] = $this->runCommand([
            'command' => 'scan',
            '-p' => true,
            '-i' => $this->tempIni,
        ]);

        // Assert
        self::assertSame(1, $statusCode);
        self::assertStringContainsString('Scan result of php.ini for', $content);
        self::assertStringContainsString('display_errors', $content);
    }

    /** Tests command accepts a valid target PHP version and a passing development scan. */
    public function testCommandCompletesDevelopmentScanForTargetPhpVersion(): void
    {
        // Arrange
        $rules = (new DevelopmentRules(80400))->getRules();
        $this->writeRules($rules);

        // Act
        [$statusCode, $content] = $this->runCommand([
            'command' => 'scan',
            '-d' => true,
            '-i' => $this->tempIni,
            '--php-version' => '8.4.0',
        ]);

        // Assert
        self::assertSame(0, $statusCode);
        self::assertStringContainsString('Development', $content);
        self::assertStringContainsString('Failed: 0', $content);
    }

    /** Tests command accepts the short target PHP version format. */
    public function testCommandAcceptsMajorAndMinorPhpVersion(): void
    {
        // Arrange
        $rules = (new ProductionRules(80400))->getRules();
        $this->writeRules($rules);

        // Act
        [$statusCode, $content] = $this->runCommand([
            'command' => 'scan',
            '-p' => true,
            '-i' => $this->tempIni,
            '--php-version' => '8.4',
        ]);

        // Assert
        self::assertSame(0, $statusCode);
        self::assertStringContainsString('Production', $content);
        self::assertStringContainsString('Failed: 0', $content);
    }

    /** Tests command scans the loaded PHP configuration when no path is supplied. */
    public function testCommandUsesLoadedPhpConfigurationByDefault(): void
    {
        // Arrange
        $application = new Application();
        $application->add(new ScanCommand());
        $application->setAutoExit(false);
        $output = new BufferedOutput();

        // Act
        $statusCode = $application->run(new ArrayInput([
            'command' => 'scan',
            '-d' => true,
        ]), $output);
        $content = $output->fetch();

        // Assert
        self::assertContains($statusCode, [0, 1]);
        if (php_ini_loaded_file() !== false) {
            self::assertStringContainsString('Using loaded PHP configuration:', $content);
        } else {
            self::assertStringContainsString('No php.ini is loaded', $content);
        }
        if (php_ini_scanned_files() !== false && trim((string) php_ini_scanned_files()) !== '') {
            self::assertStringContainsString('Additional INI files', $content);
        }
    }

    /** Tests the CLI reports when PHP starts without a loaded INI file. */
    public function testCliReportsWhenNoPhpIniIsLoaded(): void
    {
        // Arrange
        $root = dirname(__DIR__, 2);
        $command = [
            PHP_BINARY,
            '-n',
            $root . '/bin/php-ini-scanner',
            '-d',
        ];
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        // Act
        $process = proc_open($command, $descriptors, $pipes, $root);
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $statusCode = proc_close($process);

        // Assert
        self::assertSame(1, $statusCode);
        self::assertStringContainsString('No php.ini is loaded', $stdout . $stderr);
    }

    /** Tests command handles Symfony console output error streams. */
    public function testCommandSupportsConsoleOutputInterface(): void
    {
        // Arrange
        $application = new Application();
        $application->add(new ScanCommand());
        $application->setAutoExit(false);
        $output = new \Symfony\Component\Console\Output\ConsoleOutput(
            \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_QUIET
        );

        // Act
        $statusCode = $application->run(new ArrayInput(['command' => 'scan']), $output);

        // Assert
        self::assertSame(1, $statusCode);
    }

    /** Tests scanner exceptions are reported as command failures. */
    public function testCommandReportsScannerFailure(): void
    {
        // Arrange
        file_put_contents($this->tempIni, "[broken\n");

        // Act
        [$statusCode, $content] = $this->runCommand([
            'command' => 'scan',
            '-p' => true,
            '-i' => $this->tempIni,
        ]);

        // Assert
        self::assertSame(1, $statusCode);
        self::assertStringContainsString('Error:', $content);
    }

    /** Tests command rejects invalid target php version. */
    public function testCommandRejectsInvalidTargetPhpVersion(): void
    {
        // Arrange
        // Act
        [$statusCode, $content] = $this->runCommand([
            'command' => 'scan',
            '-p' => true,
            '-i' => $this->tempIni,
            '--php-version' => 'eight.three',
        ]);

        // Assert
        self::assertSame(1, $statusCode);
        self::assertStringContainsString('--php-version must use', $content);
    }

    /** Tests command rejects directory as ini path. */
    public function testCommandRejectsDirectoryAsIniPath(): void
    {
        // Arrange
        // Act
        [$statusCode, $content] = $this->runCommand([
            'command' => 'scan',
            '-p' => true,
            '-i' => sys_get_temp_dir(),
        ]);

        // Assert
        self::assertSame(1, $statusCode);
        self::assertStringContainsString('readable INI file is required', $content);
    }

    /** Writes a complete rule set to the temporary INI fixture. */
    private function writeRules(array $rules): void
    {
        $lines = [];
        foreach ($rules as $setting => $value) {
            $lines[] = $setting . ' = ' . $value;
        }

        file_put_contents($this->tempIni, implode("\n", $lines));
    }

    /** Runs the command and returns its status and output. */
    private function runCommand(array $arguments): array
    {
        $application = new Application();
        $application->add(new ScanCommand());
        $application->setAutoExit(false);

        $output = new BufferedOutput();
        $statusCode = $application->run(new ArrayInput($arguments), $output);

        return [$statusCode, $output->fetch()];
    }
}
