<?php
/**
 * HTTP OAuth Consumer
 * 
 * Adapted from halldirector's code in 
 * http://code.google.com/p/dropbox-php/issues/detail?id=36#c5
 * 
 * @package Dropbox
 * @copyright Copyright (C) 2011 Joe Constant / halldirector. All rights reserved.
 * @author Joe Constant / halldirector
 * @license http://code.google.com/p/dropbox-php/wiki/License MIT
 */

require_once 'HTTP/OAuth.php';
require_once 'HTTP/OAuth/Consumer.php';

/* 
 * This class is to help work around aomw ssl issues.
 */
class Dropbox_OAuth_Consumer_Dropbox extends  HTTP_OAuth_Consumer
{
    public function getOAuthConsumerRequest()
    {
        if (!$this->consumerRequest instanceof HTTP_OAuth_Consumer_Request) {
            $this->consumerRequest = new HTTP_OAuth_Consumer_Request;
        }
        
        // TODO: Change this and add in code to validate the SSL cert.
        // see https://github.com/bagder/curl/blob/master/lib/mk-ca-bundle.pl
        $this->consumerRequest->setConfig(array(
                    'ssl_verify_peer' => false, 
                    'ssl_verify_host' => false
                ));
        
        return $this->consumerRequest;
    }
}
