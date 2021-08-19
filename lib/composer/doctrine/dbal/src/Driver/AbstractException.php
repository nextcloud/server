<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver;

use Exception as BaseException;
use Throwable;

/**
 * Base implementation of the {@link Exception} interface.
 *
 * @internal
 *
 * @psalm-immutable
 */
abstract class AbstractException extends BaseException implements Exception
{
    /**
     * The SQLSTATE of the driver.
     *
     * @var string|null
     */
    private $sqlState;

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
     * {@inheritdoc}
     */
    public function getSQLState()
    {
        return $this->sqlState;
    }
}
