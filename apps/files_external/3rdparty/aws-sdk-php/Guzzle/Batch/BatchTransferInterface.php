<?php

namespace Guzzle\Batch;

/**
 * Interface used for transferring batches of items
 */
interface BatchTransferInterface
{
    /**
     * Transfer an array of items
     *
     * @param array $batch Array of items to transfer
     */
    public function transfer(array $batch);
}
