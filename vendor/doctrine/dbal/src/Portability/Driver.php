<?php

namespace Doctrine\DBAL\Portability;

use Doctrine\DBAL\ColumnCase;
use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use LogicException;
use PDO;
use SensitiveParameter;

use function method_exists;

final class Driver extends AbstractDriverMiddleware
{
    private int $mode;

    /** @var 0|ColumnCase::LOWER|ColumnCase::UPPER */
    private int $case;

    /**
     * @param 0|ColumnCase::LOWER|ColumnCase::UPPER $case Determines how the column case will be treated.
     *                                                    0: The case will be left as is in the database.
     *                                                    {@see ColumnCase::LOWER}: The case will be lowercased.
     *                                                    {@see ColumnCase::UPPER}: The case will be uppercased.
     */
    public function __construct(DriverInterface $driver, int $mode, int $case)
    {
        parent::__construct($driver);

        $this->mode = $mode;
        $this->case = $case;
    }

    /**
     * {@inheritDoc}
     */
    public function connect(
        #[SensitiveParameter]
        array $params
    ) {
        $connection = parent::connect($params);

        $portability = (new OptimizeFlags())(
            $this->getDatabasePlatform(),
            $this->mode,
        );

        $case = null;

        if ($this->case !== 0 && ($portability & Connection::PORTABILITY_FIX_CASE) !== 0) {
            $nativeConnection = null;
            if (method_exists($connection, 'getNativeConnection')) {
                try {
                    $nativeConnection = $connection->getNativeConnection();
                } catch (LogicException $e) {
                }
            }

            if ($nativeConnection instanceof PDO) {
                $portability &= ~Connection::PORTABILITY_FIX_CASE;
                $nativeConnection->setAttribute(
                    PDO::ATTR_CASE,
                    $this->case === ColumnCase::LOWER ? PDO::CASE_LOWER : PDO::CASE_UPPER,
                );
            } else {
                $case = $this->case === ColumnCase::LOWER ? Converter::CASE_LOWER : Converter::CASE_UPPER;
            }
        }

        $convertEmptyStringToNull = ($portability & Connection::PORTABILITY_EMPTY_TO_NULL) !== 0;
        $rightTrimString          = ($portability & Connection::PORTABILITY_RTRIM) !== 0;

        if (! $convertEmptyStringToNull && ! $rightTrimString && $case === null) {
            return $connection;
        }

        return new Connection(
            $connection,
            new Converter($convertEmptyStringToNull, $rightTrimString, $case),
        );
    }
}
