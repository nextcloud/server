<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Portability;

use Doctrine\DBAL\Driver\Middleware\AbstractResultMiddleware;
use Doctrine\DBAL\Driver\Result as ResultInterface;

final class Result extends AbstractResultMiddleware
{
    private Converter $converter;

    /** @internal The result can be only instantiated by the portability connection or statement. */
    public function __construct(ResultInterface $result, Converter $converter)
    {
        parent::__construct($result);

        $this->converter = $converter;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchNumeric()
    {
        return $this->converter->convertNumeric(
            parent::fetchNumeric(),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAssociative()
    {
        return $this->converter->convertAssociative(
            parent::fetchAssociative(),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function fetchOne()
    {
        return $this->converter->convertOne(
            parent::fetchOne(),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllNumeric(): array
    {
        return $this->converter->convertAllNumeric(
            parent::fetchAllNumeric(),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllAssociative(): array
    {
        return $this->converter->convertAllAssociative(
            parent::fetchAllAssociative(),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function fetchFirstColumn(): array
    {
        return $this->converter->convertFirstColumn(
            parent::fetchFirstColumn(),
        );
    }
}
