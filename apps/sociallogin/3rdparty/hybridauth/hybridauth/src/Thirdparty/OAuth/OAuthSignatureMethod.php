<?php
/*!
* This file is part of the OAuth PHP Library (https://code.google.com/p/oauth/)
*
* OAuth `PHP' Library is an open source software available under the MIT License.
*/

namespace Hybridauth\Thirdparty\OAuth;

/**
 * Class OAuthSignatureMethod
 *
 * @package Hybridauth\Thirdparty\OAuth
 */
abstract class OAuthSignatureMethod
{
    /**
    * Needs to return the name of the Signature Method (ie HMAC-SHA1)
    *
    * @return string
    */
    abstract public function get_name();

    /**
    * Build up the signature
    * NOTE: The output of this function MUST NOT be urlencoded.
    * the encoding is handled in OAuthRequest when the final
    * request is serialized
    *
    * @param OAuthRequest $request
    * @param OAuthConsumer $consumer
    * @param OAuthToken $token
    * @return string
    */
    abstract public function build_signature($request, $consumer, $token);

    /**
    * Verifies that a given signature is correct
    *
    * @param OAuthRequest $request
    * @param OAuthConsumer $consumer
    * @param OAuthToken $token
    * @param string $signature
    * @return bool
    */
    public function check_signature($request, $consumer, $token, $signature)
    {
        $built = $this->build_signature($request, $consumer, $token);

        // Check for zero length, although unlikely here
        if (strlen($built) == 0 || strlen($signature) == 0) {
            return false;
        }

        if (strlen($built) != strlen($signature)) {
            return false;
        }

        // Avoid a timing leak with a (hopefully) time insensitive compare
        $result = 0;
        for ($i = 0; $i < strlen($signature); $i ++) {
            $result |= ord($built[$i]) ^ ord($signature[$i]);
        }

        return $result == 0;
    }
}
