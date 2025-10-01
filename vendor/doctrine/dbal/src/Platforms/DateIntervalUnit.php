<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Platforms;

final class DateIntervalUnit
{
    public const SECOND = 'SECOND';

    public const MINUTE = 'MINUTE';

    public const HOUR = 'HOUR';

    public const DAY = 'DAY';

    public const WEEK = 'WEEK';

    public const MONTH = 'MONTH';

    public const QUARTER = 'QUARTER';

    public const YEAR = 'YEAR';

    /** @codeCoverageIgnore */
    private function __construct()
    {
    }
}
