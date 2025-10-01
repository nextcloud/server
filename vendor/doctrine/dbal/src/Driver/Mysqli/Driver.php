<?php

namespace Doctrine\DBAL\Driver\Mysqli;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Doctrine\DBAL\Driver\Mysqli\Exception\ConnectionFailed;
use Doctrine\DBAL\Driver\Mysqli\Exception\HostRequired;
use Doctrine\DBAL\Driver\Mysqli\Initializer\Charset;
use Doctrine\DBAL\Driver\Mysqli\Initializer\Options;
use Doctrine\DBAL\Driver\Mysqli\Initializer\Secure;
use Generator;
use mysqli;
use mysqli_sql_exception;
use SensitiveParameter;

final class Driver extends AbstractMySQLDriver
{
    /**
     * {@inheritDoc}
     *
     * @return Connection
     */
    public function connect(
        #[SensitiveParameter]
        array $params
    ) {
        if (! empty($params['persistent'])) {
            if (! isset($params['host'])) {
                throw HostRequired::forPersistentConnection();
            }

            $host = 'p:' . $params['host'];
        } else {
            $host = $params['host'] ?? null;
        }

        $connection = new mysqli();

        foreach ($this->compilePreInitializers($params) as $initializer) {
            $initializer->initialize($connection);
        }

        try {
            $success = @$connection->real_connect(
                $host,
                $params['user'] ?? null,
                $params['password'] ?? null,
                $params['dbname'] ?? null,
                $params['port'] ?? null,
                $params['unix_socket'] ?? null,
                $params['driverOptions'][Connection::OPTION_FLAGS] ?? 0,
            );
        } catch (mysqli_sql_exception $e) {
            throw ConnectionFailed::upcast($e);
        }

        if (! $success) {
            throw ConnectionFailed::new($connection);
        }

        foreach ($this->compilePostInitializers($params) as $initializer) {
            $initializer->initialize($connection);
        }

        return new Connection($connection);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return Generator<int, Initializer>
     */
    private function compilePreInitializers(
        #[SensitiveParameter]
        array $params
    ): Generator {
        unset($params['driverOptions'][Connection::OPTION_FLAGS]);

        if (isset($params['driverOptions']) && $params['driverOptions'] !== []) {
            yield new Options($params['driverOptions']);
        }

        if (
            ! isset($params['ssl_key']) &&
            ! isset($params['ssl_cert']) &&
            ! isset($params['ssl_ca']) &&
            ! isset($params['ssl_capath']) &&
            ! isset($params['ssl_cipher'])
        ) {
            return;
        }

        yield new Secure(
            $params['ssl_key']    ?? '',
            $params['ssl_cert']   ?? '',
            $params['ssl_ca']     ?? '',
            $params['ssl_capath'] ?? '',
            $params['ssl_cipher'] ?? '',
        );
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return Generator<int, Initializer>
     */
    private function compilePostInitializers(
        #[SensitiveParameter]
        array $params
    ): Generator {
        if (! isset($params['charset'])) {
            return;
        }

        yield new Charset($params['charset']);
    }
}
