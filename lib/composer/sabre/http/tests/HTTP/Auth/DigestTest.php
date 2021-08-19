<?php

declare(strict_types=1);

namespace Sabre\HTTP\Auth;

use Sabre\HTTP\Request;
use Sabre\HTTP\Response;

class DigestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Sabre\HTTP\Response
     */
    private $response;

    /**
     * request.
     *
     * @var Sabre\HTTP\Request
     */
    private $request;

    /**
     * @var Sabre\HTTP\Auth\Digest
     */
    private $auth;

    const REALM = 'SabreDAV unittest';

    public function setUp(): void
    {
        $this->response = new Response();
        $this->request = new Request('GET', '/');
        $this->auth = new Digest(self::REALM, $this->request, $this->response);
    }

    public function testDigest()
    {
        list($nonce, $opaque) = $this->getServerTokens();

        $username = 'admin';
        $password = '12345';
        $nc = '00002';
        $cnonce = uniqid();

        $digestHash = md5(
            md5($username.':'.self::REALM.':'.$password).':'.
            $nonce.':'.
            $nc.':'.
            $cnonce.':'.
            'auth:'.
            md5('GET'.':'.'/')
        );

        $this->request->setMethod('GET');
        $this->request->setHeader('Authorization', 'Digest username="'.$username.'", realm="'.self::REALM.'", nonce="'.$nonce.'", uri="/", response="'.$digestHash.'", opaque="'.$opaque.'", qop=auth,nc='.$nc.',cnonce="'.$cnonce.'"');

        $this->auth->init();

        $this->assertEquals($username, $this->auth->getUsername());
        $this->assertEquals(self::REALM, $this->auth->getRealm());
        $this->assertTrue($this->auth->validateA1(md5($username.':'.self::REALM.':'.$password)), 'Authentication is deemed invalid through validateA1');
        $this->assertTrue($this->auth->validatePassword($password), 'Authentication is deemed invalid through validatePassword');
    }

    public function testInvalidDigest()
    {
        list($nonce, $opaque) = $this->getServerTokens();

        $username = 'admin';
        $password = 12345;
        $nc = '00002';
        $cnonce = uniqid();

        $digestHash = md5(
            md5($username.':'.self::REALM.':'.$password).':'.
            $nonce.':'.
            $nc.':'.
            $cnonce.':'.
            'auth:'.
            md5('GET'.':'.'/')
        );

        $this->request->setMethod('GET');
        $this->request->setHeader('Authorization', 'Digest username="'.$username.'", realm="'.self::REALM.'", nonce="'.$nonce.'", uri="/", response="'.$digestHash.'", opaque="'.$opaque.'", qop=auth,nc='.$nc.',cnonce="'.$cnonce.'"');

        $this->auth->init();

        $this->assertFalse($this->auth->validateA1(md5($username.':'.self::REALM.':'.($password.'randomness'))), 'Authentication is deemed invalid through validateA1');
    }

    public function testInvalidDigest2()
    {
        $this->request->setMethod('GET');
        $this->request->setHeader('Authorization', 'basic blablabla');

        $this->auth->init();
        $this->assertFalse($this->auth->validateA1(md5('user:realm:password')));
    }

    public function testDigestAuthInt()
    {
        $this->auth->setQOP(Digest::QOP_AUTHINT);
        list($nonce, $opaque) = $this->getServerTokens(Digest::QOP_AUTHINT);

        $username = 'admin';
        $password = 12345;
        $nc = '00003';
        $cnonce = uniqid();

        $digestHash = md5(
            md5($username.':'.self::REALM.':'.$password).':'.
            $nonce.':'.
            $nc.':'.
            $cnonce.':'.
            'auth-int:'.
            md5('POST'.':'.'/'.':'.md5('body'))
        );

        $this->request->setMethod('POST');
        $this->request->setHeader('Authorization', 'Digest username="'.$username.'", realm="'.self::REALM.'", nonce="'.$nonce.'", uri="/", response="'.$digestHash.'", opaque="'.$opaque.'", qop=auth-int,nc='.$nc.',cnonce="'.$cnonce.'"');
        $this->request->setBody('body');

        $this->auth->init();

        $this->assertTrue($this->auth->validateA1(md5($username.':'.self::REALM.':'.$password)), 'Authentication is deemed invalid through validateA1');
    }

    public function testDigestAuthBoth()
    {
        $this->auth->setQOP(Digest::QOP_AUTHINT | Digest::QOP_AUTH);
        list($nonce, $opaque) = $this->getServerTokens(Digest::QOP_AUTHINT | Digest::QOP_AUTH);

        $username = 'admin';
        $password = 12345;
        $nc = '00003';
        $cnonce = uniqid();

        $digestHash = md5(
            md5($username.':'.self::REALM.':'.$password).':'.
            $nonce.':'.
            $nc.':'.
            $cnonce.':'.
            'auth-int:'.
            md5('POST'.':'.'/'.':'.md5('body'))
        );

        $this->request->setMethod('POST');
        $this->request->setHeader('Authorization', 'Digest username="'.$username.'", realm="'.self::REALM.'", nonce="'.$nonce.'", uri="/", response="'.$digestHash.'", opaque="'.$opaque.'", qop=auth-int,nc='.$nc.',cnonce="'.$cnonce.'"');
        $this->request->setBody('body');

        $this->auth->init();

        $this->assertTrue($this->auth->validateA1(md5($username.':'.self::REALM.':'.$password)), 'Authentication is deemed invalid through validateA1');
    }

    private function getServerTokens($qop = Digest::QOP_AUTH)
    {
        $this->auth->requireLogin();

        switch ($qop) {
            case Digest::QOP_AUTH: $qopstr = 'auth'; break;
            case Digest::QOP_AUTHINT: $qopstr = 'auth-int'; break;
            default: $qopstr = 'auth,auth-int'; break;
        }

        $test = preg_match('/Digest realm="'.self::REALM.'",qop="'.$qopstr.'",nonce="([0-9a-f]*)",opaque="([0-9a-f]*)"/',
            $this->response->getHeader('WWW-Authenticate'), $matches);

        $this->assertTrue(true == $test, 'The WWW-Authenticate response didn\'t match our pattern. We received: '.$this->response->getHeader('WWW-Authenticate'));

        $nonce = $matches[1];
        $opaque = $matches[2];

        // Reset our environment
        $this->setUp();
        $this->auth->setQOP($qop);

        return [$nonce, $opaque];
    }
}
