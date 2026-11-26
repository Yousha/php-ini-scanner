<?php

declare(strict_types=1);

namespace Yousha\PhpIniScanner\Tests\Output;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Yousha\PhpIniScanner\Output\ConsoleRenderer;

final class ConsoleRendererTest extends TestCase
{
    public function testRendererDrawsPassingAndFailingRows(): void
    {
        $output = new BufferedOutput();
        $renderer = new ConsoleRenderer();
        $renderer->render($output, [
            ['setting' => 'memory_limit', 'expected' => '128M', 'current' => '128M', 'pass' => true],
            ['setting' => 'display_errors', 'expected' => 'off', 'current' => 'on', 'pass' => false],
        ], 'Production');

        $content = $output->fetch();

        self::assertStringContainsString('memory_limit', $content);
        self::assertStringContainsString('PASS', $content);
        self::assertStringContainsString('FAIL', $content);
        self::assertStringContainsString('Checked: 2', $content);
    }

    public function testRendererEscapesMarkupInConfigurationValues(): void
    {
        $output = new BufferedOutput();
        $renderer = new ConsoleRenderer();
        $renderer->render($output, [
            [
                'setting' => 'custom_setting',
                'expected' => '</error><error>injected</error>',
                'current' => '<unknown>untrusted</unknown>',
                'pass' => false,
            ],
        ], 'Production');

        $content = $output->fetch();

        self::assertStringContainsString('<unknown>untrusted</unknown>', $content);
        self::assertStringContainsString('</error>', $content);
    }
}
