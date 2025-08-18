<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Metadata;

/**
 * @psalm-immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */
final class Group extends Metadata
{
    /**
     * @psalm-var non-empty-string
     */
    private readonly string $groupName;

    /**
     * @psalm-param 0|1 $level
     * @psalm-param non-empty-string $groupName
     */
    protected function __construct(int $level, string $groupName)
    {
        parent::__construct($level);

        $this->groupName = $groupName;
    }

    /**
     * @psalm-assert-if-true Group $this
     */
    public function isGroup(): bool
    {
        return true;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function groupName(): string
    {
        return $this->groupName;
    }
}
