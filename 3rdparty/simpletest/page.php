<?php
/**
 *  Base include file for SimpleTest
 *  @package    SimpleTest
 *  @subpackage WebTester
 *  @version    $Id: page.php 1938 2009-08-05 17:16:23Z dgheath $
 */

/**#@+
    *   include other SimpleTest class files
    */
require_once(dirname(__FILE__) . '/http.php');
require_once(dirname(__FILE__) . '/php_parser.php');
require_once(dirname(__FILE__) . '/tag.php');
require_once(dirname(__FILE__) . '/form.php');
require_once(dirname(__FILE__) . '/selector.php');
/**#@-*/

/**
 *    A wrapper for a web page.
 *    @package SimpleTest
 *    @subpackage WebTester
 */
class SimplePage {
    private $links = array();
    private $title = false;
    private $last_widget;
    private $label;
    private $forms = array();
    private $frames = array();
    private $transport_error;
    private $raw;
    private $text = false;
    private $sent;
    private $headers;
    private $method;
    private $url;
    private $base = false;
    private $request_data;

    /**
     *    Parses a page ready to access it's contents.
     *    @param SimpleHttpResponse $response     Result of HTTP fetch.
     *    @access public
     */
    function __construct($response = false) {
        if ($response) {
            $this->extractResponse($response);
        } else {
            $this->noResponse();
        }
    }

    /**
     *    Extracts all of the response information.
     *    @param SimpleHttpResponse $response    Response being parsed.
     *    @access private
     */
    protected function extractResponse($response) {
        $this->transport_error = $response->getError();
        $this->raw = $response->getContent();
        $this->sent = $response->getSent();
        $this->headers = $response->getHeaders();
        $this->method = $response->getMethod();
        $this->url = $response->getUrl();
        $this->request_data = $response->getRequestData();
    }

    /**
     *    Sets up a missing response.
     *    @access private
     */
    protected function noResponse() {
        $this->transport_error = 'No page fetched yet';
        $this->raw = false;
        $this->sent = false;
        $this->headers = false;
        $this->method = 'GET';
        $this->url = false;
        $this->request_data = false;
    }

    /**
     *    Original request as bytes sent down the wire.
     *    @return mixed              Sent content.
     *    @access public
     */
    function getRequest() {
        return $this->sent;
    }

    /**
     *    Accessor for raw text of page.
     *    @return string        Raw unparsed content.
     *    @access public
     */
    function getRaw() {
        return $this->raw;
    }

    /**
     *    Accessor for plain text of page as a text browser
     *    would see it.
     *    @return string        Plain text of page.
     *    @access public
     */
    function getText() {
        if (! $this->text) {
            $this->text = SimplePage::normalise($this->raw);
        }
        return $this->text;
    }

    /**
     *    Accessor for raw headers of page.
     *    @return string       Header block as text.
     *    @access public
     */
    function getHeaders() {
        if ($this->headers) {
            return $this->headers->getRaw();
        }
        return false;
    }

    /**
     *    Original request method.
     *    @return string        GET, POST or HEAD.
     *    @access public
     */
    function getMethod() {
        return $this->method;
    }

    /**
     *    Original resource name.
     *    @return SimpleUrl        Current url.
     *    @access public
     */
    function getUrl() {
        return $this->url;
    }

    /**
     *    Base URL if set via BASE tag page url otherwise
     *    @return SimpleUrl        Base url.
     *    @access public
     */
    function getBaseUrl() {
        return $this->base;
    }

    /**
     *    Original request data.
     *    @return mixed              Sent content.
     *    @access public
     */
    function getRequestData() {
        return $this->request_data;
    }

    /**
     *    Accessor for last error.
     *    @return string        Error from last response.
     *    @access public
     */
    function getTransportError() {
        return $this->transport_error;
    }

    /**
     *    Accessor for current MIME type.
     *    @return string    MIME type as string; e.g. 'text/html'
     *    @access public
     */
    function getMimeType() {
        if ($this->headers) {
            return $this->headers->getMimeType();
        }
        return false;
    }

    /**
     *    Accessor for HTTP response code.
     *    @return integer    HTTP response code received.
     *    @access public
     */
    function getResponseCode() {
        if ($this->headers) {
            return $this->headers->getResponseCode();
        }
        return false;
    }

    /**
     *    Accessor for last Authentication type. Only valid
     *    straight after a challenge (401).
     *    @return string    Description of challenge type.
     *    @access public
     */
    function getAuthentication() {
        if ($this->headers) {
            return $this->headers->getAuthentication();
        }
        return false;
    }

    /**
     *    Accessor for last Authentication realm. Only valid
     *    straight after a challenge (401).
     *    @return string    Name of security realm.
     *    @access public
     */
    function getRealm() {
        if ($this->headers) {
            return $this->headers->getRealm();
        }
        return false;
    }

    /**
     *    Accessor for current frame focus. Will be
     *    false as no frames.
     *    @return array    Always empty.
     *    @access public
     */
    function getFrameFocus() {
        return array();
    }

    /**
     *    Sets the focus by index. The integer index starts from 1.
     *    @param integer $choice    Chosen frame.
     *    @return boolean           Always false.
     *    @access public
     */
    function setFrameFocusByIndex($choice) {
        return false;
    }

    /**
     *    Sets the focus by name. Always fails for a leaf page.
     *    @param string $name    Chosen frame.
     *    @return boolean        False as no frames.
     *    @access public
     */
    function setFrameFocus($name) {
        return false;
    }

    /**
     *    Clears the frame focus. Does nothing for a leaf page.
     *    @access public
     */
    function clearFrameFocus() {
    }

    /**
     *    TODO: write docs
     */
    function setFrames($frames) {
        $this->frames = $frames;
    }

    /**
     *    Test to see if link is an absolute one.
     *    @param string $url     Url to test.
     *    @return boolean        True if absolute.
     *    @access protected
     */
    protected function linkIsAbsolute($url) {
        $parsed = new SimpleUrl($url);
        return (boolean)($parsed->getScheme() && $parsed->getHost());
    }

    /**
     *    Adds a link to the page.
     *    @param SimpleAnchorTag $tag      Link to accept.
     */
    function addLink($tag) {
        $this->links[] = $tag;
    }

    /**
     *    Set the forms
     *    @param array $forms           An array of SimpleForm objects
     */
    function setForms($forms) {
        $this->forms = $forms;
    }

    /**
     *    Test for the presence of a frameset.
     *    @return boolean        True if frameset.
     *    @access public
     */
    function hasFrames() {
        return count($this->frames) > 0;
    }

    /**
     *    Accessor for frame name and source URL for every frame that
     *    will need to be loaded. Immediate children only.
     *    @return boolean/array     False if no frameset or
     *                              otherwise a hash of frame URLs.
     *                              The key is either a numerical
     *                              base one index or the name attribute.
     *    @access public
     */
    function getFrameset() {
        if (! $this->hasFrames()) {
            return false;
        }
        $urls = array();
        for ($i = 0; $i < count($this->frames); $i++) {
            $name = $this->frames[$i]->getAttribute('name');
            $url = new SimpleUrl($this->frames[$i]->getAttribute('src'));
            $urls[$name ? $name : $i + 1] = $this->expandUrl($url);
        }
        return $urls;
    }

    /**
     *    Fetches a list of loaded frames.
     *    @return array/string    Just the URL for a single page.
     *    @access public
     */
    function getFrames() {
        $url = $this->expandUrl($this->getUrl());
        return $url->asString();
    }

    /**
     *    Accessor for a list of all links.
     *    @return array   List of urls with scheme of
     *                    http or https and hostname.
     *    @access public
     */
    function getUrls() {
        $all = array();
        foreach ($this->links as $link) {
            $url = $this->getUrlFromLink($link);
            $all[] = $url->asString();
        }
        return $all;
    }

    /**
     *    Accessor for URLs by the link label. Label will match
     *    regardess of whitespace issues and case.
     *    @param string $label    Text of link.
     *    @return array           List of links with that label.
     *    @access public
     */
    function getUrlsByLabel($label) {
        $matches = array();
        foreach ($this->links as $link) {
            if ($link->getText() == $label) {
                $matches[] = $this->getUrlFromLink($link);
            }
        }
        return $matches;
    }

    /**
     *    Accessor for a URL by the id attribute.
     *    @param string $id       Id attribute of link.
     *    @return SimpleUrl       URL with that id of false if none.
     *    @access public
     */
    function getUrlById($id) {
        foreach ($this->links as $link) {
            if ($link->getAttribute('id') === (string)$id) {
                return $this->getUrlFromLink($link);
            }
        }
        return false;
    }

    /**
     *    Converts a link tag into a target URL.
     *    @param SimpleAnchor $link    Parsed link.
     *    @return SimpleUrl            URL with frame target if any.
     *    @access private
     */
    protected function getUrlFromLink($link) {
        $url = $this->expandUrl($link->getHref());
        if ($link->getAttribute('target')) {
            $url->setTarget($link->getAttribute('target'));
        }
        return $url;
    }

    /**
     *    Expands expandomatic URLs into fully qualified
     *    URLs.
     *    @param SimpleUrl $url        Relative URL.
     *    @return SimpleUrl            Absolute URL.
     *    @access public
     */
    function expandUrl($url) {
        if (! is_object($url)) {
            $url = new SimpleUrl($url);
        }
        $location = $this->getBaseUrl() ? $this->getBaseUrl() : new SimpleUrl();
        return $url->makeAbsolute($location->makeAbsolute($this->getUrl()));
    }

    /**
     *    Sets the base url for the page.
     *    @param string $url    Base URL for page.
     */
    function setBase($url) {
        $this->base = new SimpleUrl($url);
    }

    /**
     *    Sets the title tag contents.
     *    @param SimpleTitleTag $tag    Title of page.
     */
    function setTitle($tag) {
        $this->title = $tag;
    }

    /**
     *    Accessor for parsed title.
     *    @return string     Title or false if no title is present.
     *    @access public
     */
    function getTitle() {
        if ($this->title) {
            return $this->title->getText();
        }
        return false;
    }

    /**
     *    Finds a held form by button label. Will only
     *    search correctly built forms.
     *    @param SimpleSelector $selector       Button finder.
     *    @return SimpleForm                    Form object containing
     *                                          the button.
     *    @access public
     */
    function getFormBySubmit($selector) {
        for ($i = 0; $i < count($this->forms); $i++) {
            if ($this->forms[$i]->hasSubmit($selector)) {
                return $this->forms[$i];
            }
        }
        return null;
    }

    /**
     *    Finds a held form by image using a selector.
     *    Will only search correctly built forms.
     *    @param SimpleSelector $selector  Image finder.
     *    @return SimpleForm               Form object containing
     *                                     the image.
     *    @access public
     */
    function getFormByImage($selector) {
        for ($i = 0; $i < count($this->forms); $i++) {
            if ($this->forms[$i]->hasImage($selector)) {
                return $this->forms[$i];
            }
        }
        return null;
    }

    /**
     *    Finds a held form by the form ID. A way of
     *    identifying a specific form when we have control
     *    of the HTML code.
     *    @param string $id     Form label.
     *    @return SimpleForm    Form object containing the matching ID.
     *    @access public
     */
    function getFormById($id) {
        for ($i = 0; $i < count($this->forms); $i++) {
            if ($this->forms[$i]->getId() == $id) {
                return $this->forms[$i];
            }
        }
        return null;
    }

    /**
     *    Sets a field on each form in which the field is
     *    available.
     *    @param SimpleSelector $selector    Field finder.
     *    @param string $value               Value to set field to.
     *    @return boolean                    True if value is valid.
     *    @access public
     */
    function setField($selector, $value, $position=false) {
        $is_set = false;
        for ($i = 0; $i < count($this->forms); $i++) {
            if ($this->forms[$i]->setField($selector, $value, $position)) {
                $is_set = true;
            }
        }
        return $is_set;
    }

    /**
     *    Accessor for a form element value within a page.
     *    @param SimpleSelector $selector    Field finder.
     *    @return string/boolean             A string if the field is
     *                                       present, false if unchecked
     *                                       and null if missing.
     *    @access public
     */
    function getField($selector) {
        for ($i = 0; $i < count($this->forms); $i++) {
            $value = $this->forms[$i]->getValue($selector);
            if (isset($value)) {
                return $value;
            }
        }
        return null;
    }

    /**
     *    Turns HTML into text browser visible text. Images
     *    are converted to their alt text and tags are supressed.
     *    Entities are converted to their visible representation.
     *    @param string $html        HTML to convert.
     *    @return string             Plain text.
     *    @access public
     */
    static function normalise($html) {
        $text = preg_replace('#<!--.*?-->#si', '', $html);
        $text = preg_replace('#<(script|option|textarea)[^>]*>.*?</\1>#si', '', $text);
        $text = preg_replace('#<img[^>]*alt\s*=\s*("([^"]*)"|\'([^\']*)\'|([a-zA-Z_]+))[^>]*>#', ' \2\3\4 ', $text);
        $text = preg_replace('#<[^>]*>#', '', $text);
        $text = html_entity_decode($text, ENT_QUOTES);
        $text = preg_replace('#\s+#', ' ', $text);
        return trim(trim($text), "\xA0");        // TODO: The \xAO is a &nbsp;. Add a test for this.
    }
}
?>