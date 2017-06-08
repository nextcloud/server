<?php


namespace Office365\PHP\Client\Runtime\Auth;

use Office365\PHP\Client\Runtime\HttpMethod;
use Office365\PHP\Client\Runtime\Utilities\RequestOptions;
use Office365\PHP\Client\Runtime\Utilities\Requests;

/**
 * Provider to acquire the access token from Azure AD
 */
class OAuthTokenProvider extends BaseTokenProvider
{

    /**
     * @var string
     */
    private static $TokenEndpoint = '/oauth2/token';

    /**
     * @var string
     */
    //private static $AuthorityUrl  = 'https://login.microsoftonline.com/common';
    public static $AuthorityUrl = "https://login.microsoftonline.com/";


    /**
     * @var string
     */
    //private static  $AuthorizeEndpoint = '/oauth2/authorize';

    /**
     * @var string
     */
    public static $ResourceId = 'https://outlook.office365.com/';
    //private static $ResourceId = 'https://graph.windows.net/';

    /**
     * @var string
     */
    private $authorityUrl;

    /**
     * @var \stdClass
     */
    private $accessToken;


    public function __construct($authorityUrl)
    {
        $this->authorityUrl = $authorityUrl;
    }

    public function getAuthorizationHeader()
    {
        return 'Bearer ' . $this->accessToken->access_token;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function setAccessToken($accessToken)
    {
        if (!$this->accessToken instanceof \stdClass) {
            $this->accessToken = new \stdClass();
        }
        $this->accessToken->access_token = $accessToken;
    }

    /**
     * Acquires the access token
     * @param array $parameters
     */
    public function acquireToken($parameters)
    {
        $request = $this->createRequest($parameters);
        $response = Requests::execute($request);
        $this->parseToken($response, $parameters);
    }

    /**
     * @param $parameters
     * @return RequestOptions
     */
    private function createRequest($parameters)
    {
        $tokenUrl = $this->authorityUrl . self::$TokenEndpoint;
        $request = new RequestOptions($tokenUrl);
        $request->addCustomHeader('content-Type', 'application/x-www-form-urlencoded');
        $request->Method = HttpMethod::Post;
        $request->Data = http_build_query($parameters);
        return $request;
    }

    /**
     * Parse the id token that represents a JWT token that contains information about the user
     * @param string $tokenValue
     */
    private function parseToken($tokenValue, $parameters)
    {
        $tokenPayload = json_decode($tokenValue);
        if (is_object($tokenPayload)) {
            $this->accessToken = $tokenPayload;
            if (isset($tokenPayload->id_token)) {
                $idToken = $tokenPayload->id_token;
                $idTokenPayload = base64_decode(
                    explode('.', $idToken)[1]
                );
                $this->accessToken->id_token_info = json_decode($idTokenPayload, true);
            }
        }
    }
}
