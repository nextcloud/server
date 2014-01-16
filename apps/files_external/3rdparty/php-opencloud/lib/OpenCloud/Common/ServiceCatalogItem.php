<?php

namespace OpenCloud\Common;

/**
 * Holds information on a single service from the Service Catalog
 */
class ServiceCatalogItem 
{
	
    public function __construct($info = array()) 
    {
		foreach($info as $key => $value) {
			$this->$key = $value;
        }
	}

}
