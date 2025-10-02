<?php

namespace Doctrine\DBAL\Portability;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as DriverStatement;

/**
 * Portability wrapper for a Statement.
 */
final class Statement extends AbstractStatementMiddleware
{
    private Converter $converter;

    /**
     * Wraps <tt>Statement</tt> and applies portability measures.
     */
    public function __construct(DriverStatement $stmt, Converter $converter)
    {
        parent::__construct($stmt);

        $this->converter = $converter;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($params = null): ResultInterface
    {
        return new Result(
            parent::execute($params),
            $this->converter,
        );
    }
}
