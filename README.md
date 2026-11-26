# PHP INI Scanner

A lightweight CLI tool to audit `php.ini` configurations against development and production standards.

[![current version](https://img.shields.io/packagist/v/yousha/php-ini-scanner.svg)](https://packagist.org/packages/yousha/php-ini-scanner)
[![Dependabot Updates](https://github.com/Yousha/php-ini-scanner/actions/workflows/dependabot/dependabot-updates/badge.svg?branch=main)](https://github.com/Yousha/php-ini-scanner/actions/workflows/dependabot/dependabot-updates)
[![PHP](https://img.shields.io/badge/PHP-7.4-617CBE)](https://php.net/) [![PHP](https://img.shields.io/badge/PHP-8.5-617CBE)](https://php.net/) [![issues](https://img.shields.io/github/issues/yousha/php-ini-scanner)](https://github.com/yousha/php-ini-scanner/issues) ![repo size](https://img.shields.io/github/repo-size/yousha/php-ini-scanner) [![GitHub license](https://img.shields.io/github/license/yousha/php-ini-scanner)](LICENSE)

It compares configured settings against recommended values for either **Development** or **Production** environments.

_Built using **SOLID** principles, `strict_types=1`, and PSR-compliant code._

## Requirements

- Windows or GNU/Linux OS
- PHP 7.4 or later (PHP 8.x supported).
- Composer 2 or later.

## Screenshots

![Screenshot](resources/images/screenshots/1.png)

## Installation

Install it in a project:

```shell
composer require yousha/php-ini-scanner
```

Or install it globally:

```shell
composer global require yousha/php-ini-scanner
```

For a global installation, make sure Composer's global vendor `bin` directory is included in your system `PATH`.

## Usage

Exactly one environment option is required: `-p` (`--production`) or `-d` (`--development`).

### Scan for Production

Scan a specific configuration file:

```bash
php ./vendor/bin/php-ini-scanner scan -p -i /path/to/php.ini
```

When the file belongs to a different PHP installation, specify the target version. Version-specific rules accept `MAJOR.MINOR` or `MAJOR.MINOR.PATCH`:

```bash
php ./vendor/bin/php-ini-scanner scan -p -i /path/to/php.ini --php-version=8.3.0
```

On Windows:

```powershell
php .\\vendor\\bin\\php-ini-scanner scan -p -i "C:\\Programs\\PHP\\php.ini" --php-version=8.3
```

To scan the automatically loaded configuration for the current PHP runtime:

```bash
php ./vendor/bin/php-ini-scanner scan -p
```

### Scan for Development

Scan a specific configuration file:

```bash
php ./vendor/bin/php-ini-scanner scan -d -i /path/to/php.ini
```

You can also specify the PHP version whose version-specific rules should be used:

```bash
php ./vendor/bin/php-ini-scanner scan -d -i /path/to/php.ini --php-version=8.3
```

On Windows:

```powershell
php .\\vendor\\bin\\php-ini-scanner scan -d -i "C:\\Programs\\PHP\\php.ini"
```

To scan the automatically loaded configuration for the current PHP runtime:

```bash
php ./vendor/bin/php-ini-scanner scan -d
```

For a global Composer installation, run `php-ini-scanner` instead of `php ./vendor/bin/php-ini-scanner`.

## Commands and options

| Option | Shortcut | Description |
| --- | --- | --- |
| `--production` | `-p` | Use the production rule set. Exactly one of `-p` or `-d` is required. |
| `--development` | `-d` | Use the development rule set. Exactly one of `-d` or `-p` is required. |
| `--ini-path` | `-i` | Path to the `php.ini` file. If omitted, the scanner uses the configuration loaded by the current PHP runtime. |
| `--php-version` | | Target PHP version used to select version-specific rules. Accepts `MAJOR.MINOR` or `MAJOR.MINOR.PATCH`; defaults to the PHP version running the scanner. |

## Exit codes

- `0`: The scan completed and all checked rules passed.
- `1`: The scan encountered a failure or error.

## Testing

Run the test suite:

```bash
composer test
```

Run the test suite with code coverage:

```bash
composer test-coverage
```

Coverage reporting requires a supported PHP coverage driver, such as Xdebug or PCOV.

## License

This open-source software is distributed under the GPL-3.0-only license. See the [LICENSE](LICENSE) file.
