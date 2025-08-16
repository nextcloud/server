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
use function sort;
use function sprintf;
use function str_starts_with;
use PHPUnit\Framework\TestSuite;
use PHPUnit\TextUI\Configuration\Registry;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class ListGroupsCommand implements Command
{
    private readonly TestSuite $suite;

    public function __construct(TestSuite $suite)
    {
        $this->suite = $suite;
    }

    public function execute(): Result
    {
        $buffer = $this->warnAboutConflictingOptions();
        $buffer .= 'Available test group(s):' . PHP_EOL;

        $groups = $this->suite->groups();
        sort($groups);

        foreach ($groups as $group) {
            if (str_starts_with($group, '__phpunit_')) {
                continue;
            }

            $buffer .= sprintf(
                ' - %s' . PHP_EOL,
                $group,
            );
        }

        return Result::from($buffer);
    }

    private function warnAboutConflictingOptions(): string
    {
        $buffer = '';

        $configuration = Registry::get();

        if ($configuration->hasFilter()) {
            $buffer .= 'The --filter and --list-groups options cannot be combined, --filter is ignored' . PHP_EOL;
        }

        if ($configuration->hasGroups()) {
            $buffer .= 'The --group and --list-groups options cannot be combined, --group is ignored' . PHP_EOL;
        }

        if ($configuration->hasExcludeGroups()) {
            $buffer .= 'The --exclude-group and --list-groups options cannot be combined, --exclude-group is ignored' . PHP_EOL;
        }

        if ($configuration->includeTestSuite() !== '') {
            $buffer .= 'The --testsuite and --list-groups options cannot be combined, --exclude-group is ignored' . PHP_EOL;
        }

        if (!empty($buffer)) {
            $buffer .= PHP_EOL;
        }

        return $buffer;
    }
}
