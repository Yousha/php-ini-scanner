<?php

declare(strict_types=1);

namespace Yousha\PhpIniScanner\Tests;

use PHPUnit\Framework\TestCase;
use Yousha\PhpIniScanner\Commands\ScanCommand;

/** Tests smoke behavior. */
final class SmokeTest extends TestCase
{
    /** Tests composer can load scan command. */
    public function testComposerCanLoadScanCommand(): void
    {
        // Arrange
        // Act
        // Assert
        self::assertTrue(class_exists(ScanCommand::class));
    }

    /** Tests scan command can be instantiated. */
    public function testScanCommandCanBeInstantiated(): void
    {
        // Arrange
        // Act
        $command = new ScanCommand();

        // Assert
        self::assertInstanceOf(ScanCommand::class, $command);
    }
}
