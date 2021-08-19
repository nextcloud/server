<?php

namespace Doctrine\DBAL\Driver;

use Doctrine\DBAL\ParameterType;

/**
 * Driver-level statement
 */
interface Statement
{
    /**
     * Binds a value to a corresponding named (not supported by mysqli driver, see comment below) or positional
     * placeholder in the SQL statement that was used to prepare the statement.
     *
     * As mentioned above, the named parameters are not natively supported by the mysqli driver, use executeQuery(),
     * fetchAll(), fetchArray(), fetchColumn(), fetchAssoc() methods to have the named parameter emulated by doctrine.
     *
     * @param string|int $param Parameter identifier. For a prepared statement using named placeholders,
     *                          this will be a parameter name of the form :name. For a prepared statement
     *                          using question mark placeholders, this will be the 1-indexed position of the parameter.
     * @param mixed      $value The value to bind to the parameter.
     * @param int        $type  Explicit data type for the parameter using the {@link ParameterType}
     *                          constants.
     *
     * @return bool TRUE on success or FALSE on failure.
     *
     * @throws Exception
     */
    public function bindValue($param, $value, $type = ParameterType::STRING);

    /**
     * Binds a PHP variable to a corresponding named (not supported by mysqli driver, see comment below) or question
     * mark placeholder in the SQL statement that was use to prepare the statement. Unlike {@link bindValue()},
     * the variable is bound as a reference and will only be evaluated at the time
     * that PDOStatement->execute() is called.
     *
     * As mentioned above, the named parameters are not natively supported by the mysqli driver, use executeQuery(),
     * fetchAll(), fetchArray(), fetchColumn(), fetchAssoc() methods to have the named parameter emulated by doctrine.
     *
     * Most parameters are input parameters, that is, parameters that are
     * used in a read-only fashion to build up the query. Some drivers support the invocation
     * of stored procedures that return data as output parameters, and some also as input/output
     * parameters that both send in data and are updated to receive it.
     *
     * @param string|int $param    Parameter identifier. For a prepared statement using named placeholders,
     *                             this will be a parameter name of the form :name. For a prepared statement using
     *                             question mark placeholders, this will be the 1-indexed position of the parameter.
     * @param mixed      $variable Name of the PHP variable to bind to the SQL statement parameter.
     * @param int        $type     Explicit data type for the parameter using the {@link ParameterType}
     *                             constants.
     * @param int|null   $length   You must specify maxlength when using an OUT bind
     *                             so that PHP allocates enough memory to hold the returned value.
     *
     * @return bool TRUE on success or FALSE on failure.
     *
     * @throws Exception
     */
    public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null);

    /**
     * Executes a prepared statement
     *
     * If the prepared statement included parameter markers, you must either:
     * call {@link bindParam()} to bind PHP variables to the parameter markers:
     * bound variables pass their value as input and receive the output value,
     * if any, of their associated parameter markers or pass an array of input-only
     * parameter values.
     *
     * @param mixed[]|null $params A numeric array of values with as many elements as there are
     *                             bound parameters in the SQL statement being executed.
     *
     * @throws Exception
     */
    public function execute($params = null): Result;
}
