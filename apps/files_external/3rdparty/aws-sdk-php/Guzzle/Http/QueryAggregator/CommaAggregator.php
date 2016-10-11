<?php

namespace Guzzle\Http\QueryAggregator;

use Guzzle\Http\QueryString;

/**
 * Aggregates nested query string variables using commas
 */
class CommaAggregator implements QueryAggregatorInterface
{
    public function aggregate($key, $value, QueryString $query)
    {
        if ($query->isUrlEncoding()) {
            return array($query->encodeValue($key) => implode(',', array_map(array($query, 'encodeValue'), $value)));
        } else {
            return array($key => implode(',', $value));
        }
    }
}
