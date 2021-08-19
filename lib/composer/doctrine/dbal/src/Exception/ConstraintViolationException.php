<?php

namespace Doctrine\DBAL\Exception;

/**
 * Base class for all constraint violation related errors detected in the driver.
 *
 * @psalm-immutable
 */
class ConstraintViolationException extends ServerException
{
}
