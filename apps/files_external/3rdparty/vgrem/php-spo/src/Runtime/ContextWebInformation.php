<?php

namespace Office365\PHP\Client\Runtime;


/**
 * The context information for a site.
 */
class ContextWebInformation extends ClientValueObject
{
    
    /**
     * The form digest value.
     * @var string
     */
    public $FormDigestValue;


    /**
     * The library version.
     * @var string
     */
    public $LibraryVersion;


    /**
     * The amount of time in seconds that the form digest will timeout.
     * @var int
     */
    public $FormDigestTimeoutSeconds;

    /**
     * The full URL of the site collection context.
     * @var string
     */
    public $SiteFullUrl;

    /**
     * The supported client-side object model request schema version.
     * @var array
     */
    public $SupportedSchemaVersions;


    /**
     * The full URL of the site context.
     * @var string
     */
    public $WebFullUrl;



}