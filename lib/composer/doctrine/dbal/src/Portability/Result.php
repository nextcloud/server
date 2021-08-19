<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Portability;

use Doctrine\DBAL\Driver\Result as ResultInterface;

final class Result implements ResultInterface
{
    /** @var ResultInterface */
    private $result;

    /** @var Converter */
    private $converter;

    /**
     * @internal The result can be only instantiated by the portability connection or statement.
     */
    public function __construct(ResultInterface $result, Converter $converter)
    {
        $this->result    = $result;
        $this->converter = $converter;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchNumeric()
    {
        return $this->converter->convertNumeric(
            $this->result->fetchNumeric()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAssociative()
    {
        return $this->converter->convertAssociative(
            $this->result->fetchAssociative()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function fetchOne()
    {
        return $this->converter->convertOne(
            $this->result->fetchOne()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllNumeric(): array
    {
        return $this->converter->convertAllNumeric(
            $this->result->fetchAllNumeric()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllAssociative(): array
    {
        return $this->converter->convertAllAssociative(
            $this->result->fetchAllAssociative()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function fetchFirstColumn(): array
    {
        return $this->converter->convertFirstColumn(
            $this->result->fetchFirstColumn()
        );
    }

    public function rowCount(): int
    {
        return $this->result->rowCount();
    }

    public function columnCount(): int
    {
        return $this->result->columnCount();
    }

    public function free(): void
    {
        $this->result->free();
    }
}
