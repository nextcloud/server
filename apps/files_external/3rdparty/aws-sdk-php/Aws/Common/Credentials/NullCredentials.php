<?php
namespace Aws\Common\Credentials;

/**
 * A blank set of credentials. AWS clients must be provided credentials, but
 * there are some types of requests that do not need authentication. This class
 * can be used to pivot on that scenario, and also serve as a mock credentials
 * object when testing
 *
 * @codeCoverageIgnore
 */
class NullCredentials implements CredentialsInterface
{
    public function getAccessKeyId()
    {
        return '';
    }

    public function getSecretKey()
    {
        return '';
    }

    public function getSecurityToken()
    {
        return null;
    }

    public function getExpiration()
    {
        return null;
    }

    public function isExpired()
    {
        return false;
    }

    public function serialize()
    {
        return 'N;';
    }

    public function unserialize($serialized)
    {
        // Nothing to do here.
    }

    public function setAccessKeyId($key)
    {
        // Nothing to do here.
    }

    public function setSecretKey($secret)
    {
        // Nothing to do here.
    }

    public function setSecurityToken($token)
    {
        // Nothing to do here.
    }

    public function setExpiration($timestamp)
    {
        // Nothing to do here.
    }
}
