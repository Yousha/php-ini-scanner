<?php

declare(strict_types=1);

namespace Yousha\\PhpIniScanner\\Scanner;

use Yousha\\PhpIniScanner\\Contracts\\RuleSetInterface;

final class ScannerEngine
{
    /**
     * Parses an INI file and compares its explicitly configured values with a rule set.
     *
     * @param string $filePath Path to the INI file.
     * @param RuleSetInterface $standardRuleSet Rules to validate.
     * @return array<int, array{setting: string, expected: string, current: string, pass: bool}>
     */
    public function scanFile(string $filePath, RuleSetInterface $standardRuleSet): array
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new \\RuntimeException("Cannot read INI file: {$filePath}");
        }

        $phpConfigs = parse_ini_file($filePath, false, INI_SCANNER_RAW);
        if ($phpConfigs === false) {
            throw new \\RuntimeException("Failed to parse INI file: {$filePath}");
        }

        $standardRules = $standardRuleSet->getRules();
        if ($standardRules === []) {
            throw new \\RuntimeException('The supplied rule set contains no rules.');
        }

        $results = [];
        foreach ($standardRules as $setting => $expectedValue) {
            $isSet = array_key_exists($setting, $phpConfigs);
            $currentValue = $isSet && is_scalar($phpConfigs[$setting])
                ? $this->normalizeValue((string) $phpConfigs[$setting])
                : '[NOT SET]';
            $expected = $this->normalizeValue((string) $expectedValue);

            $results[] = [
                'setting' => (string) $setting,
                'current' => $currentValue,
                'expected' => $expected,
                'pass' => $isSet && $currentValue === $expected,
            ];
        }

        return $results;
    }

    /**
     * Normalizes quoted and boolean-like INI scalar values.
     */
    private function normalizeValue(string $value): string
    {
        $value = trim($value);
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = substr($value, -1);
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        $value = strtolower(trim($value));

        switch ($value) {
            case 'off':
            case '0':
            case 'false':
            case 'no':
                return 'off';
            case 'on':
            case '1':
            case 'true':
            case 'yes':
                return 'on';
            default:
                return $value;
        }
    }
}
