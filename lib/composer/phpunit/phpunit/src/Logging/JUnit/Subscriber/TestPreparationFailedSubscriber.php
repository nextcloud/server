<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Logging\JUnit;

use PHPUnit\Event\InvalidArgumentException;
use PHPUnit\Event\Test\PreparationFailed;
use PHPUnit\Event\Test\PreparationFailedSubscriber;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class TestPreparationFailedSubscriber extends Subscriber implements PreparationFailedSubscriber
{
    /**
     * @throws InvalidArgumentException
     */
    public function notify(PreparationFailed $event): void
    {
        $this->logger()->testPreparationFailed();
    }
}
