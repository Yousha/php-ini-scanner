<?php

declare(strict_types=1);

namespace Yousha\PhpIniScanner\Config;

use Yousha\PhpIniScanner\Contracts\RuleSetInterface;

final class DevelopmentRules implements RuleSetInterface
{
    private int $phpVersionId;

    /** Uses the supplied target PHP version, or the current runtime version by default. */
    public function __construct(?int $phpVersionId = null)
    {
        $this->phpVersionId = $phpVersionId ?? PHP_VERSION_ID;
    }

    /** Returns the rule set name. */
    public function getName(): string
    {
        return 'Development';
    }

    /** Returns development configuration expectations. */
    public function getRules(): array
    {
        $rules = [
            'engine' => 'On',
            'short_open_tag' => 'Off',
            'error_reporting' => 'E_ALL',
            'display_errors' => 'On',
            'log_errors' => 'On',
            'display_startup_errors' => 'On',
            'html_errors' => 'Off',
            'zend.exception_ignore_args' => 'Off',
            'memory_limit' => '128M',
            'max_execution_time' => '120',
            'max_input_time' => '60',
            'post_max_size' => '8M',
            'max_input_vars' => '1024',
            'session.cookie_secure' => 'Off',
            'session.cookie_httponly' => 'On',
            'session.use_strict_mode' => 'On',
            'session.use_trans_sid' => 'Off',
            'expose_php' => 'On',
            'mail.add_x_header' => 'On',
            'allow_url_fopen' => 'On',
            'allow_url_include' => 'Off',
            'cgi.fix_pathinfo' => 'Off',
            'mysqli.allow_local_infile' => 'Off',
            'opcache.enable' => 'Off',
            'opcache.enable_cli' => 'Off',
            'opcache.log_verbosity_level' => '2',
            'fastcgi.logging' => 'On',
            'enable_dl' => 'Off',
            'date.timezone' => 'UTC',
            'default_mimetype' => 'text/html',
            'zend.assertions' => '1',
        ];

        if ($this->phpVersionId >= 80000) {
            $rules['opcache.record_warnings'] = 'Off';
            $rules['zend.exception_string_param_max_len'] = '15';
        }

        if ($this->phpVersionId >= 80200) {
            $rules['error_log_mode'] = '0600';
        }

        if ($this->phpVersionId < 80400) {
            $rules['session.sid_length'] = '48';
        }

        if ($this->phpVersionId < 80500) {
            $rules['report_memleaks'] = 'On';
        }

        return $rules;
    }
}
