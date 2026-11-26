<?php

declare(strict_types=1);

namespace Yousha\PhpIniScanner\Tests;

use PHPUnit\Framework\TestCase;

/** Tests project requirements and expected repository contents. */
final class RequirementsTest extends TestCase
{
    /** Tests the minimum supported PHP version. */
    public function testSupportedPhpVersion(): void
    {
        // Arrange
        // Act
        $versionId = PHP_VERSION_ID;

        // Assert
        self::assertGreaterThanOrEqual(70400, $versionId);
    }

    /** Tests required PHP extensions are available. */
    public function testRequiredPhpExtensionsAreAvailable(): void
    {
        // Arrange
        // Act
        $pcreLoaded = extension_loaded('pcre');

        // Assert
        self::assertTrue($pcreLoaded);
    }

    /** Tests required project directories exist. */
    public function testProjectDirectoriesExist(): void
    {
        // Arrange
        $root = dirname(__DIR__);
        $directories = ['bin', 'resources', 'src', 'tests'];

        // Act
        $paths = [];
        foreach ($directories as $directory) {
            $paths[] = $root . DIRECTORY_SEPARATOR . $directory;
        }

        // Assert
        foreach ($paths as $path) {
            self::assertDirectoryExists($path);
        }
    }

    /** Tests required project files exist. */
    public function testProjectFilesExist(): void
    {
        // Arrange
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

        // Act
        $paths = [];
        foreach ($files as $file) {
            $paths[] = $root . DIRECTORY_SEPARATOR . $file;
        }

        // Assert
        foreach ($paths as $path) {
            self::assertFileExists($path);
        }
    }
}
