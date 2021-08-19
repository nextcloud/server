<?php

namespace Doctrine\DBAL\Exception;

/**
 * Exception for a foreign key constraint violation detected in the driver.
 *
 * @psalm-immutable
 */
class ForeignKeyConstraintViolationException extends ConstraintViolationException
{
}
