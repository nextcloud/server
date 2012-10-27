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

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Loader\FileLoader;

/**
 * XmlFileLoader loads XML routing files.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class XmlFileLoader extends FileLoader
{
    /**
     * Loads an XML file.
     *
     * @param string $file An XML file path
     * @param string $type The resource type
     *
     * @return RouteCollection A RouteCollection instance
     *
     * @throws \InvalidArgumentException When a tag can't be parsed
     *
     * @api
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
     * @param RouteCollection $collection the collection to associate with the node
     * @param DOMElement      $node       the node to parse
     * @param string          $path       the path of the XML file being processed
     * @param string          $file
     */
    protected function parseNode(RouteCollection $collection, \DOMElement $node, $path, $file)
    {
        switch ($node->tagName) {
            case 'route':
                $this->parseRoute($collection, $node, $path);
                break;
            case 'import':
                $resource = (string) $node->getAttribute('resource');
                $type = (string) $node->getAttribute('type');
                $prefix = (string) $node->getAttribute('prefix');
                $this->setCurrentDir(dirname($path));
                $collection->addCollection($this->import($resource, ('' !== $type ? $type : null), false, $file), $prefix);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unable to parse tag "%s"', $node->tagName));
        }
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean True if this class supports the given resource, false otherwise
     *
     * @api
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'xml' === pathinfo($resource, PATHINFO_EXTENSION) && (!$type || 'xml' === $type);
    }

    /**
     * Parses a route and adds it to the RouteCollection.
     *
     * @param RouteCollection $collection A RouteCollection instance
     * @param \DOMElement     $definition Route definition
     * @param string          $file       An XML file path
     *
     * @throws \InvalidArgumentException When the definition cannot be parsed
     */
    protected function parseRoute(RouteCollection $collection, \DOMElement $definition, $file)
    {
        $defaults = array();
        $requirements = array();
        $options = array();

        foreach ($definition->childNodes as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }

            switch ($node->tagName) {
                case 'default':
                    $defaults[(string) $node->getAttribute('key')] = trim((string) $node->nodeValue);
                    break;
                case 'option':
                    $options[(string) $node->getAttribute('key')] = trim((string) $node->nodeValue);
                    break;
                case 'requirement':
                    $requirements[(string) $node->getAttribute('key')] = trim((string) $node->nodeValue);
                    break;
                default:
                    throw new \InvalidArgumentException(sprintf('Unable to parse tag "%s"', $node->tagName));
            }
        }

        $route = new Route((string) $definition->getAttribute('pattern'), $defaults, $requirements, $options);

        $collection->add((string) $definition->getAttribute('id'), $route);
    }

    /**
     * Loads an XML file.
     *
     * @param string $file An XML file path
     *
     * @return \DOMDocument
     *
     * @throws \InvalidArgumentException When loading of XML file returns error
     */
    protected function loadFile($file)
    {
        $internalErrors = libxml_use_internal_errors(true);
        $disableEntities = libxml_disable_entity_loader(true);
        libxml_clear_errors();

        $dom = new \DOMDocument();
        $dom->validateOnParse = true;
        if (!$dom->loadXML(file_get_contents($file), LIBXML_NONET | (defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0))) {
            libxml_disable_entity_loader($disableEntities);

            throw new \InvalidArgumentException(implode("\n", $this->getXmlErrors($internalErrors)));
        }
        $dom->normalizeDocument();

        libxml_use_internal_errors($internalErrors);
        libxml_disable_entity_loader($disableEntities);

        foreach ($dom->childNodes as $child) {
            if ($child->nodeType === XML_DOCUMENT_TYPE_NODE) {
                throw new \InvalidArgumentException('Document types are not allowed.');
            }
        }

        $this->validate($dom);

        return $dom;
    }

    /**
     * Validates a loaded XML file.
     *
     * @param \DOMDocument $dom A loaded XML file
     *
     * @throws \InvalidArgumentException When XML doesn't validate its XSD schema
     */
    protected function validate(\DOMDocument $dom)
    {
        $location = __DIR__.'/schema/routing/routing-1.0.xsd';

        $current = libxml_use_internal_errors(true);
        libxml_clear_errors();

        if (!$dom->schemaValidate($location)) {
            throw new \InvalidArgumentException(implode("\n", $this->getXmlErrors($current)));
        }
        libxml_use_internal_errors($current);
    }

    /**
     * Retrieves libxml errors and clears them.
     *
     * @return array An array of libxml error strings
     */
    private function getXmlErrors($internalErrors)
    {
        $errors = array();
        foreach (libxml_get_errors() as $error) {
            $errors[] = sprintf('[%s %s] %s (in %s - line %d, column %d)',
                LIBXML_ERR_WARNING == $error->level ? 'WARNING' : 'ERROR',
                $error->code,
                trim($error->message),
                $error->file ? $error->file : 'n/a',
                $error->line,
                $error->column
            );
        }

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        return $errors;
    }
}
