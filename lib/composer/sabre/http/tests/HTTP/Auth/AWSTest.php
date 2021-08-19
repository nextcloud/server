<?php

declare(strict_types=1);

namespace Sabre\HTTP\Auth;

use Sabre\HTTP\Request;
use Sabre\HTTP\Response;

class AWSTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Sabre\HTTP\Response
     */
    private $response;

    /**
     * @var Sabre\HTTP\Request
     */
    private $request;

    /**
     * @var Sabre\HTTP\Auth\AWS
     */
    private $auth;

    const REALM = 'SabreDAV unittest';

    public function setUp(): void
    {
        $this->response = new Response();
        $this->request = new Request('GET', '/');
        $this->auth = new AWS(self::REALM, $this->request, $this->response);
    }

    public function testNoHeader()
    {
        $this->request->setMethod('GET');
        $result = $this->auth->init();

        $this->assertFalse($result, 'No AWS Authorization header was supplied, so we should have gotten false');
        $this->assertEquals(AWS::ERR_NOAWSHEADER, $this->auth->errorCode);
    }

    public function testIncorrectContentMD5()
    {
        $accessKey = 'accessKey';
        $secretKey = 'secretKey';

        $this->request->setMethod('GET');
        $this->request->setHeaders([
            'Authorization' => "AWS $accessKey:sig",
            'Content-MD5' => 'garbage',
        ]);
        $this->request->setUrl('/');

        $this->auth->init();
        $result = $this->auth->validate($secretKey);

        $this->assertFalse($result);
        $this->assertEquals(AWS::ERR_MD5CHECKSUMWRONG, $this->auth->errorCode);
    }

    public function testNoDate()
    {
        $accessKey = 'accessKey';
        $secretKey = 'secretKey';
        $content = 'thisisthebody';
        $contentMD5 = base64_encode(md5($content, true));

        $this->request->setMethod('POST');
        $this->request->setHeaders([
            'Authorization' => "AWS $accessKey:sig",
            'Content-MD5' => $contentMD5,
        ]);
        $this->request->setUrl('/');
        $this->request->setBody($content);

        $this->auth->init();
        $result = $this->auth->validate($secretKey);

        $this->assertFalse($result);
        $this->assertEquals(AWS::ERR_INVALIDDATEFORMAT, $this->auth->errorCode);
    }

    public function testFutureDate()
    {
        $accessKey = 'accessKey';
        $secretKey = 'secretKey';
        $content = 'thisisthebody';
        $contentMD5 = base64_encode(md5($content, true));

        $date = new \DateTime('@'.(time() + (60 * 20)));
        $date->setTimeZone(new \DateTimeZone('GMT'));
        $date = $date->format('D, d M Y H:i:s \\G\\M\\T');

        $this->request->setMethod('POST');
        $this->request->setHeaders([
            'Authorization' => "AWS $accessKey:sig",
            'Content-MD5' => $contentMD5,
            'Date' => $date,
        ]);

        $this->request->setBody($content);

        $this->auth->init();
        $result = $this->auth->validate($secretKey);

        $this->assertFalse($result);
        $this->assertEquals(AWS::ERR_REQUESTTIMESKEWED, $this->auth->errorCode);
    }

    public function testPastDate()
    {
        $accessKey = 'accessKey';
        $secretKey = 'secretKey';
        $content = 'thisisthebody';
        $contentMD5 = base64_encode(md5($content, true));

        $date = new \DateTime('@'.(time() - (60 * 20)));
        $date->setTimeZone(new \DateTimeZone('GMT'));
        $date = $date->format('D, d M Y H:i:s \\G\\M\\T');

        $this->request->setMethod('POST');
        $this->request->setHeaders([
            'Authorization' => "AWS $accessKey:sig",
            'Content-MD5' => $contentMD5,
            'Date' => $date,
        ]);

        $this->request->setBody($content);

        $this->auth->init();
        $result = $this->auth->validate($secretKey);

        $this->assertFalse($result);
        $this->assertEquals(AWS::ERR_REQUESTTIMESKEWED, $this->auth->errorCode);
    }

    public function testIncorrectSignature()
    {
        $accessKey = 'accessKey';
        $secretKey = 'secretKey';
        $content = 'thisisthebody';

        $contentMD5 = base64_encode(md5($content, true));

        $date = new \DateTime('now');
        $date->setTimeZone(new \DateTimeZone('GMT'));
        $date = $date->format('D, d M Y H:i:s \\G\\M\\T');

        $this->request->setUrl('/');
        $this->request->setMethod('POST');
        $this->request->setHeaders([
            'Authorization' => "AWS $accessKey:sig",
            'Content-MD5' => $contentMD5,
            'X-amz-date' => $date,
        ]);
        $this->request->setBody($content);

        $this->auth->init();
        $result = $this->auth->validate($secretKey);

        $this->assertFalse($result);
        $this->assertEquals(AWS::ERR_INVALIDSIGNATURE, $this->auth->errorCode);
    }

    public function testValidRequest()
    {
        $accessKey = 'accessKey';
        $secretKey = 'secretKey';
        $content = 'thisisthebody';
        $contentMD5 = base64_encode(md5($content, true));

        $date = new \DateTime('now');
        $date->setTimeZone(new \DateTimeZone('GMT'));
        $date = $date->format('D, d M Y H:i:s \\G\\M\\T');

        $sig = base64_encode($this->hmacsha1($secretKey,
            "POST\n$contentMD5\n\n$date\nx-amz-date:$date\n/evert"
        ));

        $this->request->setUrl('/evert');
        $this->request->setMethod('POST');
        $this->request->setHeaders([
            'Authorization' => "AWS $accessKey:$sig",
            'Content-MD5' => $contentMD5,
            'X-amz-date' => $date,
        ]);

        $this->request->setBody($content);

        $this->auth->init();
        $result = $this->auth->validate($secretKey);

        $this->assertTrue($result, 'Signature did not validate, got errorcode '.$this->auth->errorCode);
        $this->assertEquals($accessKey, $this->auth->getAccessKey());
    }

    public function test401()
    {
        $this->auth->requireLogin();
        $test = preg_match('/^AWS$/', $this->response->getHeader('WWW-Authenticate'), $matches);
        $this->assertTrue(true == $test, 'The WWW-Authenticate response didn\'t match our pattern');
    }

    /**
     * Generates an HMAC-SHA1 signature.
     *
     * @param string $key
     * @param string $message
     *
     * @return string
     */
    private function hmacsha1($key, $message)
    {
        $blocksize = 64;
        if (strlen($key) > $blocksize) {
            $key = pack('H*', sha1($key));
        }
        $key = str_pad($key, $blocksize, chr(0x00));
        $ipad = str_repeat(chr(0x36), $blocksize);
        $opad = str_repeat(chr(0x5c), $blocksize);
        $hmac = pack('H*', sha1(($key ^ $opad).pack('H*', sha1(($key ^ $ipad).$message))));

        return $hmac;
    }
}
