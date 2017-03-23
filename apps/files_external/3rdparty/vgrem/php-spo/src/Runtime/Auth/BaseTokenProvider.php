<?php


namespace Office365\PHP\Client\Runtime\Auth;


abstract class BaseTokenProvider
{
     public abstract function acquireToken($parameters);
     
}