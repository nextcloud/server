<?php
namespace Office365\PHP\Client\Runtime\Utilities;

use Office365\PHP\Client\Runtime\HttpMethod;

class Requests
{

	private static $defaultOptions = array(
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_RETURNTRANSFER => true,
	);

    public static function getHistory()
    {
        return self::$history;
    }

    protected static $history = [];

	public static function execute(RequestOptions $options)
	{
        $call = [];
        $call['request'] = $options->toArray();

		$ch = Requests::init($options);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $call['response'] = $response;

        if ($response === false) {
            throw new \Exception(curl_error($ch));
        }
        curl_close($ch);

        self::$history[] = $call;

		return $response;
	}


    public static function get($url,$headers)
    {
        $options = new RequestOptions($url);
        $options->Headers = $headers;
        return Requests::execute($options);
    }

    public static function head($url,$headers)
    {
        $options = new RequestOptions($url);
        $options->IncludeHeaders = true;
        $options->IncludeBody = false;
        $options->Headers = $headers;
        return Requests::execute($options);
    }

    public static function post($url, $headers, $data, $includeHeaders = false)
    {
        $options = new RequestOptions($url);
        $options->Method = HttpMethod::Post;
        $options->Headers = $headers;
        $options->Data = $data;
        $options->IncludeHeaders = $includeHeaders;
        return Requests::execute($options);
    }


	/**
	 * Parse cookies (http://stackoverflow.com/a/895858/1375553)
	 * @param $response
	 * @return array
	 */
    public static function parseCookies($response)
    {
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
        $cookies = array();
        foreach($matches[1] as $item) {
            list($k, $v) = explode('=', $item,2);
            $cookies[$k] = $v;
        }
        return $cookies;
    }


    /**
     * Init Curl with the default parameters
     * @param RequestOptions $options
     * @return resource [type]    [description]
     */
    private static function init(RequestOptions $options)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $options->Url);
        curl_setopt_array($ch, self::$defaultOptions);  //default options
        //include headers in response
        curl_setopt($ch, CURLOPT_HEADER, $options->IncludeHeaders);
        //include body in response
        curl_setopt($ch, CURLOPT_NOBODY, !$options->IncludeBody);
        //Set method
        if($options->Method == HttpMethod::Post)
            curl_setopt($ch, CURLOPT_POST, 1);
        else if($options->Method == HttpMethod::Patch)
           curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $options->Method);
        else if($options->Method == HttpMethod::Delete)
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $options->Method);
        //set Post Body
        if(isset($options->Data))
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options->Data);
        $options->addCustomHeader("content-length",strlen($options->Data));
        //custom HTTP headers
        if($options->Headers)
            curl_setopt($ch, CURLOPT_HTTPHEADER, $options->getRawHeaders());
        //debugging mode
        curl_setopt($ch,CURLOPT_VERBOSE, $options->Verbose);
        //SSL Version
        if(!is_null($options->SSLVersion)) {
            curl_setopt($ch,CURLOPT_SSLVERSION, $options->SSLVersion);
        }
        //authentication
        if(!is_null($options->AuthType))
            curl_setopt($ch,CURLOPT_HTTPAUTH, $options->AuthType);
        if(!is_null($options->UserCredentials))
            curl_setopt($ch,CURLOPT_USERPWD, $options->UserCredentials->toString());

        return $ch;
    }

}
