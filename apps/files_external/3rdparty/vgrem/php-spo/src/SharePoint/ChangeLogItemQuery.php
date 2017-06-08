<?php


namespace Office365\PHP\Client\SharePoint;
use Office365\PHP\Client\Runtime\ClientValueObject;

/**
 * Class ChangeLogItemQuery
 */
class ChangeLogItemQuery extends ClientValueObject
{

    function __construct()
    {
        $this->ViewName = "";
        $this->Query = "";
        $this->QueryOptions = "";
        $this->Contains = "";
        parent::__construct();
    }


    /**
     * A string that contains either the title or the GUID for the list. When querying the UserInfo table,
     * the string contains UserInfo. Using the GUID results in better performance.
     */
    //public $ListName;

    /**
     * A string that contains the GUID for the view, which determines the view to use for the default view
     * attributes represented by the query, viewFields, and rowLimit parameters. If this argument is not supplied,
     * the default view is assumed. If it is supplied, the value of the query, viewFields,
     * or rowLimit parameter overrides the equivalent setting within the view. For example,
     * if the view specified by the viewFields parameter has a row limit of 100 rows but the rowLimit parameter
     * contains a value of 1000, then 1,000 rows are returned in the response.
     */
    public $ViewName;

    /**
     * A Query element containing the query that determines which records are returned and in what order.
     */
    public $Query;

    /**
     * An XML fragment in the following form that contains separate nodes for the various properties of the SPQuery object.
     */
    public $QueryOptions;

    /**
     * A string that contains the change token for the request. For a description of the format that is used in this string,
     * see Overview of the Change Log. If null is passed, all items in the list are returned.
     */
    public $ChangeToken;


    /**
     * A Contains element that defines custom filtering for the query.
     */
    public $Contains;

}