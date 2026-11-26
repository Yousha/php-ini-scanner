<?php

declare(strict_types=1);

namespace Yousha\PhpIniScanner\Scanner;

use Yousha\PhpIniScanner\Contracts\RuleSetInterface;

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
            throw new \RuntimeException("Cannot read INI file: {$filePath}");
        }

        $parsedConfigs = parse_ini_file($filePath, true, INI_SCANNER_RAW);
        if ($parsedConfigs === false) {
            throw new \RuntimeException("Failed to parse INI file: {$filePath}");
        }

        // Only global directives belong to the base configuration; PATH/HOST sections
        // are conditional overrides and must not be mistaken for global settings.
        $phpConfigs = array_filter($parsedConfigs, 'is_scalar');

        $standardRules = $standardRuleSet->getRules();
        if ($standardRules === []) {
            throw new \RuntimeException('The supplied rule set contains no rules.');
        }

        $results = [];
        foreach ($standardRules as $setting => $expectedValue) {
            $isSet = array_key_exists($setting, $phpConfigs) && is_scalar($phpConfigs[$setting]);
            $currentValue = $isSet
                ? $this->normalizeValue((string) $phpConfigs[$setting], (string) $setting)
                : '[NOT SET]';
            $expected = $this->normalizeValue((string) $expectedValue, (string) $setting);

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
     * Normalizes quoted, boolean-like, and PHP error-reporting values.
     */
    private function normalizeValue(string $value, string $setting): string
    {
        $value = trim($value);
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = substr($value, -1);
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        if ($setting === 'error_reporting') {
            $mask = $this->parseErrorReportingMask($value);
            if ($mask !== null) {
                return (string) $mask;
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

    /**
     * Evaluates the common PHP INI error_reporting bitmask form without eval().
     */
    private function parseErrorReportingMask(string $expression): ?int
    {
        $parts = preg_split('/\s*&\s*/', trim($expression));
        if ($parts === false || $parts === []) {
            return null;
        }

        $mask = null;
        foreach ($parts as $part) {
            $part = trim($part);
            $invert = isset($part[0]) && $part[0] === '~';
            $constantName = $invert ? trim(substr($part, 1)) : $part;

            if (!preg_match('/^E_[A-Z_]+$/', $constantName) || !defined($constantName)) {
                return null;
            }

            $value = (int) constant($constantName);
            if ($invert) {
                $value = ~$value;
            }

            $mask = $mask === null ? $value : ($mask & $value);
        }

        return $mask;
    }
}
