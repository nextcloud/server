<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\Mysqli\Initializer;

use Doctrine\DBAL\Driver\Mysqli\Exception\InvalidCharset;
use Doctrine\DBAL\Driver\Mysqli\Initializer;
use mysqli;
use mysqli_sql_exception;

final class Charset implements Initializer
{
    private string $charset;

    public function __construct(string $charset)
    {
        $this->charset = $charset;
    }

    public function initialize(mysqli $connection): void
    {
        try {
            $success = $connection->set_charset($this->charset);
        } catch (mysqli_sql_exception $e) {
            throw InvalidCharset::upcast($e, $this->charset);
        }

        if ($success) {
            return;
        }

        throw InvalidCharset::fromCharset($connection, $this->charset);
    }
}
