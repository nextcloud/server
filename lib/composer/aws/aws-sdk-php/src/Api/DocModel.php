<?php
namespace Aws\Api;

/**
 * Encapsulates the documentation strings for a given service-version and
 * provides methods for extracting the desired parts related to a service,
 * operation, error, or shape (i.e., parameter).
 */
class DocModel
{
    /** @var array */
    private $docs;

    /**
     * @param array $docs
     *
     * @throws \RuntimeException
     */
    public function __construct(array $docs)
    {
        if (!extension_loaded('tidy')) {
            throw new \RuntimeException('The "tidy" PHP extension is required.');
        }

        $this->docs = $docs;
    }

    /**
     * Convert the doc model to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->docs;
    }

    /**
     * Retrieves documentation about the service.
     *
     * @return null|string
     */
    public function getServiceDocs()
    {
        return isset($this->docs['service']) ? $this->docs['service'] : null;
    }

    /**
     * Retrieves documentation about an operation.
     *
     * @param string $operation Name of the operation
     *
     * @return null|string
     */
    public function getOperationDocs($operation)
    {
        return isset($this->docs['operations'][$operation])
            ? $this->docs['operations'][$operation]
            : null;
    }

    /**
     * Retrieves documentation about an error.
     *
     * @param string $error Name of the error
     *
     * @return null|string
     */
    public function getErrorDocs($error)
    {
        return isset($this->docs['shapes'][$error]['base'])
            ? $this->docs['shapes'][$error]['base']
            : null;
    }

    /**
     * Retrieves documentation about a shape, specific to the context.
     *
     * @param string $shapeName  Name of the shape.
     * @param string $parentName Name of the parent/context shape.
     * @param string $ref        Name used by the context to reference the shape.
     *
     * @return null|string
     */
    public function getShapeDocs($shapeName, $parentName, $ref)
    {
        if (!isset($this->docs['shapes'][$shapeName])) {
            return '';
        }

        $result = '';
        $d = $this->docs['shapes'][$shapeName];
        if (isset($d['refs']["{$parentName}\$${ref}"])) {
            $result = $d['refs']["{$parentName}\$${ref}"];
        } elseif (isset($d['base'])) {
            $result = $d['base'];
        }

        if (isset($d['append'])) {
            $result .= $d['append'];
        }

        return $this->clean($result);
    }

    private function clean($content)
    {
        if (!$content) {
            return '';
        }

        $tidy = new \tidy();
        $tidy->parseString($content, [
            'indent' => true,
            'doctype' => 'omit',
            'output-html' => true,
            'show-body-only' => true,
            'drop-empty-paras' => true,
            'drop-font-tags' => true,
            'drop-proprietary-attributes' => true,
            'hide-comments' => true,
            'logical-emphasis' => true
        ]);
        $tidy->cleanRepair();

        return (string) $content;
    }
}
