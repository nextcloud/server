<?php

namespace Doctrine\DBAL\Exception;

/**
 * Exception for an already existing table referenced in a statement detected in the driver.
 */
class TableExistsException extends DatabaseObjectExistsException
{
}
