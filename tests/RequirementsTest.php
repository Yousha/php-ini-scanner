<?php

declare(strict_types=1);

namespace Yousha\PhpIniScanner\Tests;

use PHPUnit\Framework\TestCase;

final class RequirementsTest extends TestCase
{
    public function testSupportedPhpVersion(): void
    {
        self::assertGreaterThanOrEqual(70400, PHP_VERSION_ID);
    }

    public function testRequiredPhpExtensionsAreAvailable(): void
    {
        self::assertTrue(extension_loaded('pcre'));
    }

    public function testProjectDirectoriesExist(): void
    {
        $root = dirname(__DIR__);
        foreach (['bin', 'resources', 'src', 'tests'] as $directory) {
            self::assertDirectoryExists($root . DIRECTORY_SEPARATOR . $directory);
        }
    }

    public function testProjectFilesExist(): void
    {
        $root = dirname(__DIR__);
        $files = [
            '.editorconfig',
            '.gitattributes',
            '.gitignore',
            '.mailmap',
            '.phplint.yml',
            'composer.json',
            'LICENSE',
            '.php-cs-fixer.php',
            'phpunit.xml',
            'README.md',
            'rector.php',
            'VERSION.txt',
        ];

        foreach ($files as $file) {
            self::assertFileExists($root . DIRECTORY_SEPARATOR . $file);
        }
    }
}
