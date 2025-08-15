<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Runner\ResultCache;

use PHPUnit\Framework\TestStatus\TestStatus;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
interface ResultCache
{
    public function setStatus(string $id, TestStatus $status): void;

    public function status(string $id): TestStatus;

    public function setTime(string $id, float $time): void;

    public function time(string $id): float;

    public function load(): void;

    public function persist(): void;
}
