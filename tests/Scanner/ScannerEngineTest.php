<?php

declare(strict_types=1);

namespace Yousha\PhpIniScanner\Tests\Scanner;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yousha\PhpIniScanner\Contracts\RuleSetInterface;
use Yousha\PhpIniScanner\Scanner\ScannerEngine;

final class ScannerEngineTest extends TestCase
{
    private string $tempIni;

    private ScannerEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new ScannerEngine();
        $this->tempIni = tempnam(sys_get_temp_dir(), 'php_ini_scanner_');
    }

    protected function tearDown(): void
    {
        if (is_file($this->tempIni)) {
            unlink($this->tempIni);
        }
    }

    public function testNormalizesBooleanValuesAndQuotedStrings(): void
    {
        file_put_contents($this->tempIni, "display_errors = 0\ndefault_mimetype = \"text/html\"\n");

        $results = $this->engine->scanFile($this->tempIni, $this->rules([
            'display_errors' => 'Off',
            'default_mimetype' => 'text/html',
        ]));

        self::assertSame('off', $this->resultFor($results, 'display_errors')['current']);
        self::assertTrue($this->resultFor($results, 'display_errors')['pass']);
        self::assertSame('text/html', $this->resultFor($results, 'default_mimetype')['current']);
        self::assertTrue($this->resultFor($results, 'default_mimetype')['pass']);
    }

    public function testComparesEquivalentErrorReportingBitmasks(): void
    {
        file_put_contents($this->tempIni, 'error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT');

        $results = $this->engine->scanFile($this->tempIni, $this->rules([
            'error_reporting' => 'E_ALL & ~E_DEPRECATED & ~E_STRICT',
        ]));

        self::assertTrue($this->resultFor($results, 'error_reporting')['pass']);
    }

    public function testReportsMissingValuesWithoutEmbeddingConsoleMarkup(): void
    {
        file_put_contents($this->tempIni, "unrelated = value\n");

        $results = $this->engine->scanFile($this->tempIni, $this->rules(['display_errors' => 'Off']));

        self::assertSame('[NOT SET]', $results[0]['current']);
        self::assertFalse($results[0]['pass']);
    }

    public function testAcceptsAnEmptyOrCommentOnlyIniFileAndReportsUnsetRules(): void
    {
        file_put_contents($this->tempIni, "; intentionally empty\n");

        $results = $this->engine->scanFile($this->tempIni, $this->rules(['display_errors' => 'Off']));

        self::assertCount(1, $results);
        self::assertSame('[NOT SET]', $results[0]['current']);
        self::assertFalse($results[0]['pass']);
    }

    public function testRejectsAnEmptyRuleSet(): void
    {
        file_put_contents($this->tempIni, "display_errors = Off\n");

        $this->expectException(RuntimeException::class);
        $this->engine->scanFile($this->tempIni, $this->rules([]));
    }

    public function testRejectsDirectoryPaths(): void
    {
        $this->expectException(RuntimeException::class);
        $this->engine->scanFile(sys_get_temp_dir(), $this->rules(['display_errors' => 'Off']));
    }

    public function testThrowsForMissingFile(): void
    {
        $this->expectException(RuntimeException::class);
        $this->engine->scanFile($this->tempIni . '.missing', $this->rules(['display_errors' => 'Off']));
    }

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

    private function resultFor(array $results, string $setting): array
    {
        foreach ($results as $result) {
            if ($result['setting'] === $setting) {
                return $result;
            }
        }

        self::fail('Expected setting was not returned: ' . $setting);
    }
}
