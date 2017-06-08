<?php

namespace Office365\PHP\Client\Runtime\Auth;

use Office365\PHP\Client\Runtime\Utilities\RequestOptions;

interface IAuthenticationContext
{
    public function authenticateRequest(RequestOptions $request);
}