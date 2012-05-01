<?php
// $Id: page_test.php 1913 2009-07-29 16:50:56Z lastcraft $
require_once(dirname(__FILE__) . '/../autorun.php');
require_once(dirname(__FILE__) . '/../expectation.php');
require_once(dirname(__FILE__) . '/../http.php');
require_once(dirname(__FILE__) . '/../page.php');
Mock::generate('SimpleHttpHeaders');
Mock::generate('SimpleHttpResponse');

class TestOfPageInterface extends UnitTestCase {
    function testInterfaceOnEmptyPage() {
        $page = new SimplePage();
        $this->assertEqual($page->getTransportError(), 'No page fetched yet');
        $this->assertIdentical($page->getRaw(), false);
        $this->assertIdentical($page->getHeaders(), false);
        $this->assertIdentical($page->getMimeType(), false);
        $this->assertIdentical($page->getResponseCode(), false);
        $this->assertIdentical($page->getAuthentication(), false);
        $this->assertIdentical($page->getRealm(), false);
        $this->assertFalse($page->hasFrames());
        $this->assertIdentical($page->getUrls(), array());
        $this->assertIdentical($page->getTitle(), false);
    }
}

class TestOfPageHeaders extends UnitTestCase {

    function testUrlAccessor() {
        $headers = new MockSimpleHttpHeaders();

        $response = new MockSimpleHttpResponse();
        $response->setReturnValue('getHeaders', $headers);
        $response->setReturnValue('getMethod', 'POST');
        $response->setReturnValue('getUrl', new SimpleUrl('here'));
        $response->setReturnValue('getRequestData', array('a' => 'A'));

        $page = new SimplePage($response);
        $this->assertEqual($page->getMethod(), 'POST');
        $this->assertEqual($page->getUrl(), new SimpleUrl('here'));
        $this->assertEqual($page->getRequestData(), array('a' => 'A'));
    }

    function testTransportError() {
        $response = new MockSimpleHttpResponse();
        $response->setReturnValue('getError', 'Ouch');

        $page = new SimplePage($response);
        $this->assertEqual($page->getTransportError(), 'Ouch');
    }

    function testHeadersAccessor() {
        $headers = new MockSimpleHttpHeaders();
        $headers->setReturnValue('getRaw', 'My: Headers');

        $response = new MockSimpleHttpResponse();
        $response->setReturnValue('getHeaders', $headers);

        $page = new SimplePage($response);
        $this->assertEqual($page->getHeaders(), 'My: Headers');
    }

    function testMimeAccessor() {
        $headers = new MockSimpleHttpHeaders();
        $headers->setReturnValue('getMimeType', 'text/html');

        $response = new MockSimpleHttpResponse();
        $response->setReturnValue('getHeaders', $headers);

        $page = new SimplePage($response);
        $this->assertEqual($page->getMimeType(), 'text/html');
    }

    function testResponseAccessor() {
        $headers = new MockSimpleHttpHeaders();
        $headers->setReturnValue('getResponseCode', 301);

        $response = new MockSimpleHttpResponse();
        $response->setReturnValue('getHeaders', $headers);

        $page = new SimplePage($response);
        $this->assertIdentical($page->getResponseCode(), 301);
    }

    function testAuthenticationAccessors() {
        $headers = new MockSimpleHttpHeaders();
        $headers->setReturnValue('getAuthentication', 'Basic');
        $headers->setReturnValue('getRealm', 'Secret stuff');

        $response = new MockSimpleHttpResponse();
        $response->setReturnValue('getHeaders', $headers);

        $page = new SimplePage($response);
        $this->assertEqual($page->getAuthentication(), 'Basic');
        $this->assertEqual($page->getRealm(), 'Secret stuff');
    }
}

class TestOfHtmlStrippingAndNormalisation extends UnitTestCase {

	function testImageSuppressionWhileKeepingParagraphsAndAltText() {
        $this->assertEqual(
                SimplePage::normalise('<img src="foo.png" /><p>some text</p><img src="bar.png" alt="bar" />'),
                'some text bar');
	}

    function testSpaceNormalisation() {
        $this->assertEqual(
                SimplePage::normalise("\nOne\tTwo   \nThree\t"),
                'One Two Three');
    }

    function testMultilinesCommentSuppression() {
        $this->assertEqual(
                SimplePage::normalise('<!--\n Hello \n-->'),
                '');
    }

    function testCommentSuppression() {
        $this->assertEqual(
                SimplePage::normalise('<!--Hello-->'),
                '');
    }

    function testJavascriptSuppression() {
        $this->assertEqual(
                SimplePage::normalise('<script attribute="test">\nHello\n</script>'),
                '');
        $this->assertEqual(
                SimplePage::normalise('<script attribute="test">Hello</script>'),
                '');
        $this->assertEqual(
                SimplePage::normalise('<script>Hello</script>'),
                '');
    }

    function testTagSuppression() {
        $this->assertEqual(
                SimplePage::normalise('<b>Hello</b>'),
                'Hello');
    }

    function testAdjoiningTagSuppression() {
        $this->assertEqual(
                SimplePage::normalise('<b>Hello</b><em>Goodbye</em>'),
                'HelloGoodbye');
    }

    function testExtractImageAltTextWithDifferentQuotes() {
        $this->assertEqual(
                SimplePage::normalise('<img alt="One"><img alt=\'Two\'><img alt=Three>'),
                'One Two Three');
    }

    function testExtractImageAltTextMultipleTimes() {
        $this->assertEqual(
                SimplePage::normalise('<img alt="One"><img alt="Two"><img alt="Three">'),
                'One Two Three');
    }

    function testHtmlEntityTranslation() {
        $this->assertEqual(
                SimplePage::normalise('&lt;&gt;&quot;&amp;&#039;'),
                '<>"&\'');
    }
}
?>