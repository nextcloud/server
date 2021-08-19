<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\Mysqli;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Driver\FetchUtils;
use Doctrine\DBAL\Driver\Mysqli\Exception\StatementError;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use mysqli_stmt;
use stdClass;

use function array_combine;
use function array_fill;
use function array_map;
use function assert;
use function count;
use function is_array;

final class Result implements ResultInterface
{
    /** @var mysqli_stmt */
    private $statement;

    /**
     * Whether the statement result has columns. The property should be used only after the result metadata
     * has been fetched ({@see $metadataFetched}). Otherwise, the property value is undetermined.
     *
     * @var bool
     */
    private $hasColumns = false;

    /**
     * Mapping of statement result column indexes to their names. The property should be used only
     * if the statement result has columns ({@see $hasColumns}). Otherwise, the property value is undetermined.
     *
     * @var array<int,string>
     */
    private $columnNames = [];

    /** @var mixed[] */
    private $boundValues = [];

    /**
     * @internal The result can be only instantiated by its driver connection or statement.
     *
     * @throws Exception
     */
    public function __construct(mysqli_stmt $statement)
    {
        $this->statement = $statement;

        $meta = $statement->result_metadata();

        if ($meta === false) {
            return;
        }

        $this->hasColumns = true;

        $fields = $meta->fetch_fields();
        assert(is_array($fields));

        $this->columnNames = array_map(static function (stdClass $field): string {
            return $field->name;
        }, $fields);

        $meta->free();

        // Store result of every execution which has it. Otherwise it will be impossible
        // to execute a new statement in case if the previous one has non-fetched rows
        // @link http://dev.mysql.com/doc/refman/5.7/en/commands-out-of-sync.html
        $this->statement->store_result();

        // Bind row values _after_ storing the result. Otherwise, if mysqli is compiled with libmysql,
        // it will have to allocate as much memory as it may be needed for the given column type
        // (e.g. for a LONGBLOB column it's 4 gigabytes)
        // @link https://bugs.php.net/bug.php?id=51386#1270673122
        //
        // Make sure that the values are bound after each execution. Otherwise, if free() has been
        // previously called on the result, the values are unbound making the statement unusable.
        //
        // It's also important that row values are bound after _each_ call to store_result(). Otherwise,
        // if mysqli is compiled with libmysql, subsequently fetched string values will get truncated
        // to the length of the ones fetched during the previous execution.
        $this->boundValues = array_fill(0, count($this->columnNames), null);

        $refs = [];
        foreach ($this->boundValues as &$value) {
            $refs[] =& $value;
        }

        if (! $this->statement->bind_result(...$refs)) {
            throw StatementError::new($this->statement);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fetchNumeric()
    {
        $ret = $this->statement->fetch();

        if ($ret === false) {
            throw StatementError::new($this->statement);
        }

        if ($ret === null) {
            return false;
        }

        $values = [];

        foreach ($this->boundValues as $v) {
            $values[] = $v;
        }

        return $values;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAssociative()
    {
        $values = $this->fetchNumeric();

        if ($values === false) {
            return false;
        }

        $row = array_combine($this->columnNames, $values);
        assert(is_array($row));

        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchOne()
    {
        return FetchUtils::fetchOne($this);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllNumeric(): array
    {
        return FetchUtils::fetchAllNumeric($this);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllAssociative(): array
    {
        return FetchUtils::fetchAllAssociative($this);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchFirstColumn(): array
    {
        return FetchUtils::fetchFirstColumn($this);
    }

    public function rowCount(): int
    {
        if ($this->hasColumns) {
            return $this->statement->num_rows;
        }

        return $this->statement->affected_rows;
    }

    public function columnCount(): int
    {
        return $this->statement->field_count;
    }

    public function free(): void
    {
        $this->statement->free_result();
    }
}
