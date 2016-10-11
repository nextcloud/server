<?php

namespace Guzzle\Http\QueryAggregator;

use Guzzle\Http\QueryString;

/**
 * Interface used for aggregating nested query string variables into a flattened array of key value pairs
 */
interface QueryAggregatorInterface
{
    /**
     * Aggregate multi-valued parameters into a flattened associative array
     *
     * @param string      $key   The name of the query string parameter
     * @param array       $value The values of the parameter
     * @param QueryString $query The query string that is being aggregated
     *
     * @return array Returns an array of the combined values
     */
    public function aggregate($key, $value, QueryString $query);
}
