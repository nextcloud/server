<?php

namespace Doctrine\DBAL\Exception;

/**
 * Exception for a write operation attempt on a read-only database element detected in the driver.
 */
class ReadOnlyException extends ServerException
{
}
