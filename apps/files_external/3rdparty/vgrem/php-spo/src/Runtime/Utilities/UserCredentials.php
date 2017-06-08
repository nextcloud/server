<?php


namespace Office365\PHP\Client\Runtime\Utilities;


class UserCredentials
{

    public function __construct($username,$password)
    {
        $this->Username = $username;
        $this->Password = $password;
    }

    public function toString(){
        return $this->Username . ':' . $this->Password;
    }

    /**
     * @var string
     */
    public $Username;


    /**
     * @var string
     */
    public $Password;

}