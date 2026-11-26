<?php

declare(strict_types=1);

namespace Yousha\\PhpIniScanner\\Tests\\Commands;

use PHPUnit\\Framework\\TestCase;
use Symfony\\Component\\Console\\Application;
use Symfony\\Component\\Console\\Input\\ArrayInput;
use Symfony\\Component\\Console\\Output\\BufferedOutput;
use Yousha\\PhpIniScanner\\Commands\\ScanCommand;

final class ScanCommandTest extends TestCase
{
    private string $tempIni;

    protected function setUp(): void
    {
        $this->tempIni = tempnam(sys_get_temp_dir(), 'test_cli_');
        file_put_contents($this->tempIni, "display_errors = Off\nlog_errors = On");
    }

    protected function tearDown(): void
    {
        if (is_file($this->tempIni)) {
            unlink($this->tempIni);
        }
    }

    public function testCommandFailsWithoutMode(): void
    {
        list($statusCode, $content) = $this->runCommand(['command' => 'scan', '-i' => $this->tempIni]);

        self::assertSame(1, $statusCode);
        self::assertStringContainsString('Specify exactly one mode', $content);
    }

    public function testCommandRejectsBothModes(): void
    {
        list($statusCode, $content) = $this->runCommand([
            'command' => 'scan',
            '-d' => true,
            '-p' => true,
            '-i' => $this->tempIni,
        ]);

        self::assertSame(1, $statusCode);
        self::assertStringContainsString('Specify exactly one mode', $content);
    }

    public function testCommandReturnsFailureWhenRulesDoNotPass(): void
    {
        list($statusCode, $content) = $this->runCommand([
            'command' => 'scan',
            '-p' => true,
            '-i' => $this->tempIni,
        ]);

        self::assertSame(1, $statusCode);
        self::assertStringContainsString('Scan result of php.ini for', $content);
        self::assertStringContainsString('display_errors', $content);
    }

    public function testCommandRejectsDirectoryAsIniPath(): void
    {
        list($statusCode, $content) = $this->runCommand([
            'command' => 'scan',
            '-p' => true,
            '-i' => sys_get_temp_dir(),
        ]);

        self::assertSame(1, $statusCode);
        self::assertStringContainsString('readable INI file is required', $content);
    }

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
