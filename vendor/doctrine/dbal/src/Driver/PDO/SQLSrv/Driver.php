<?php

namespace Doctrine\DBAL\Driver\PDO\SQLSrv;

use Doctrine\DBAL\Driver\AbstractSQLServerDriver;
use Doctrine\DBAL\Driver\AbstractSQLServerDriver\Exception\PortWithoutHost;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Driver\PDO\Connection as PDOConnection;
use Doctrine\DBAL\Driver\PDO\Exception as PDOException;
use Doctrine\DBAL\Driver\PDO\PDOConnect;
use PDO;
use SensitiveParameter;

use function is_int;
use function sprintf;

final class Driver extends AbstractSQLServerDriver
{
    use PDOConnect;

    /**
     * {@inheritDoc}
     *
     * @return Connection
     */
    public function connect(
        #[SensitiveParameter]
        array $params
    ) {
        $driverOptions = $dsnOptions = [];

        if (isset($params['driverOptions'])) {
            foreach ($params['driverOptions'] as $option => $value) {
                if (is_int($option)) {
                    $driverOptions[$option] = $value;
                } else {
                    $dsnOptions[$option] = $value;
                }
            }
        }

        if (! empty($params['persistent'])) {
            $driverOptions[PDO::ATTR_PERSISTENT] = true;
        }

        $safeParams = $params;
        unset($safeParams['password'], $safeParams['url']);

        try {
            $pdo = $this->doConnect(
                $this->constructDsn($safeParams, $dsnOptions),
                $params['user'] ?? '',
                $params['password'] ?? '',
                $driverOptions,
            );
        } catch (\PDOException $exception) {
            throw PDOException::new($exception);
        }

        return new Connection(new PDOConnection($pdo));
    }

    /**
     * Constructs the Sqlsrv PDO DSN.
     *
     * @param mixed[]  $params
     * @param string[] $connectionOptions
     *
     * @throws Exception
     */
    private function constructDsn(array $params, array $connectionOptions): string
    {
        $dsn = 'sqlsrv:server=';

        if (isset($params['host'])) {
            $dsn .= $params['host'];

            if (isset($params['port'])) {
                $dsn .= ',' . $params['port'];
            }
        } elseif (isset($params['port'])) {
            throw PortWithoutHost::new();
        }

        if (isset($params['dbname'])) {
            $connectionOptions['Database'] = $params['dbname'];
        }

        if (isset($params['MultipleActiveResultSets'])) {
            $connectionOptions['MultipleActiveResultSets'] = $params['MultipleActiveResultSets'] ? 'true' : 'false';
        }

        return $dsn . $this->getConnectionOptionsDsn($connectionOptions);
    }

    /**
     * Converts a connection options array to the DSN
     *
     * @param string[] $connectionOptions
     */
    private function getConnectionOptionsDsn(array $connectionOptions): string
    {
        $connectionOptionsDsn = '';

        foreach ($connectionOptions as $paramName => $paramValue) {
            $connectionOptionsDsn .= sprintf(';%s=%s', $paramName, $paramValue);
        }

        return $connectionOptionsDsn;
    }
}
