<?php

namespace Doctrine\DBAL\Tools;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\MalformedDsnException;
use SensitiveParameter;

use function array_merge;
use function assert;
use function is_a;
use function is_string;
use function parse_str;
use function parse_url;
use function preg_replace;
use function rawurldecode;
use function str_replace;
use function strpos;
use function substr;

/** @phpstan-import-type Params from DriverManager */
final class DsnParser
{
    /** @var array<string, string|class-string<Driver>> */
    private array $schemeMapping;

    /** @param array<string, string|class-string<Driver>> $schemeMapping An array used to map DSN schemes to DBAL drivers */
    public function __construct(array $schemeMapping = [])
    {
        $this->schemeMapping = $schemeMapping;
    }

    /**
     * @phpstan-return Params
     *
     * @throws MalformedDsnException
     */
    public function parse(
        #[SensitiveParameter]
        string $dsn
    ): array {
        // (pdo-)?sqlite3?:///... => (pdo-)?sqlite3?://localhost/... or else the URL will be invalid
        $url = preg_replace('#^((?:pdo-)?sqlite3?):///#', '$1://localhost/', $dsn);
        assert($url !== null);

        $url = parse_url($url);

        if ($url === false) {
            throw MalformedDsnException::new();
        }

        foreach ($url as $param => $value) {
            if (! is_string($value)) {
                continue;
            }

            $url[$param] = rawurldecode($value);
        }

        $params = [];

        if (isset($url['scheme'])) {
            $params['driver'] = $this->parseDatabaseUrlScheme($url['scheme']);
        }

        if (isset($url['host'])) {
            $params['host'] = $url['host'];
        }

        if (isset($url['port'])) {
            $params['port'] = $url['port'];
        }

        if (isset($url['user'])) {
            $params['user'] = $url['user'];
        }

        if (isset($url['pass'])) {
            $params['password'] = $url['pass'];
        }

        if (isset($params['driver']) && is_a($params['driver'], Driver::class, true)) {
            $params['driverClass'] = $params['driver'];
            unset($params['driver']);
        }

        $params = $this->parseDatabaseUrlPath($url, $params);
        $params = $this->parseDatabaseUrlQuery($url, $params);

        return $params;
    }

    /**
     * Parses the given connection URL and resolves the given connection parameters.
     *
     * Assumes that the connection URL scheme is already parsed and resolved into the given connection parameters
     * via {@see parseDatabaseUrlScheme}.
     *
     * @see parseDatabaseUrlScheme
     *
     * @param mixed[] $url    The URL parts to evaluate.
     * @param mixed[] $params The connection parameters to resolve.
     *
     * @return mixed[] The resolved connection parameters.
     */
    private function parseDatabaseUrlPath(array $url, array $params): array
    {
        if (! isset($url['path'])) {
            return $params;
        }

        $url['path'] = $this->normalizeDatabaseUrlPath($url['path']);

        // If we do not have a known DBAL driver, we do not know any connection URL path semantics to evaluate
        // and therefore treat the path as a regular DBAL connection URL path.
        if (! isset($params['driver'])) {
            return $this->parseRegularDatabaseUrlPath($url, $params);
        }

        if (strpos($params['driver'], 'sqlite') !== false) {
            return $this->parseSqliteDatabaseUrlPath($url, $params);
        }

        return $this->parseRegularDatabaseUrlPath($url, $params);
    }

    /**
     * Normalizes the given connection URL path.
     *
     * @return string The normalized connection URL path
     */
    private function normalizeDatabaseUrlPath(string $urlPath): string
    {
        // Trim leading slash from URL path.
        return substr($urlPath, 1);
    }

    /**
     * Parses the query part of the given connection URL and resolves the given connection parameters.
     *
     * @param mixed[] $url    The connection URL parts to evaluate.
     * @param mixed[] $params The connection parameters to resolve.
     *
     * @return mixed[] The resolved connection parameters.
     */
    private function parseDatabaseUrlQuery(array $url, array $params): array
    {
        if (! isset($url['query'])) {
            return $params;
        }

        $query = [];

        parse_str($url['query'], $query); // simply ingest query as extra params, e.g. charset or sslmode

        return array_merge($params, $query); // parse_str wipes existing array elements
    }

    /**
     * Parses the given regular connection URL and resolves the given connection parameters.
     *
     * Assumes that the "path" URL part is already normalized via {@see normalizeDatabaseUrlPath}.
     *
     * @see normalizeDatabaseUrlPath
     *
     * @param mixed[] $url    The regular connection URL parts to evaluate.
     * @param mixed[] $params The connection parameters to resolve.
     *
     * @return mixed[] The resolved connection parameters.
     */
    private function parseRegularDatabaseUrlPath(array $url, array $params): array
    {
        $params['dbname'] = $url['path'];

        return $params;
    }

    /**
     * Parses the given SQLite connection URL and resolves the given connection parameters.
     *
     * Assumes that the "path" URL part is already normalized via {@see normalizeDatabaseUrlPath}.
     *
     * @see normalizeDatabaseUrlPath
     *
     * @param mixed[] $url    The SQLite connection URL parts to evaluate.
     * @param mixed[] $params The connection parameters to resolve.
     *
     * @return mixed[] The resolved connection parameters.
     */
    private function parseSqliteDatabaseUrlPath(array $url, array $params): array
    {
        if ($url['path'] === ':memory:') {
            $params['memory'] = true;

            return $params;
        }

        $params['path'] = $url['path']; // pdo_sqlite driver uses 'path' instead of 'dbname' key

        return $params;
    }

    /**
     * Parses the scheme part from given connection URL and resolves the given connection parameters.
     *
     * @return string The resolved driver.
     */
    private function parseDatabaseUrlScheme(string $scheme): string
    {
        // URL schemes must not contain underscores, but dashes are ok
        $driver = str_replace('-', '_', $scheme);

        // If the driver is an alias (e.g. "postgres"), map it to the actual name ("pdo-pgsql").
        // Otherwise, let checkParams decide later if the driver exists.
        return $this->schemeMapping[$driver] ?? $driver;
    }
}
