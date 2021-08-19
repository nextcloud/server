<?php
namespace Aws\Credentials;

/**
 * Basic implementation of the AWS Credentials interface that allows callers to
 * pass in the AWS Access Key and AWS Secret Access Key in the constructor.
 */
class Credentials implements CredentialsInterface, \Serializable
{
    private $key;
    private $secret;
    private $token;
    private $expires;

    /**
     * Constructs a new BasicAWSCredentials object, with the specified AWS
     * access key and AWS secret key
     *
     * @param string $key     AWS access key ID
     * @param string $secret  AWS secret access key
     * @param string $token   Security token to use
     * @param int    $expires UNIX timestamp for when credentials expire
     */
    public function __construct($key, $secret, $token = null, $expires = null)
    {
        $this->key = trim($key);
        $this->secret = trim($secret);
        $this->token = $token;
        $this->expires = $expires;
    }

    public static function __set_state(array $state)
    {
        return new self(
            $state['key'],
            $state['secret'],
            $state['token'],
            $state['expires']
        );
    }

    public function getAccessKeyId()
    {
        return $this->key;
    }

    public function getSecretKey()
    {
        return $this->secret;
    }

    public function getSecurityToken()
    {
        return $this->token;
    }

    public function getExpiration()
    {
        return $this->expires;
    }

    public function isExpired()
    {
        return $this->expires !== null && time() >= $this->expires;
    }

    public function toArray()
    {
        return [
            'key'     => $this->key,
            'secret'  => $this->secret,
            'token'   => $this->token,
            'expires' => $this->expires
        ];
    }

    public function serialize()
    {
        return json_encode($this->toArray());
    }

    public function unserialize($serialized)
    {
        $data = json_decode($serialized, true);

        $this->key = $data['key'];
        $this->secret = $data['secret'];
        $this->token = $data['token'];
        $this->expires = $data['expires'];
    }
}
