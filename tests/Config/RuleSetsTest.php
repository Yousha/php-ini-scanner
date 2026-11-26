<?php

declare(strict_types=1);

namespace Yousha\PhpIniScanner\Tests\Config;

use PHPUnit\Framework\TestCase;
use Yousha\PhpIniScanner\Config\DevelopmentRules;
use Yousha\PhpIniScanner\Config\ProductionRules;

/** Tests development and production PHP configuration rule sets. */
final class RuleSetsTest extends TestCase
{
    /** Tests the development rule set name and required settings. */
    public function testDevelopmentRuleSet(): void
    {
        // Arrange
        $ruleSet = new DevelopmentRules(80400);

        // Act
        $name = $ruleSet->getName();
        $rules = $ruleSet->getRules();

        // Assert
        self::assertSame('Development', $name);
        self::assertSame('512M', $rules['memory_limit']);
        self::assertSame('Off', $rules['allow_url_fopen']);
        self::assertSame('32', $rules['session.sid_length']);
        self::assertSame('On', $rules['display_errors']);
    }

    /** Tests development rules across supported PHP feature thresholds. */
    public function testDevelopmentRulesRespectPhpVersion(): void
    {
        // Arrange
        $versions = [70400, 80000, 80200, 80400, 80500];

        // Act
        $ruleSets = [];
        foreach ($versions as $version) {
            $ruleSets[$version] = (new DevelopmentRules($version))->getRules();
        }

        // Assert
        self::assertArrayNotHasKey('opcache.jit', $ruleSets[70400]);
        self::assertSame('disable', $ruleSets[80000]['opcache.jit']);
        self::assertArrayNotHasKey('error_log_mode', $ruleSets[80000]);
        self::assertSame('0600', $ruleSets[80200]['error_log_mode']);
        self::assertArrayHasKey('report_memleaks', $ruleSets[80400]);
        self::assertArrayNotHasKey('report_memleaks', $ruleSets[80500]);
    }

    /** Tests the production rule set name and required settings. */
    public function testProductionRuleSet(): void
    {
        // Arrange
        $ruleSet = new ProductionRules(80400);

        // Act
        $name = $ruleSet->getName();
        $rules = $ruleSet->getRules();

        // Assert
        self::assertSame('Production', $name);
        self::assertSame('256M', $rules['memory_limit']);
        self::assertSame('Off', $rules['allow_url_fopen']);
        self::assertSame('32', $rules['session.sid_length']);
        self::assertSame('Off', $rules['display_errors']);
    }

    /** Tests production rules across supported PHP feature thresholds. */
    public function testProductionRulesRespectPhpVersion(): void
    {
        // Arrange
        $versions = [70400, 80000, 80100, 80200, 80400, 80500];

        // Act
        $ruleSets = [];
        foreach ($versions as $version) {
            $ruleSets[$version] = (new ProductionRules($version))->getRules();
        }

        // Assert
        self::assertArrayNotHasKey('opcache.jit', $ruleSets[70400]);
        self::assertSame('disable', $ruleSets[80000]['opcache.jit']);
        self::assertArrayNotHasKey('opcache.interned_strings_buffer', $ruleSets[80000]);
        self::assertSame('16', $ruleSets[80100]['opcache.interned_strings_buffer']);
        self::assertSame('0600', $ruleSets[80200]['error_log_mode']);
        self::assertArrayHasKey('report_memleaks', $ruleSets[80400]);
        self::assertArrayNotHasKey('report_memleaks', $ruleSets[80500]);
    }
}
