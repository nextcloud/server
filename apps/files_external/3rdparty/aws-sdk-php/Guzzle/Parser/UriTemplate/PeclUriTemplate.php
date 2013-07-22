<?php

namespace Guzzle\Parser\UriTemplate;

use Guzzle\Common\Exception\RuntimeException;

/**
 * Expands URI templates using the uri_template pecl extension (pecl install uri_template-beta)
 *
 * @link http://pecl.php.net/package/uri_template
 * @link https://github.com/ioseb/uri-template
 */
class PeclUriTemplate implements UriTemplateInterface
{
    public function __construct()
    {
        if (!extension_loaded('uri_template')) {
            throw new RuntimeException('uri_template PECL extension must be installed to use PeclUriTemplate');
        }
    }

    public function expand($template, array $variables)
    {
        return uri_template($template, $variables);
    }
}
