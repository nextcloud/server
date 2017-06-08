<?php


namespace Office365\PHP\Client\Runtime\Auth;


use Exception;
use Office365\PHP\Client\Runtime\Utilities\Requests;

class SamlTokenProvider extends BaseTokenProvider
{

    /**
     * External Security Token Service for O365
     * @var string
     */
    private static $StsUrl = 'https://login.microsoftonline.com/extSTS.srf';

    /**
     * Form Url to submit SAML token
     * @var string
     */
    private static $SignInPageUrl = '/_forms/default.aspx?wa=wsignin1.0';


    /**
     * @var string
     */
    protected $authorityUrl;


    /**
     * Office365 Auth cookie
     * @var mixed
     */
    private $FedAuth;

    /**
     * Office365 Auth cookie
     * @var mixed
     */
    private $rtFa;


    public function __construct($authorityUrl)
    {
        $this->authorityUrl = $authorityUrl;
    }


    public function getAuthenticationCookie()
    {
        return 'FedAuth=' . $this->FedAuth . '; rtFa=' . $this->rtFa;
    }

    

    public function acquireToken($parameters)
    {
        $token = $this->acquireSecurityToken($parameters['username'], $parameters['password']);
        $this->acquireAuthenticationCookies($token);
    }


    /**
     * Acquire SharePoint Online authentication (FedAuth and rtFa) cookies
     * @param mixed $token
     * @throws Exception
     */
    protected function acquireAuthenticationCookies($token)
    {
        $urlInfo = parse_url($this->authorityUrl);
        $url =  $urlInfo['scheme'] . '://' . $urlInfo['host'] . self::$SignInPageUrl;
        $response = Requests::post($url,null,$token,true);
        $cookies = Requests::parseCookies($response);
        $this->FedAuth = $cookies['FedAuth'];
        $this->rtFa = $cookies['rtFa'];
    }


    /**
     * Acquire the service token from STS
     *
     * @param string $username
     * @param string $password
     * @return string
     * @throws Exception
     */
    protected function acquireSecurityToken($username, $password)
    {
        $data = $this->prepareSecurityTokenRequest($username, $password, $this->authorityUrl);
        $response = Requests::post(self::$StsUrl,null,$data);
        return $this->processSecurityTokenResponse($response);
    }


    /**
     * Verify and extract security token from the HTTP response
     * @param mixed $response
     * @return mixed
     * @throws Exception
     */
    protected function processSecurityTokenResponse($response)
    {
        $xml = new \DOMDocument();
        $xml->loadXML($response);
        $xpath = new \DOMXPath($xml);
        if ($xpath->query("//S:Fault")->length > 0) {
            $nodeErr = $xpath->query("//S:Fault/S:Detail/psf:error/psf:internalerror/psf:text")->item(0);
            throw new \Exception($nodeErr->nodeValue);
        }
        $nodeToken = $xpath->query("//wsse:BinarySecurityToken")->item(0);
        if (empty($nodeToken)) {
            throw new \RuntimeException('Error trying to get a token, check your URL or credentials');
        }

        return $nodeToken->nodeValue;
    }

    /**
     * Construct the request body to acquire security token from STS endpoint
     *
     * @param string $username
     * @param string $password
     * @param string $address
     * @return string
     * @throws Exception
     */
    protected function prepareSecurityTokenRequest($username, $password, $address)
    {
        $fileName = __DIR__ . '/xml/SAML.xml';
        if (!file_exists($fileName)) {
            throw new \Exception("The file $fileName does not exist");
        }

        $template = file_get_contents($fileName);
        $template = str_replace('{username}', $username, $template);
        $template = str_replace('{password}', $password, $template);
        $template = str_replace('{address}', $address, $template);
        return $template;
    }
}