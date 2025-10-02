<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver;

use Exception as BaseException;
use Throwable;

/**
 * Base implementation of the {@see Exception} interface.
 *
 * @internal
 */
abstract class AbstractException extends BaseException implements Exception
{
    /**
     * The SQLSTATE of the driver.
     */
    private ?string $sqlState = null;

    /**
     * @param string         $message  The driver error message.
     * @param string|null    $sqlState The SQLSTATE the driver is in at the time the error occurred, if any.
     * @param int            $code     The driver specific error code if any.
     * @param Throwable|null $previous The previous throwable used for the exception chaining.
     */
    public function __construct($message, $sqlState = null, $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->sqlState = $sqlState;
    }

    /**
     * {@inheritDoc}
     */
    public function getSQLState()
    {
        return $this->sqlState;
    }
}
