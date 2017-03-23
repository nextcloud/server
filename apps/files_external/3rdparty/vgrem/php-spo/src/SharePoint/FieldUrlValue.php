<?php
/**
 * Specifies the hyperlink and the description values for FieldURL.
 */

namespace Office365\PHP\Client\SharePoint;


use Office365\PHP\Client\Runtime\ClientValueObject;

class FieldUrlValue extends ClientValueObject
{

    /**
     * Specifies the URI.
     * @var string
     */
    public $Url;

    /**
     * Gets or sets a value that specifies the description for the URI.
     * @var string
     */
    public $Description;

}