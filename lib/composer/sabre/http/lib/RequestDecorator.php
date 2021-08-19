<?php

declare(strict_types=1);

namespace Sabre\HTTP;

/**
 * Request Decorator.
 *
 * This helper class allows you to easily create decorators for the Request
 * object.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class RequestDecorator implements RequestInterface
{
    use MessageDecoratorTrait;

    /**
     * Constructor.
     */
    public function __construct(RequestInterface $inner)
    {
        $this->inner = $inner;
    }

    /**
     * Returns the current HTTP method.
     */
    public function getMethod(): string
    {
        return $this->inner->getMethod();
    }

    /**
     * Sets the HTTP method.
     */
    public function setMethod(string $method)
    {
        $this->inner->setMethod($method);
    }

    /**
     * Returns the request url.
     */
    public function getUrl(): string
    {
        return $this->inner->getUrl();
    }

    /**
     * Sets the request url.
     */
    public function setUrl(string $url)
    {
        $this->inner->setUrl($url);
    }

    /**
     * Returns the absolute url.
     */
    public function getAbsoluteUrl(): string
    {
        return $this->inner->getAbsoluteUrl();
    }

    /**
     * Sets the absolute url.
     */
    public function setAbsoluteUrl(string $url)
    {
        $this->inner->setAbsoluteUrl($url);
    }

    /**
     * Returns the current base url.
     */
    public function getBaseUrl(): string
    {
        return $this->inner->getBaseUrl();
    }

    /**
     * Sets a base url.
     *
     * This url is used for relative path calculations.
     *
     * The base url should default to /
     */
    public function setBaseUrl(string $url)
    {
        $this->inner->setBaseUrl($url);
    }

    /**
     * Returns the relative path.
     *
     * This is being calculated using the base url. This path will not start
     * with a slash, so it will always return something like
     * 'example/path.html'.
     *
     * If the full path is equal to the base url, this method will return an
     * empty string.
     *
     * This method will also urldecode the path, and if the url was incoded as
     * ISO-8859-1, it will convert it to UTF-8.
     *
     * If the path is outside of the base url, a LogicException will be thrown.
     */
    public function getPath(): string
    {
        return $this->inner->getPath();
    }

    /**
     * Returns the list of query parameters.
     *
     * This is equivalent to PHP's $_GET superglobal.
     */
    public function getQueryParameters(): array
    {
        return $this->inner->getQueryParameters();
    }

    /**
     * Returns the POST data.
     *
     * This is equivalent to PHP's $_POST superglobal.
     */
    public function getPostData(): array
    {
        return $this->inner->getPostData();
    }

    /**
     * Sets the post data.
     *
     * This is equivalent to PHP's $_POST superglobal.
     *
     * This would not have been needed, if POST data was accessible as
     * php://input, but unfortunately we need to special case it.
     */
    public function setPostData(array $postData)
    {
        $this->inner->setPostData($postData);
    }

    /**
     * Returns an item from the _SERVER array.
     *
     * If the value does not exist in the array, null is returned.
     *
     * @return string|null
     */
    public function getRawServerValue(string $valueName)
    {
        return $this->inner->getRawServerValue($valueName);
    }

    /**
     * Sets the _SERVER array.
     */
    public function setRawServerData(array $data)
    {
        $this->inner->setRawServerData($data);
    }

    /**
     * Serializes the request object as a string.
     *
     * This is useful for debugging purposes.
     */
    public function __toString(): string
    {
        return $this->inner->__toString();
    }
}
