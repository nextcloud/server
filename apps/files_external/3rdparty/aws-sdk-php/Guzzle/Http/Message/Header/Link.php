<?php

namespace Guzzle\Http\Message\Header;

use Guzzle\Http\Message\Header;

/**
 * Provides helpful functionality for link headers
 */
class Link extends Header
{
    /**
     * Add a link to the header
     *
     * @param string $url    Link URL
     * @param string $rel    Link rel
     * @param array  $params Other link parameters
     *
     * @return self
     */
    public function addLink($url, $rel, array $params = array())
    {
        $values = array("<{$url}>", "rel=\"{$rel}\"");

        foreach ($params as $k => $v) {
            $values[] = "{$k}=\"{$v}\"";
        }

        return $this->add(implode('; ', $values));
    }

    /**
     * Check if a specific link exists for a given rel attribute
     *
     * @param string $rel rel value
     *
     * @return bool
     */
    public function hasLink($rel)
    {
        return $this->getLink($rel) !== null;
    }

    /**
     * Get a specific link for a given rel attribute
     *
     * @param string $rel Rel value
     *
     * @return array|null
     */
    public function getLink($rel)
    {
        foreach ($this->getLinks() as $link) {
            if (isset($link['rel']) && $link['rel'] == $rel) {
                return $link;
            }
        }

        return null;
    }

    /**
     * Get an associative array of links
     *
     * For example:
     * Link: <http:/.../front.jpeg>; rel=front; type="image/jpeg", <http://.../back.jpeg>; rel=back; type="image/jpeg"
     *
     * <code>
     * var_export($response->getLinks());
     * array(
     *     array(
     *         'url' => 'http:/.../front.jpeg',
     *         'rel' => 'back',
     *         'type' => 'image/jpeg',
     *     )
     * )
     * </code>
     *
     * @return array
     */
    public function getLinks()
    {
        $links = $this->parseParams();

        foreach ($links as &$link) {
            $key = key($link);
            unset($link[$key]);
            $link['url'] = trim($key, '<> ');
        }

        return $links;
    }
}
