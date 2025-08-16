<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\TextUI\Command;

use const PHP_EOL;
use function extension_loaded;
use function ini_get;
use function max;
use function sprintf;
use function strlen;
use PHPUnit\Runner\Version;
use PHPUnit\Util\Color;
use SebastianBergmann\Environment\Console;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class CheckPhpConfigurationCommand implements Command
{
    /**
     * @var non-empty-array<non-empty-string, array{expectedValue: non-empty-string, valueForConfiguration: non-empty-string, requiredExtensions: list<non-empty-string>}>
     */
    private const SETTINGS = [
        'display_errors' => [
            'expectedValue'         => '1',
            'valueForConfiguration' => 'On',
            'requiredExtensions'    => [],
        ],
        'display_startup_errors' => [
            'expectedValue'         => '1',
            'valueForConfiguration' => 'On',
            'requiredExtensions'    => [],
        ],
        'error_reporting' => [
            'expectedValue'         => '-1',
            'valueForConfiguration' => '-1',
            'requiredExtensions'    => [],
        ],
        'xdebug.show_exception_trace' => [
            'expectedValue'         => '0',
            'valueForConfiguration' => '0',
            'requiredExtensions'    => ['xdebug'],
        ],
        'zend.assertions' => [
            'expectedValue'         => '1',
            'valueForConfiguration' => '1',
            'requiredExtensions'    => [],
        ],
        'assert.exception' => [
            'expectedValue'         => '1',
            'valueForConfiguration' => '1',
            'requiredExtensions'    => [],
        ],
        'memory_limit' => [
            'expectedValue'         => '-1',
            'valueForConfiguration' => '-1',
            'requiredExtensions'    => [],
        ],
    ];
    private readonly bool $colorize;

    public function __construct()
    {
        $this->colorize = (new Console)->hasColorSupport();
    }

    public function execute(): Result
    {
        $lines         = [];
        $shellExitCode = 0;

        foreach (self::SETTINGS as $name => $setting) {
            foreach ($setting['requiredExtensions'] as $extension) {
                if (!extension_loaded($extension)) {
                    // @codeCoverageIgnoreStart
                    continue 2;
                    // @codeCoverageIgnoreEnd
                }
            }

            $actualValue = ini_get($name);

            if ($actualValue === $setting['expectedValue']) {
                $check = $this->ok();
            } else {
                $check         = $this->notOk($actualValue);
                $shellExitCode = 1;
            }

            $lines[] = [
                sprintf(
                    '%s = %s',
                    $name,
                    $setting['valueForConfiguration'],
                ),
                $check,
            ];
        }

        $maxLength = 0;

        foreach ($lines as $line) {
            $maxLength = max($maxLength, strlen($line[0]));
        }

        $buffer = sprintf(
            'Checking whether PHP is configured according to https://docs.phpunit.de/en/%s/installation.html#configuring-php-for-development' . PHP_EOL . PHP_EOL,
            Version::series(),
        );

        foreach ($lines as $line) {
            $buffer .= sprintf(
                '%-' . $maxLength . 's ... %s' . PHP_EOL,
                $line[0],
                $line[1],
            );
        }

        return Result::from($buffer, $shellExitCode);
    }

    /**
     * @return non-empty-string
     */
    private function ok(): string
    {
        if (!$this->colorize) {
            return 'ok';
        }

        // @codeCoverageIgnoreStart
        return Color::colorizeTextBox('fg-green, bold', 'ok');
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return non-empty-string
     */
    private function notOk(string $actualValue): string
    {
        $message = sprintf('not ok (%s)', $actualValue);

        if (!$this->colorize) {
            return $message;
        }

        // @codeCoverageIgnoreStart
        return Color::colorizeTextBox('fg-red, bold', $message);
        // @codeCoverageIgnoreEnd
    }
}
