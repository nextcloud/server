<?php

namespace Aws\Signature;

use Aws\Credentials\Credentials;
use Aws\Credentials\CredentialsInterface;
use Psr\Http\Message\RequestInterface;

class S3ExpressSignature extends S3SignatureV4
{
    public function signRequest(
        RequestInterface $request,
        CredentialsInterface $credentials,
        $signingService = 's3express'
    ) {
        $request = $this->modifyTokenHeaders($request, $credentials);
        $credentials = $this->getSigningCredentials($credentials);
        return parent::signRequest($request, $credentials, $signingService);
    }

    public function presign(RequestInterface $request, CredentialsInterface $credentials, $expires, array $options = [])
    {
        $request = $this->modifyTokenHeaders($request, $credentials);
        $credentials = $this->getSigningCredentials($credentials);
        return parent::presign($request, $credentials, $expires, $options);
    }

    private function modifyTokenHeaders(
        RequestInterface $request,
        CredentialsInterface $credentials
    ) {
        //The x-amz-security-token header is not supported by s3 express
        $request = $request->withoutHeader('X-Amz-Security-Token');
        return $request->withHeader(
            'x-amz-s3session-token',
            $credentials->getSecurityToken()
        );
    }

    private function getSigningCredentials(CredentialsInterface $credentials)
    {
        return new Credentials(
            $credentials->getAccessKeyId(),
            $credentials->getSecretKey()
        );
    }
}
