<?php

namespace Guzzle\Parser\UriTemplate;

/**
 * Expands URI templates using an array of variables
 *
 * @link http://tools.ietf.org/html/rfc6570
 */
interface UriTemplateInterface
{
    /**
     * Expand the URI template using the supplied variables
     *
     * @param string $template  URI Template to expand
     * @param array  $variables Variables to use with the expansion
     *
     * @return string Returns the expanded template
     */
    public function expand($template, array $variables);
}
