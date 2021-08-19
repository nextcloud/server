<?php

namespace Doctrine\DBAL\Driver\OCI8;

use Doctrine\DBAL\Driver\AbstractOracleDriver;

use const OCI_NO_AUTO_COMMIT;

/**
 * A Doctrine DBAL driver for the Oracle OCI8 PHP extensions.
 */
final class Driver extends AbstractOracleDriver
{
    /**
     * {@inheritdoc}
     *
     * @return Connection
     */
    public function connect(array $params)
    {
        return new Connection(
            $params['user'] ?? '',
            $params['password'] ?? '',
            $this->_constructDsn($params),
            $params['charset'] ?? '',
            $params['sessionMode'] ?? OCI_NO_AUTO_COMMIT,
            $params['persistent'] ?? false
        );
    }

    /**
     * Constructs the Oracle DSN.
     *
     * @param mixed[] $params
     *
     * @return string The DSN.
     */
    protected function _constructDsn(array $params)
    {
        return $this->getEasyConnectString($params);
    }
}
