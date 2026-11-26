<?php

declare(strict_types=1);

namespace Yousha\PhpIniScanner\Tests;

use PHPUnit\Framework\TestCase;
use Yousha\PhpIniScanner\Commands\ScanCommand;

final class SmokeTest extends TestCase
{
    public function testComposerCanLoadScanCommand(): void
    {
        self::assertTrue(class_exists(ScanCommand::class));
    }

    public function testScanCommandCanBeInstantiated(): void
    {
        $command = new ScanCommand();

        self::assertInstanceOf(ScanCommand::class, $command);
    }
}
