<?php


namespace Office365\PHP\Client\Runtime\OData;

/**
 * Enumerates the different kinds of payloads
 */
class ODataPayloadKind
{
    /**
     *
     */
    const Unsupported = -1;

    /**
     *
     */
    const Error = 0;

    /**
     * Specifies a payload kind for writing a collection.
     */
    const Collection = 1;

    /**
     *
     */
    const Entity = 2;

    /**
     *
     */
    const Property = 3;


    /**
     *
     */
    const Parameter = 4;

    /**
     * Specifies the payload kind for writing a batch.
     */
    const Batch = 5;
}