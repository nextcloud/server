<?php

namespace Office365\PHP\Client\Runtime\Auth;


use Office365\PHP\Client\Runtime\Utilities\Requests;

/**
 * Live Connect implements the OAuth 2.0 protocol to authenticate users
 */
class LiveConnectProvider extends BaseTokenProvider
{
    private static $StsUrl = 'https://login.live.com/oauth20_token.srf';

    public function acquireToken($parameters)
    {
        throw  new \Exception("Not implemented");
    }
}