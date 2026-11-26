<?php

declare(strict_types=1);

namespace Yousha\PhpIniScanner\Tests\Scanner;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yousha\PhpIniScanner\Config\DevelopmentRules;
use Yousha\PhpIniScanner\Config\ProductionRules;
use Yousha\PhpIniScanner\Contracts\RuleSetInterface;
use Yousha\PhpIniScanner\Scanner\ScannerEngine;

/** Tests scanner parsing, normalization, and validation behavior. */
final class ScannerEngineTest extends TestCase
{
    private string $tempIni;

    private ScannerEngine $engine;

    /** Prepares temporary INI fixtures for each test. */
    protected function setUp(): void
    {
        $this->engine = new ScannerEngine();
        $this->tempIni = tempnam(sys_get_temp_dir(), 'php_ini_scanner_');
    }

    /** Removes temporary INI fixtures after each test. */
    protected function tearDown(): void
    {
        if (is_file($this->tempIni)) {
            unlink($this->tempIni);
        }
    }

    /** Tests normalization of boolean values and quoted strings. */
    public function testNormalizesBooleanValuesAndQuotedStrings(): void
    {
        // Arrange
        file_put_contents($this->tempIni, "display_errors = 0\ndefault_mimetype = \"text/html\"\n");

        // Act
        $results = $this->engine->scanFile($this->tempIni, $this->rules([
            'display_errors' => 'Off',
            'default_mimetype' => 'text/html',
        ]));

        // Assert
        self::assertSame('off', $this->resultFor($results, 'display_errors')['current']);
        self::assertTrue($this->resultFor($results, 'display_errors')['pass']);
        self::assertSame('text/html', $this->resultFor($results, 'default_mimetype')['current']);
        self::assertTrue($this->resultFor($results, 'default_mimetype')['pass']);
    }

    /** Tests equivalent PHP error_reporting bitmask expressions. */
    public function testComparesEquivalentErrorReportingBitmasks(): void
    {
        // Arrange
        file_put_contents($this->tempIni, 'error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT');

        // Act
        $results = $this->engine->scanFile($this->tempIni, $this->rules([
            'error_reporting' => 'E_ALL & ~E_DEPRECATED & ~E_STRICT',
        ]));

        // Assert
        self::assertTrue($this->resultFor($results, 'error_reporting')['pass']);
    }

    /** Tests missing values are reported without console markup. */
    public function testReportsMissingValuesWithoutEmbeddingConsoleMarkup(): void
    {
        // Arrange
        file_put_contents($this->tempIni, "unrelated = value\n");

        // Act
        $results = $this->engine->scanFile($this->tempIni, $this->rules(['display_errors' => 'Off']));

        // Assert
        self::assertSame('[NOT SET]', $results[0]['current']);
        self::assertFalse($results[0]['pass']);
    }

    /** Tests empty and comment-only INI files report unset rules. */
    public function testAcceptsAnEmptyOrCommentOnlyIniFileAndReportsUnsetRules(): void
    {
        // Arrange
        file_put_contents($this->tempIni, "; intentionally empty\n");

        // Act
        $results = $this->engine->scanFile($this->tempIni, $this->rules(['display_errors' => 'Off']));

        // Assert
        self::assertCount(1, $results);
        self::assertSame('[NOT SET]', $results[0]['current']);
        self::assertFalse($results[0]['pass']);
    }

    /** Tests ordinary INI sections are read and conditional sections are ignored. */
    public function testReadsDirectivesFromOrdinaryIniSectionsAndIgnoresConditionalSections(): void
    {
        // Arrange
        file_put_contents($this->tempIni, "[PHP]\nengine = On\nshort_open_tag = Off\n[Date]\ndate.timezone = UTC\n[Session]\nsession.use_strict_mode = On\n[PATH=/var/www]\ndisplay_errors = Off\n[HOST=example.com]\nallow_url_fopen = Off\n");

        // Act
        $results = $this->engine->scanFile($this->tempIni, $this->rules([
            'engine' => 'On',
            'short_open_tag' => 'Off',
            'date.timezone' => 'UTC',
            'session.use_strict_mode' => 'On',
            'display_errors' => 'On',
            'allow_url_fopen' => 'On',
        ]));

        // Assert
        foreach (['engine', 'short_open_tag', 'date.timezone', 'session.use_strict_mode'] as $setting) {
            self::assertTrue($this->resultFor($results, $setting)['pass']);
        }
        self::assertSame('[NOT SET]', $this->resultFor($results, 'display_errors')['current']);
        self::assertSame('[NOT SET]', $this->resultFor($results, 'allow_url_fopen')['current']);
    }

    /** Tests session.sid_length is included only before PHP 8.4. */
    public function testSessionSidLengthIsAvailableOnlyBeforePhp84(): void
    {
        // Arrange
        $beforePhp84 = [70400, 80000, 80399];
        $php84AndLater = [80400, 80401, 80500];

        // Act
        $rulesBeforePhp84 = [];
        foreach ($beforePhp84 as $phpVersionId) {
            $rulesBeforePhp84[] = [
                (new DevelopmentRules($phpVersionId))->getRules(),
                (new ProductionRules($phpVersionId))->getRules(),
            ];
        }
        $rulesPhp84AndLater = [];
        foreach ($php84AndLater as $phpVersionId) {
            $rulesPhp84AndLater[] = [
                (new DevelopmentRules($phpVersionId))->getRules(),
                (new ProductionRules($phpVersionId))->getRules(),
            ];
        }

        // Assert
        foreach ($rulesBeforePhp84 as $rules) {
            self::assertArrayHasKey('session.sid_length', $rules[0]);
            self::assertArrayHasKey('session.sid_length', $rules[1]);
        }
        foreach ($rulesPhp84AndLater as $rules) {
            self::assertArrayNotHasKey('session.sid_length', $rules[0]);
            self::assertArrayNotHasKey('session.sid_length', $rules[1]);
        }
    }

    /** Tests JIT rules do not require or recommend a JIT buffer. */
    public function testJitRulesDoNotRequireOrRecommendAJitBuffer(): void
    {
        // Arrange
        $phpVersions = [80000, 80399, 80400, 80401, 80500];

        // Act
        $ruleSets = [];
        foreach ($phpVersions as $phpVersionId) {
            $ruleSets[] = [
                (new DevelopmentRules($phpVersionId))->getRules(),
                (new ProductionRules($phpVersionId))->getRules(),
            ];
        }

        // Assert
        foreach ($ruleSets as $rules) {
            self::assertSame('disable', $rules[0]['opcache.jit']);
            self::assertSame('disable', $rules[1]['opcache.jit']);
            self::assertArrayNotHasKey('opcache.jit_buffer_size', $rules[0]);
            self::assertArrayNotHasKey('opcache.jit_buffer_size', $rules[1]);
        }
    }

    /** Tests rule sets honor the specified target PHP version. */
    public function testRuleSetsUseTheSpecifiedTargetPhpVersion(): void
    {
        // Arrange
        $developmentVersion = 80400;
        $productionVersion = 80500;

        // Act
        $developmentRules = (new DevelopmentRules($developmentVersion))->getRules();
        $productionRules = (new ProductionRules($productionVersion))->getRules();

        // Assert
        self::assertArrayNotHasKey('session.sid_length', $developmentRules);
        self::assertArrayNotHasKey('session.sid_length', $productionRules);
        self::assertArrayNotHasKey('report_memleaks', $productionRules);
    }

    /** Tests an empty rule set is rejected. */
    public function testRejectsAnEmptyRuleSet(): void
    {
        // Arrange
        file_put_contents($this->tempIni, "display_errors = Off\n");
        $rules = $this->rules([]);

        // Act
        $exception = null;
        try {
            $this->engine->scanFile($this->tempIni, $rules);
        } catch (RuntimeException $caught) {
            $exception = $caught;
        }

        // Assert
        self::assertInstanceOf(RuntimeException::class, $exception);
    }

    /** Tests directory paths are rejected. */
    public function testRejectsDirectoryPaths(): void
    {
        // Arrange
        $rules = $this->rules(['display_errors' => 'Off']);

        // Act
        $exception = null;
        try {
            $this->engine->scanFile(sys_get_temp_dir(), $rules);
        } catch (RuntimeException $caught) {
            $exception = $caught;
        }

        // Assert
        self::assertInstanceOf(RuntimeException::class, $exception);
    }

    /** Tests missing INI files are rejected. */
    public function testThrowsForMissingFile(): void
    {
        // Arrange
        $missingPath = $this->tempIni . '.missing';
        $rules = $this->rules(['display_errors' => 'Off']);

        // Act
        $exception = null;
        try {
            $this->engine->scanFile($missingPath, $rules);
        } catch (RuntimeException $caught) {
            $exception = $caught;
        }

        // Assert
        self::assertInstanceOf(RuntimeException::class, $exception);
    }

    /**
     * Creates an in-memory rule set for scanner tests.
     *
     * @param array<string, string> $rules
     */
    private function rules(array $rules): RuleSetInterface
    {
        return new class($rules) implements RuleSetInterface {
            private array $rules;

            /** Stores the supplied test rules. */
            public function __construct(array $rules)
            {
                $this->rules = $rules;
            }

            /** Returns the test rule set name. */
            public function getName(): string
            {
                return 'Test';
            }

            /** Returns the supplied test rules. */
            public function getRules(): array
            {
                return $this->rules;
            }
        };
    }

    /**
     * Finds one result by setting name.
     *
     * @param array<int, array{setting: string, expected: string, current: string, pass: bool}> $results
     * @return array{setting: string, expected: string, current: string, pass: bool}
     */
    private function resultFor(array $results, string $setting): array
    {
        foreach ($results as $result) {
            if ($result['setting'] === $setting) {
                return $result;
            }
        }

        self::fail('Expected setting was not returned: ' . $setting);
        return [];
    }
}
