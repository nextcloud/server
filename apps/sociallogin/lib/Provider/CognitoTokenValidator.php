<?php
namespace OCA\SocialLogin\Provider;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

class CognitoTokenValidator {
    

    

    // Function to fetch JWKs from AWS Cognito
    private function fetchJwks($jwksUrl) {
        $jwks = file_get_contents($jwksUrl);
        if ($jwks === false) {
            throw new Exception('Could not fetch JWKs');
        }
        return json_decode($jwks, true);
    }

    // Function to verify JWT token
    public function verifyToken($token,$jwksUrl) {


        $jwks = $this->fetchJwks($jwksUrl);
        // Decode the JWT to get the header and extract the kid
        $header = JWT::decode($token, new \Firebase\JWT\Key('dummy', 'none')); // Use a dummy key for header-only decoding
    $kid = $header->kid;

        // Find the matching JWK by kid
        $key = null;
        foreach ($this->jwks['keys'] as $jwk) {
            if ($jwk['kid'] === $kid) {
                $key = $jwk;
                break;
            }
        }

        if ($key === null) {
            throw new Exception('Matching key not found');
        }

        // Convert JWK to PEM
        $pem = $this->jwkToPem($key);

        // Decode and verify the token
        try {
            $decoded = JWT::decode($token, $pem, ['RS256']);
            return $decoded; // Return decoded token if valid
        } catch (Exception $e) {
            return false; // Token is invalid
        }
    }

    // Function to convert JWK to PEM format
    private function jwkToPem($jwk) {
        $n = base64_decode(strtr($jwk['n'], '-_', '+/'));
        $e = base64_decode(strtr($jwk['e'], '-_', '+/'));

        $modulusHex = bin2hex($n);
        $exponentHex = bin2hex($e);

        $publicKey = openssl_pkey_get_public(sprintf(
            '-----BEGIN PUBLIC KEY-----' . PHP_EOL .
            chunk_split(base64_encode(pack('H*', sprintf('%02s', '00') . $modulusHex . $exponentHex)), 64, PHP_EOL) .
            '-----END PUBLIC KEY-----'
        ));

        if ($publicKey === false) {
            throw new Exception('Invalid JWK');
        }

        return $publicKey;
    }
}
