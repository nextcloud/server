<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Loader;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouteCompiler;

/**
 * XmlFileLoader loads XML routing files.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Tobias Schultze <http://tobion.de>
 */
class XmlFileLoader extends FileLoader
{
    public const NAMESPACE_URI = 'http://symfony.com/schema/routing';
    public const SCHEME_PATH = '/schema/routing/routing-1.0.xsd';

    /**
     * Loads an XML file.
     *
     * @param string      $file An XML file path
     * @param string|null $type The resource type
     *
     * @return RouteCollection A RouteCollection instance
     *
     * @throws \InvalidArgumentException when the file cannot be loaded or when the XML cannot be
     *                                   parsed because it does not validate against the scheme
     */
    public function load($file, $type = null)
    {
        $path = $this->locator->locate($file);

        $xml = $this->loadFile($path);

        $collection = new RouteCollection();
        $collection->addResource(new FileResource($path));

        // process routes and imports
        foreach ($xml->documentElement->childNodes as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }

            $this->parseNode($collection, $node, $path, $file);
        }

        return $collection;
    }

    /**
     * Parses a node from a loaded XML file.
     *
     * @param \DOMElement $node Element to parse
     * @param string      $path Full path of the XML file being processed
     * @param string      $file Loaded file name
     *
     * @throws \InvalidArgumentException When the XML is invalid
     */
    protected function parseNode(RouteCollection $collection, \DOMElement $node, $path, $file)
    {
        if (self::NAMESPACE_URI !== $node->namespaceURI) {
            return;
        }

        switch ($node->localName) {
            case 'route':
                $this->parseRoute($collection, $node, $path);
                break;
            case 'import':
                $this->parseImport($collection, $node, $path, $file);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unknown tag "%s" used in file "%s". Expected "route" or "import".', $node->localName, $path));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return \is_string($resource) && 'xml' === pathinfo($resource, \PATHINFO_EXTENSION) && (!$type || 'xml' === $type);
    }

    /**
     * Parses a route and adds it to the RouteCollection.
     *
     * @param \DOMElement $node Element to parse that represents a Route
     * @param string      $path Full path of the XML file being processed
     *
     * @throws \InvalidArgumentException When the XML is invalid
     */
    protected function parseRoute(RouteCollection $collection, \DOMElement $node, $path)
    {
        if ('' === $id = $node->getAttribute('id')) {
            throw new \InvalidArgumentException(sprintf('The <route> element in file "%s" must have an "id" attribute.', $path));
        }

        $schemes = preg_split('/[\s,\|]++/', $node->getAttribute('schemes'), -1, \PREG_SPLIT_NO_EMPTY);
        $methods = preg_split('/[\s,\|]++/', $node->getAttribute('methods'), -1, \PREG_SPLIT_NO_EMPTY);

        [$defaults, $requirements, $options, $condition, $paths] = $this->parseConfigs($node, $path);

        if (!$paths && '' === $node->getAttribute('path')) {
            throw new \InvalidArgumentException(sprintf('The <route> element in file "%s" must have a "path" attribute or <path> child nodes.', $path));
        }

        if ($paths && '' !== $node->getAttribute('path')) {
            throw new \InvalidArgumentException(sprintf('The <route> element in file "%s" must not have both a "path" attribute and <path> child nodes.', $path));
        }

        if (!$paths) {
            $route = new Route($node->getAttribute('path'), $defaults, $requirements, $options, $node->getAttribute('host'), $schemes, $methods, $condition);
            $collection->add($id, $route);
        } else {
            foreach ($paths as $locale => $p) {
                $defaults['_locale'] = $locale;
                $defaults['_canonical_route'] = $id;
                $requirements['_locale'] = preg_quote($locale, RouteCompiler::REGEX_DELIMITER);
                $route = new Route($p, $defaults, $requirements, $options, $node->getAttribute('host'), $schemes, $methods, $condition);
                $collection->add($id.'.'.$locale, $route);
            }
        }
    }

    /**
     * Parses an import and adds the routes in the resource to the RouteCollection.
     *
     * @param \DOMElement $node Element to parse that represents a Route
     * @param string      $path Full path of the XML file being processed
     * @param string      $file Loaded file name
     *
     * @throws \InvalidArgumentException When the XML is invalid
     */
    protected function parseImport(RouteCollection $collection, \DOMElement $node, $path, $file)
    {
        if ('' === $resource = $node->getAttribute('resource')) {
            throw new \InvalidArgumentException(sprintf('The <import> element in file "%s" must have a "resource" attribute.', $path));
        }

        $type = $node->getAttribute('type');
        $prefix = $node->getAttribute('prefix');
        $host = $node->hasAttribute('host') ? $node->getAttribute('host') : null;
        $schemes = $node->hasAttribute('schemes') ? preg_split('/[\s,\|]++/', $node->getAttribute('schemes'), -1, \PREG_SPLIT_NO_EMPTY) : null;
        $methods = $node->hasAttribute('methods') ? preg_split('/[\s,\|]++/', $node->getAttribute('methods'), -1, \PREG_SPLIT_NO_EMPTY) : null;
        $trailingSlashOnRoot = $node->hasAttribute('trailing-slash-on-root') ? XmlUtils::phpize($node->getAttribute('trailing-slash-on-root')) : true;

        [$defaults, $requirements, $options, $condition, /* $paths */, $prefixes] = $this->parseConfigs($node, $path);

        if ('' !== $prefix && $prefixes) {
            throw new \InvalidArgumentException(sprintf('The <route> element in file "%s" must not have both a "prefix" attribute and <prefix> child nodes.', $path));
        }

        $exclude = [];
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMElement && $child->localName === $exclude && self::NAMESPACE_URI === $child->namespaceURI) {
                $exclude[] = $child->nodeValue;
            }
        }

        if ($node->hasAttribute('exclude')) {
            if ($exclude) {
                throw new \InvalidArgumentException('You cannot use both the attribute "exclude" and <exclude> tags at the same time.');
            }
            $exclude = [$node->getAttribute('exclude')];
        }

        $this->setCurrentDir(\dirname($path));

        /** @var RouteCollection[] $imported */
        $imported = $this->import($resource, ('' !== $type ? $type : null), false, $file, $exclude) ?: [];

        if (!\is_array($imported)) {
            $imported = [$imported];
        }

        foreach ($imported as $subCollection) {
            /* @var $subCollection RouteCollection */
            if ('' !== $prefix || !$prefixes) {
                $subCollection->addPrefix($prefix);
                if (!$trailingSlashOnRoot) {
                    $rootPath = (new Route(trim(trim($prefix), '/').'/'))->getPath();
                    foreach ($subCollection->all() as $route) {
                        if ($route->getPath() === $rootPath) {
                            $route->setPath(rtrim($rootPath, '/'));
                        }
                    }
                }
            } else {
                foreach ($prefixes as $locale => $localePrefix) {
                    $prefixes[$locale] = trim(trim($localePrefix), '/');
                }
                foreach ($subCollection->all() as $name => $route) {
                    if (null === $locale = $route->getDefault('_locale')) {
                        $subCollection->remove($name);
                        foreach ($prefixes as $locale => $localePrefix) {
                            $localizedRoute = clone $route;
                            $localizedRoute->setPath($localePrefix.(!$trailingSlashOnRoot && '/' === $route->getPath() ? '' : $route->getPath()));
                            $localizedRoute->setDefault('_locale', $locale);
                            $localizedRoute->setRequirement('_locale', preg_quote($locale, RouteCompiler::REGEX_DELIMITER));
                            $localizedRoute->setDefault('_canonical_route', $name);
                            $subCollection->add($name.'.'.$locale, $localizedRoute);
                        }
                    } elseif (!isset($prefixes[$locale])) {
                        throw new \InvalidArgumentException(sprintf('Route "%s" with locale "%s" is missing a corresponding prefix when imported in "%s".', $name, $locale, $path));
                    } else {
                        $route->setPath($prefixes[$locale].(!$trailingSlashOnRoot && '/' === $route->getPath() ? '' : $route->getPath()));
                        $subCollection->add($name, $route);
                    }
                }
            }

            if (null !== $host) {
                $subCollection->setHost($host);
            }
            if (null !== $condition) {
                $subCollection->setCondition($condition);
            }
            if (null !== $schemes) {
                $subCollection->setSchemes($schemes);
            }
            if (null !== $methods) {
                $subCollection->setMethods($methods);
            }
            $subCollection->addDefaults($defaults);
            $subCollection->addRequirements($requirements);
            $subCollection->addOptions($options);

            if ($namePrefix = $node->getAttribute('name-prefix')) {
                $subCollection->addNamePrefix($namePrefix);
            }

            $collection->addCollection($subCollection);
        }
    }

    /**
     * Loads an XML file.
     *
     * @param string $file An XML file path
     *
     * @return \DOMDocument
     *
     * @throws \InvalidArgumentException When loading of XML file fails because of syntax errors
     *                                   or when the XML structure is not as expected by the scheme -
     *                                   see validate()
     */
    protected function loadFile($file)
    {
        return XmlUtils::loadFile($file, __DIR__.static::SCHEME_PATH);
    }

    /**
     * Parses the config elements (default, requirement, option).
     *
     * @throws \InvalidArgumentException When the XML is invalid
     */
    private function parseConfigs(\DOMElement $node, string $path): array
    {
        $defaults = [];
        $requirements = [];
        $options = [];
        $condition = null;
        $prefixes = [];
        $paths = [];

        /** @var \DOMElement $n */
        foreach ($node->getElementsByTagNameNS(self::NAMESPACE_URI, '*') as $n) {
            if ($node !== $n->parentNode) {
                continue;
            }

            switch ($n->localName) {
                case 'path':
                    $paths[$n->getAttribute('locale')] = trim($n->textContent);
                    break;
                case 'prefix':
                    $prefixes[$n->getAttribute('locale')] = trim($n->textContent);
                    break;
                case 'default':
                    if ($this->isElementValueNull($n)) {
                        $defaults[$n->getAttribute('key')] = null;
                    } else {
                        $defaults[$n->getAttribute('key')] = $this->parseDefaultsConfig($n, $path);
                    }

                    break;
                case 'requirement':
                    $requirements[$n->getAttribute('key')] = trim($n->textContent);
                    break;
                case 'option':
                    $options[$n->getAttribute('key')] = XmlUtils::phpize(trim($n->textContent));
                    break;
                case 'condition':
                    $condition = trim($n->textContent);
                    break;
                default:
                    throw new \InvalidArgumentException(sprintf('Unknown tag "%s" used in file "%s". Expected "default", "requirement", "option" or "condition".', $n->localName, $path));
            }
        }

        if ($controller = $node->getAttribute('controller')) {
            if (isset($defaults['_controller'])) {
                $name = $node->hasAttribute('id') ? sprintf('"%s".', $node->getAttribute('id')) : sprintf('the "%s" tag.', $node->tagName);

                throw new \InvalidArgumentException(sprintf('The routing file "%s" must not specify both the "controller" attribute and the defaults key "_controller" for ', $path).$name);
            }

            $defaults['_controller'] = $controller;
        }
        if ($node->hasAttribute('locale')) {
            $defaults['_locale'] = $node->getAttribute('locale');
        }
        if ($node->hasAttribute('format')) {
            $defaults['_format'] = $node->getAttribute('format');
        }
        if ($node->hasAttribute('utf8')) {
            $options['utf8'] = XmlUtils::phpize($node->getAttribute('utf8'));
        }

        return [$defaults, $requirements, $options, $condition, $paths, $prefixes];
    }

    /**
     * Parses the "default" elements.
     *
     * @return array|bool|float|int|string|null The parsed value of the "default" element
     */
    private function parseDefaultsConfig(\DOMElement $element, string $path)
    {
        if ($this->isElementValueNull($element)) {
            return null;
        }

        // Check for existing element nodes in the default element. There can
        // only be a single element inside a default element. So this element
        // (if one was found) can safely be returned.
        foreach ($element->childNodes as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }

            if (self::NAMESPACE_URI !== $child->namespaceURI) {
                continue;
            }

            return $this->parseDefaultNode($child, $path);
        }

        // If the default element doesn't contain a nested "bool", "int", "float",
        // "string", "list", or "map" element, the element contents will be treated
        // as the string value of the associated default option.
        return trim($element->textContent);
    }

    /**
     * Recursively parses the value of a "default" element.
     *
     * @return array|bool|float|int|string|null The parsed value
     *
     * @throws \InvalidArgumentException when the XML is invalid
     */
    private function parseDefaultNode(\DOMElement $node, string $path)
    {
        if ($this->isElementValueNull($node)) {
            return null;
        }

        switch ($node->localName) {
            case 'bool':
                return 'true' === trim($node->nodeValue) || '1' === trim($node->nodeValue);
            case 'int':
                return (int) trim($node->nodeValue);
            case 'float':
                return (float) trim($node->nodeValue);
            case 'string':
                return trim($node->nodeValue);
            case 'list':
                $list = [];

                foreach ($node->childNodes as $element) {
                    if (!$element instanceof \DOMElement) {
                        continue;
                    }

                    if (self::NAMESPACE_URI !== $element->namespaceURI) {
                        continue;
                    }

                    $list[] = $this->parseDefaultNode($element, $path);
                }

                return $list;
            case 'map':
                $map = [];

                foreach ($node->childNodes as $element) {
                    if (!$element instanceof \DOMElement) {
                        continue;
                    }

                    if (self::NAMESPACE_URI !== $element->namespaceURI) {
                        continue;
                    }

                    $map[$element->getAttribute('key')] = $this->parseDefaultNode($element, $path);
                }

                return $map;
            default:
                throw new \InvalidArgumentException(sprintf('Unknown tag "%s" used in file "%s". Expected "bool", "int", "float", "string", "list", or "map".', $node->localName, $path));
        }
    }

    private function isElementValueNull(\DOMElement $element): bool
    {
        $namespaceUri = 'http://www.w3.org/2001/XMLSchema-instance';

        if (!$element->hasAttributeNS($namespaceUri, 'nil')) {
            return false;
        }

        return 'true' === $element->getAttributeNS($namespaceUri, 'nil') || '1' === $element->getAttributeNS($namespaceUri, 'nil');
    }
}
