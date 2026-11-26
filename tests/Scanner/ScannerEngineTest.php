<?php

declare(strict_types=1);

namespace Yousha\PhpIniScanner\Tests\Scanner;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yousha\PhpIniScanner\Config\DevelopmentRules;
use Yousha\PhpIniScanner\Config\ProductionRules;
use Yousha\PhpIniScanner\Contracts\RuleSetInterface;
use Yousha\PhpIniScanner\Scanner\ScannerEngine;

/** Tests scanner engine behavior. */
final class ScannerEngineTest extends TestCase
{
    private string $tempIni;

    private ScannerEngine $engine;

    /** Prepares test fixtures. */
    protected function setUp(): void
    {
        $this->engine = new ScannerEngine();
        $this->tempIni = tempnam(sys_get_temp_dir(), 'php_ini_scanner_');
    }

    /** Cleans up test fixtures. */
    protected function tearDown(): void
    {
        if (is_file($this->tempIni)) {
            unlink($this->tempIni);
        }
    }

    /** Tests normalizes boolean values and quoted strings. */
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

    /** Tests compares equivalent error reporting bitmasks. */
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

    /** Tests reports missing values without embedding console markup. */
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

    /** Tests accepts an empty or comment only ini file and reports unset rules. */
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

    /** Tests reads directives from ordinary ini sections and ignores conditional sections. */
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

        foreach (['engine', 'short_open_tag', 'date.timezone', 'session.use_strict_mode'] as $setting) {
        // Assert
            self::assertTrue($this->resultFor($results, $setting)['pass']);
        }

        self::assertSame('[NOT SET]', $this->resultFor($results, 'display_errors')['current']);
        self::assertSame('[NOT SET]', $this->resultFor($results, 'allow_url_fopen')['current']);
    }

    /** Tests session sid length is available only before php84. */
    public function testSessionSidLengthIsAvailableOnlyBeforePhp84(): void
    {
        // Arrange
        foreach ([70400, 80000, 80399] as $phpVersionId) {
        // Act
            $developmentRules = new DevelopmentRules($phpVersionId);
            $productionRules = new ProductionRules($phpVersionId);

        // Assert
            self::assertArrayHasKey('session.sid_length', $developmentRules->getRules());
            self::assertArrayHasKey('session.sid_length', $productionRules->getRules());
        }

        foreach ([80400, 80401, 80500] as $phpVersionId) {
            $developmentRules = new DevelopmentRules($phpVersionId);
            $productionRules = new ProductionRules($phpVersionId);

            self::assertArrayNotHasKey('session.sid_length', $developmentRules->getRules());
            self::assertArrayNotHasKey('session.sid_length', $productionRules->getRules());
        }
    }

    /** Tests jit rules do not require or recommend ajit buffer. */
    public function testJitRulesDoNotRequireOrRecommendAJitBuffer(): void
    {
        // Arrange
        foreach ([80000, 80399, 80400, 80401, 80500] as $phpVersionId) {
        // Act
            $developmentRules = new DevelopmentRules($phpVersionId);
            $productionRules = new ProductionRules($phpVersionId);

        // Assert
            self::assertSame('disable', $developmentRules->getRules()['opcache.jit']);
            self::assertSame('disable', $productionRules->getRules()['opcache.jit']);
            self::assertArrayNotHasKey('opcache.jit_buffer_size', $developmentRules->getRules());
            self::assertArrayNotHasKey('opcache.jit_buffer_size', $productionRules->getRules());
        }
    }

    /** Tests rule sets use the specified target php version. */
    public function testRuleSetsUseTheSpecifiedTargetPhpVersion(): void
    {
        // Arrange
        // Act
        $developmentRules = new DevelopmentRules(80400);
        $productionRules = new ProductionRules(80500);

        // Assert
        self::assertArrayNotHasKey('session.sid_length', $developmentRules->getRules());
        self::assertArrayNotHasKey('session.sid_length', $productionRules->getRules());
        self::assertArrayNotHasKey('report_memleaks', $productionRules->getRules());
    }

    /** Tests rejects an empty rule set. */
    public function testRejectsAnEmptyRuleSet(): void
    {
        // Arrange
        file_put_contents($this->tempIni, "display_errors = Off\n");

        $this->expectException(RuntimeException::class);
        // Act
        $this->engine->scanFile($this->tempIni, $this->rules([]));
    }

    /** Tests rejects directory paths. */
    public function testRejectsDirectoryPaths(): void
    {
        // Arrange
        $this->expectException(RuntimeException::class);
        // Act
        $this->engine->scanFile(sys_get_temp_dir(), $this->rules(['display_errors' => 'Off']));
    }

    /** Tests throws for missing file. */
    public function testThrowsForMissingFile(): void
    {
        // Arrange
        $this->expectException(RuntimeException::class);
        // Act
        $this->engine->scanFile($this->tempIni . '.missing', $this->rules(['display_errors' => 'Off']));
    }

    /** Performs the rules test helper operation. */
    private function rules(array $rules): RuleSetInterface
    {
        return new class($rules) implements RuleSetInterface {
            private array $rules;

            public function __construct(array $rules)
            {
                $this->rules = $rules;
            }

            public function getName(): string
            {
                return 'Test';
            }

            public function getRules(): array
            {
                return $this->rules;
            }
        };
    }

    /** Performs the result for test helper operation. */
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
