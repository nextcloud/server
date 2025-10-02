<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Type;

use DateTimeImmutable;

/**
 * Interface to mark types that encode a time as a string.
 */
interface TimeType extends StringType
{
    /**
     * Get the date and time.
     */
    public function dateTime(): DateTimeImmutable;
}
