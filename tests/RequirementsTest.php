<?php

declare(strict_types=1);

namespace Yousha\PhpIniScanner\Tests;

use PHPUnit\Framework\TestCase;

/** Tests requirements behavior. */
final class RequirementsTest extends TestCase
{
    /** Tests supported php version. */
    public function testSupportedPhpVersion(): void
    {
        // Arrange
        // Assert
        self::assertGreaterThanOrEqual(70400, PHP_VERSION_ID);
    }

    /** Tests required php extensions are available. */
    public function testRequiredPhpExtensionsAreAvailable(): void
    {
        // Arrange
        // Assert
        self::assertTrue(extension_loaded('pcre'));
    }

    /** Tests project directories exist. */
    public function testProjectDirectoriesExist(): void
    {
        // Arrange
        // Act
        $root = dirname(__DIR__);
        foreach (['bin', 'resources', 'src', 'tests'] as $directory) {
        // Assert
            self::assertDirectoryExists($root . DIRECTORY_SEPARATOR . $directory);
        }
    }

    /** Tests project files exist. */
    public function testProjectFilesExist(): void
    {
        // Arrange
        // Act
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
        // Assert
            self::assertFileExists($root . DIRECTORY_SEPARATOR . $file);
        }
    }
}
