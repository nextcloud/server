<?php

/**
 * EntityTooLarge
 *
 * This exception is thrown whenever a user tries to upload a file which exceeds hard limitations
 *
 */
class OC_Connector_Sabre_Exception_EntityTooLarge extends Sabre_DAV_Exception {

    /**
     * Returns the HTTP statuscode for this exception
     *
     * @return int
     */
    public function getHTTPCode() {

//        return 413;

	    return 450;
    }

}
