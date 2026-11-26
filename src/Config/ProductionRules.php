<?php

declare(strict_types=1);

namespace Yousha\\PhpIniScanner\\Config;

use Yousha\\PhpIniScanner\\Contracts\\RuleSetInterface;

final class ProductionRules implements RuleSetInterface
{
    /** Returns the rule set name. */
    public function getName(): string
    {
        return 'Production';
    }

    /** Returns production configuration expectations. */
    public function getRules(): array
    {
        $rules = [
            'engine' => 'On',
            'short_open_tag' => 'Off',
            'error_reporting' => 'E_ALL',
            'display_errors' => 'Off',
            'log_errors' => 'On',
            'display_startup_errors' => 'Off',
            'html_errors' => 'Off',
            'zend.exception_ignore_args' => 'On',
            'memory_limit' => '256M',
            'max_execution_time' => '30',
            'max_input_time' => '60',
            'post_max_size' => '16M',
            'max_input_vars' => '1024',
            'session.cookie_secure' => 'On',
            'session.cookie_httponly' => 'On',
            'session.use_strict_mode' => 'On',
            'session.use_trans_sid' => 'Off',
            'expose_php' => 'Off',
            'mail.add_x_header' => 'Off',
            'allow_url_fopen' => 'Off',
            'allow_url_include' => 'Off',
            'cgi.fix_pathinfo' => 'Off',
            'mysqli.allow_local_infile' => 'Off',
            'opcache.enable' => 'On',
            'opcache.log_verbosity_level' => '2',
            'opcache.validate_timestamps' => 'On',
            'zlib.output_compression' => 'On',
            'fastcgi.logging' => 'Off',
            'enable_dl' => 'Off',
            'date.timezone' => 'UTC',
            'default_mimetype' => 'text/html',
            'zend.assertions' => '-1',
        ];

        if (PHP_VERSION_ID >= 80000) {
            $rules['opcache.record_warnings'] = 'On';
            $rules['zend.exception_string_param_max_len'] = '0';
        }

        if (PHP_VERSION_ID >= 80100) {
            $rules['opcache.interned_strings_buffer'] = '16';
        }

        if (PHP_VERSION_ID >= 80200) {
            $rules['error_log_mode'] = '0600';
        }

        if (PHP_VERSION_ID >= 80300 && PHP_VERSION_ID < 80400) {
            $rules['session.sid_length'] = '48';
        }

        if (PHP_VERSION_ID < 80500) {
            $rules['report_memleaks'] = 'On';
        }

        return $rules;
    }
}
