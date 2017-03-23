<?php


namespace Office365\PHP\Client\OutlookServices;


abstract class Item extends OutlookEntity
{


    /**
     * Identifies the version of the outlook object. Every time the event is changed, ChangeKey changes as well.
     * This allows Exchange to apply changes to the correct version of the object.
     * @var string
     */
    public $ChangeKey;


    /**
     * @var array
     */
    public $Categories;


    /**
     * The date and time the message was created.
     * @var string|null
     */
    public $CreatedDateTime;


    /**
     * @var string|null
     */
    public $LastModifiedDateTime;


}