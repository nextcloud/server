<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\Mysqli\Initializer;

use Doctrine\DBAL\Driver\Mysqli\Exception\InvalidCharset;
use Doctrine\DBAL\Driver\Mysqli\Initializer;
use mysqli;

final class Charset implements Initializer
{
    /** @var string */
    private $charset;

    public function __construct(string $charset)
    {
        $this->charset = $charset;
    }

    public function initialize(mysqli $connection): void
    {
        if ($connection->set_charset($this->charset)) {
            return;
        }

        throw InvalidCharset::fromCharset($connection, $this->charset);
    }
}
