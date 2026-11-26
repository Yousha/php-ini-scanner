<?php

declare(strict_types=1);

namespace Yousha\PhpIniScanner\Tests;

use PHPUnit\Framework\TestCase;
use Yousha\PhpIniScanner\Commands\ScanCommand;

/** Tests basic application bootstrapping. */
final class SmokeTest extends TestCase
{
    /** Tests Composer can autoload the scan command. */
    public function testComposerCanLoadScanCommand(): void
    {
        // Arrange
        // Act
        $isLoaded = class_exists(ScanCommand::class);

        // Assert
        self::assertTrue($isLoaded);
    }

    /** Tests the scan command can be instantiated. */
    public function testScanCommandCanBeInstantiated(): void
    {
        // Arrange
        // Act
        $command = new ScanCommand();

        // Assert
        self::assertInstanceOf(ScanCommand::class, $command);
    }
}
