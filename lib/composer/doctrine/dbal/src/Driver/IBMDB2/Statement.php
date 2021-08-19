<?php

namespace Doctrine\DBAL\Driver\IBMDB2;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Driver\IBMDB2\Exception\CannotCopyStreamToStream;
use Doctrine\DBAL\Driver\IBMDB2\Exception\CannotCreateTemporaryFile;
use Doctrine\DBAL\Driver\IBMDB2\Exception\CannotWriteToTemporaryFile;
use Doctrine\DBAL\Driver\IBMDB2\Exception\StatementError;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;

use function assert;
use function db2_bind_param;
use function db2_execute;
use function error_get_last;
use function fclose;
use function fwrite;
use function is_int;
use function is_resource;
use function ksort;
use function stream_copy_to_stream;
use function stream_get_meta_data;
use function tmpfile;

use const DB2_BINARY;
use const DB2_CHAR;
use const DB2_LONG;
use const DB2_PARAM_FILE;
use const DB2_PARAM_IN;

final class Statement implements StatementInterface
{
    /** @var resource */
    private $stmt;

    /** @var mixed[] */
    private $bindParam = [];

    /**
     * Map of LOB parameter positions to the tuples containing reference to the variable bound to the driver statement
     * and the temporary file handle bound to the underlying statement
     *
     * @var mixed[][]
     */
    private $lobs = [];

    /**
     * @internal The statement can be only instantiated by its driver connection.
     *
     * @param resource $stmt
     */
    public function __construct($stmt)
    {
        $this->stmt = $stmt;
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue($param, $value, $type = ParameterType::STRING)
    {
        assert(is_int($param));

        return $this->bindParam($param, $value, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null)
    {
        assert(is_int($param));

        switch ($type) {
            case ParameterType::INTEGER:
                $this->bind($param, $variable, DB2_PARAM_IN, DB2_LONG);
                break;

            case ParameterType::LARGE_OBJECT:
                if (isset($this->lobs[$param])) {
                    [, $handle] = $this->lobs[$param];
                    fclose($handle);
                }

                $handle = $this->createTemporaryFile();
                $path   = stream_get_meta_data($handle)['uri'];

                $this->bind($param, $path, DB2_PARAM_FILE, DB2_BINARY);

                $this->lobs[$param] = [&$variable, $handle];
                break;

            default:
                $this->bind($param, $variable, DB2_PARAM_IN, DB2_CHAR);
                break;
        }

        return true;
    }

    /**
     * @param int   $position Parameter position
     * @param mixed $variable
     *
     * @throws Exception
     */
    private function bind($position, &$variable, int $parameterType, int $dataType): void
    {
        $this->bindParam[$position] =& $variable;

        if (! db2_bind_param($this->stmt, $position, 'variable', $parameterType, $dataType)) {
            throw StatementError::new($this->stmt);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function execute($params = null): ResultInterface
    {
        if ($params === null) {
            ksort($this->bindParam);

            $params = [];

            foreach ($this->bindParam as $column => $value) {
                $params[] = $value;
            }
        }

        foreach ($this->lobs as [$source, $target]) {
            if (is_resource($source)) {
                $this->copyStreamToStream($source, $target);

                continue;
            }

            $this->writeStringToStream($source, $target);
        }

        $result = db2_execute($this->stmt, $params);

        foreach ($this->lobs as [, $handle]) {
            fclose($handle);
        }

        $this->lobs = [];

        if ($result === false) {
            throw StatementError::new($this->stmt);
        }

        return new Result($this->stmt);
    }

    /**
     * @return resource
     *
     * @throws Exception
     */
    private function createTemporaryFile()
    {
        $handle = @tmpfile();

        if ($handle === false) {
            throw CannotCreateTemporaryFile::new(error_get_last());
        }

        return $handle;
    }

    /**
     * @param resource $source
     * @param resource $target
     *
     * @throws Exception
     */
    private function copyStreamToStream($source, $target): void
    {
        if (@stream_copy_to_stream($source, $target) === false) {
            throw CannotCopyStreamToStream::new(error_get_last());
        }
    }

    /**
     * @param resource $target
     *
     * @throws Exception
     */
    private function writeStringToStream(string $string, $target): void
    {
        if (@fwrite($target, $string) === false) {
            throw CannotWriteToTemporaryFile::new(error_get_last());
        }
    }
}
