<?php
namespace Aws\Credentials;

use Aws\Identity\AwsCredentialIdentity;

/**
 * Basic implementation of the AWS Credentials interface that allows callers to
 * pass in the AWS Access Key and AWS Secret Access Key in the constructor.
 */
class Credentials extends AwsCredentialIdentity implements
    CredentialsInterface,
    \Serializable
{
    private $key;
    private $secret;
    private $token;
    private $expires;
    private $accountId;
    private $source;

    /**
     * Constructs a new BasicAWSCredentials object, with the specified AWS
     * access key and AWS secret key
     *
     * @param string $key     AWS access key ID
     * @param string $secret  AWS secret access key
     * @param string $token   Security token to use
     * @param int    $expires UNIX timestamp for when credentials expire
     */
    public function __construct(
        $key,
        $secret,
        $token = null,
        $expires = null,
        $accountId = null,
        $source = CredentialSources::STATIC
    )
    {
        $this->key = trim((string) $key);
        $this->secret = trim((string) $secret);
        $this->token = $token;
        $this->expires = $expires;
        $this->accountId = $accountId;
        $this->source = $source ?? CredentialSources::STATIC;
    }

    public static function __set_state(array $state)
    {
        return new self(
            $state['key'],
            $state['secret'],
            $state['token'],
            $state['expires'],
            $state['accountId'],
            $state['source'] ?? null
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

    public function getAccountId()
    {
        return $this->accountId;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function toArray()
    {
        return [
            'key'     => $this->key,
            'secret'  => $this->secret,
            'token'   => $this->token,
            'expires' => $this->expires,
            'accountId' =>  $this->accountId,
            'source' => $this->source
        ];
    }

    public function serialize()
    {
        return json_encode($this->__serialize());
    }

    public function unserialize($serialized)
    {
        $data = json_decode($serialized, true);

        $this->__unserialize($data);
    }

    public function __serialize()
    {
        return $this->toArray();
    }

    public function __unserialize($data)
    {
        $this->key = $data['key'];
        $this->secret = $data['secret'];
        $this->token = $data['token'];
        $this->expires = $data['expires'];
        $this->accountId = $data['accountId'] ?? null;
        $this->source = $data['source'] ?? null;
    }

    /**
     * Internal-only. Used when IMDS is unreachable
     * or returns expires credentials.
     *
     * @internal
     */
    public function extendExpiration() {
        $extension = mt_rand(5, 10);
        $this->expires = time() + $extension * 60;

        $message = <<<EOT
Attempting credential expiration extension due to a credential service 
availability issue. A refresh of these credentials will be attempted again 
after {$extension} minutes.\n
EOT;
        trigger_error($message, E_USER_WARNING);
    }
}
