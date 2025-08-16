<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\TextUI\XmlConfiguration\CodeCoverage\Report;

use PHPUnit\TextUI\Configuration\File;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 *
 * @psalm-immutable
 */
final class Crap4j
{
    private readonly File $target;
    private readonly int $threshold;

    public function __construct(File $target, int $threshold)
    {
        $this->target    = $target;
        $this->threshold = $threshold;
    }

    public function target(): File
    {
        return $this->target;
    }

    public function threshold(): int
    {
        return $this->threshold;
    }
}
