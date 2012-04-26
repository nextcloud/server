<?php

/**
 * IQuota interface
 *
 * Implement this interface to add the ability to return quota information. The ObjectTree
 * will check for quota information on any given node. If the information is not available it will
 * attempt to fetch the information from the root node.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface Sabre_DAV_IQuota extends Sabre_DAV_ICollection {

    /**
     * Returns the quota information
     *
     * This method MUST return an array with 2 values, the first being the total used space,
     * the second the available space (in bytes)
     */
    function getQuotaInfo();

}

