<?php

namespace Doctrine\DBAL\Exception;

/**
 * Exception for an unknown table referenced in a statement detected in the driver.
 */
class TableNotFoundException extends DatabaseObjectNotFoundException
{
}
