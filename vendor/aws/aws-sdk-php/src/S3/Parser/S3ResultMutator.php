<?php

namespace Aws\S3\Parser;

use Aws\CommandInterface;
use Aws\ResultInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface for S3 result mutator implementations.
 * A S3 result mutator is meant for modifying a request
 * result before returning it to the user.
 * One example is if a custom field is needed to be injected
 * into the result or if an existent field needs to be modified.
 * Since the command and the response itself are parameters when
 * invoking the mutators then, this facilitates to make better
 * decisions that may involve validations using the command parameters
 * or response fields, etc.
 *
 * @internal
 */
interface S3ResultMutator
{
    /**
     * @param ResultInterface $result the result object to be modified.
     * @param CommandInterface $command the command that originated the request.
     * @param ResponseInterface $response the response resulting from the request.
     *
     * @return ResultInterface
     */
    public function __invoke(
        ResultInterface $result,
        CommandInterface $command,
        ResponseInterface $response
    ): ResultInterface;
}
