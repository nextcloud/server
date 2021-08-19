<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\OCI8;

use Doctrine\DBAL\Driver\FetchUtils;
use Doctrine\DBAL\Driver\Result as ResultInterface;

use function oci_cancel;
use function oci_fetch_all;
use function oci_fetch_array;
use function oci_num_fields;
use function oci_num_rows;

use const OCI_ASSOC;
use const OCI_FETCHSTATEMENT_BY_COLUMN;
use const OCI_FETCHSTATEMENT_BY_ROW;
use const OCI_NUM;
use const OCI_RETURN_LOBS;
use const OCI_RETURN_NULLS;

final class Result implements ResultInterface
{
    /** @var resource */
    private $statement;

    /**
     * @internal The result can be only instantiated by its driver connection or statement.
     *
     * @param resource $statement
     */
    public function __construct($statement)
    {
        $this->statement = $statement;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchNumeric()
    {
        return $this->fetch(OCI_NUM);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAssociative()
    {
        return $this->fetch(OCI_ASSOC);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchOne()
    {
        return FetchUtils::fetchOne($this);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllNumeric(): array
    {
        return $this->fetchAll(OCI_NUM, OCI_FETCHSTATEMENT_BY_ROW);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllAssociative(): array
    {
        return $this->fetchAll(OCI_ASSOC, OCI_FETCHSTATEMENT_BY_ROW);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchFirstColumn(): array
    {
        return $this->fetchAll(OCI_NUM, OCI_FETCHSTATEMENT_BY_COLUMN)[0];
    }

    public function rowCount(): int
    {
        $count = oci_num_rows($this->statement);

        if ($count !== false) {
            return $count;
        }

        return 0;
    }

    public function columnCount(): int
    {
        $count = oci_num_fields($this->statement);

        if ($count !== false) {
            return $count;
        }

        return 0;
    }

    public function free(): void
    {
        oci_cancel($this->statement);
    }

    /**
     * @return mixed|false
     */
    private function fetch(int $mode)
    {
        return oci_fetch_array(
            $this->statement,
            $mode | OCI_RETURN_NULLS | OCI_RETURN_LOBS
        );
    }

    /**
     * @return array<mixed>
     */
    private function fetchAll(int $mode, int $fetchStructure): array
    {
        oci_fetch_all(
            $this->statement,
            $result,
            0,
            -1,
            $mode | OCI_RETURN_NULLS | $fetchStructure | OCI_RETURN_LOBS
        );

        return $result;
    }
}
