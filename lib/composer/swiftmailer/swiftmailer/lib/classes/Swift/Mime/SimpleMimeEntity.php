<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * A MIME entity, in a multipart message.
 *
 * @author Chris Corbyn
 */
class Swift_Mime_SimpleMimeEntity implements Swift_Mime_CharsetObserver, Swift_Mime_EncodingObserver
{
    /** Main message document; there can only be one of these */
    const LEVEL_TOP = 16;

    /** An entity which nests with the same precedence as an attachment */
    const LEVEL_MIXED = 256;

    /** An entity which nests with the same precedence as a mime part */
    const LEVEL_ALTERNATIVE = 4096;

    /** An entity which nests with the same precedence as embedded content */
    const LEVEL_RELATED = 65536;

    /** A collection of Headers for this mime entity */
    private $headers;

    /** The body as a string, or a stream */
    private $body;

    /** The encoder that encodes the body into a streamable format */
    private $encoder;

    /** Message ID generator */
    private $idGenerator;

    /** A mime boundary, if any is used */
    private $boundary;

    /** Mime types to be used based on the nesting level */
    private $compositeRanges = [
        'multipart/mixed' => [self::LEVEL_TOP, self::LEVEL_MIXED],
        'multipart/alternative' => [self::LEVEL_MIXED, self::LEVEL_ALTERNATIVE],
        'multipart/related' => [self::LEVEL_ALTERNATIVE, self::LEVEL_RELATED],
    ];

    /** A set of filter rules to define what level an entity should be nested at */
    private $compoundLevelFilters = [];

    /** The nesting level of this entity */
    private $nestingLevel = self::LEVEL_ALTERNATIVE;

    /** A KeyCache instance used during encoding and streaming */
    private $cache;

    /** Direct descendants of this entity */
    private $immediateChildren = [];

    /** All descendants of this entity */
    private $children = [];

    /** The maximum line length of the body of this entity */
    private $maxLineLength = 78;

    /** The order in which alternative mime types should appear */
    private $alternativePartOrder = [
        'text/plain' => 1,
        'text/html' => 2,
        'multipart/related' => 3,
    ];

    /** The CID of this entity */
    private $id;

    /** The key used for accessing the cache */
    private $cacheKey;

    protected $userContentType;

    /**
     * Create a new SimpleMimeEntity with $headers, $encoder and $cache.
     */
    public function __construct(Swift_Mime_SimpleHeaderSet $headers, Swift_Mime_ContentEncoder $encoder, Swift_KeyCache $cache, Swift_IdGenerator $idGenerator)
    {
        $this->cacheKey = bin2hex(random_bytes(16)); // set 32 hex values
        $this->cache = $cache;
        $this->headers = $headers;
        $this->idGenerator = $idGenerator;
        $this->setEncoder($encoder);
        $this->headers->defineOrdering(['Content-Type', 'Content-Transfer-Encoding']);

        // This array specifies that, when the entire MIME document contains
        // $compoundLevel, then for each child within $level, if its Content-Type
        // is $contentType then it should be treated as if it's level is
        // $neededLevel instead.  I tried to write that unambiguously! :-\
        // Data Structure:
        // array (
        //   $compoundLevel => array(
        //     $level => array(
        //       $contentType => $neededLevel
        //     )
        //   )
        // )

        $this->compoundLevelFilters = [
            (self::LEVEL_ALTERNATIVE + self::LEVEL_RELATED) => [
                self::LEVEL_ALTERNATIVE => [
                    'text/plain' => self::LEVEL_ALTERNATIVE,
                    'text/html' => self::LEVEL_RELATED,
                    ],
                ],
            ];

        $this->id = $this->idGenerator->generateId();
    }

    /**
     * Generate a new Content-ID or Message-ID for this MIME entity.
     *
     * @return string
     */
    public function generateId()
    {
        $this->setId($this->idGenerator->generateId());

        return $this->id;
    }

    /**
     * Get the {@link Swift_Mime_SimpleHeaderSet} for this entity.
     *
     * @return Swift_Mime_SimpleHeaderSet
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get the nesting level of this entity.
     *
     * @see LEVEL_TOP, LEVEL_MIXED, LEVEL_RELATED, LEVEL_ALTERNATIVE
     *
     * @return int
     */
    public function getNestingLevel()
    {
        return $this->nestingLevel;
    }

    /**
     * Get the Content-type of this entity.
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->getHeaderFieldModel('Content-Type');
    }

    /**
     * Get the Body Content-type of this entity.
     *
     * @return string
     */
    public function getBodyContentType()
    {
        return $this->userContentType;
    }

    /**
     * Set the Content-type of this entity.
     *
     * @param string $type
     *
     * @return $this
     */
    public function setContentType($type)
    {
        $this->setContentTypeInHeaders($type);
        // Keep track of the value so that if the content-type changes automatically
        // due to added child entities, it can be restored if they are later removed
        $this->userContentType = $type;

        return $this;
    }

    /**
     * Get the CID of this entity.
     *
     * The CID will only be present in headers if a Content-ID header is present.
     *
     * @return string
     */
    public function getId()
    {
        $tmp = (array) $this->getHeaderFieldModel($this->getIdField());

        return $this->headers->has($this->getIdField()) ? current($tmp) : $this->id;
    }

    /**
     * Set the CID of this entity.
     *
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        if (!$this->setHeaderFieldModel($this->getIdField(), $id)) {
            $this->headers->addIdHeader($this->getIdField(), $id);
        }
        $this->id = $id;

        return $this;
    }

    /**
     * Get the description of this entity.
     *
     * This value comes from the Content-Description header if set.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->getHeaderFieldModel('Content-Description');
    }

    /**
     * Set the description of this entity.
     *
     * This method sets a value in the Content-ID header.
     *
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        if (!$this->setHeaderFieldModel('Content-Description', $description)) {
            $this->headers->addTextHeader('Content-Description', $description);
        }

        return $this;
    }

    /**
     * Get the maximum line length of the body of this entity.
     *
     * @return int
     */
    public function getMaxLineLength()
    {
        return $this->maxLineLength;
    }

    /**
     * Set the maximum line length of lines in this body.
     *
     * Though not enforced by the library, lines should not exceed 1000 chars.
     *
     * @param int $length
     *
     * @return $this
     */
    public function setMaxLineLength($length)
    {
        $this->maxLineLength = $length;

        return $this;
    }

    /**
     * Get all children added to this entity.
     *
     * @return Swift_Mime_SimpleMimeEntity[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set all children of this entity.
     *
     * @param Swift_Mime_SimpleMimeEntity[] $children
     * @param int                           $compoundLevel For internal use only
     *
     * @return $this
     */
    public function setChildren(array $children, $compoundLevel = null)
    {
        // TODO: Try to refactor this logic
        $compoundLevel = $compoundLevel ?? $this->getCompoundLevel($children);
        $immediateChildren = [];
        $grandchildren = [];
        $newContentType = $this->userContentType;

        foreach ($children as $child) {
            $level = $this->getNeededChildLevel($child, $compoundLevel);
            if (empty($immediateChildren)) {
                //first iteration
                $immediateChildren = [$child];
            } else {
                $nextLevel = $this->getNeededChildLevel($immediateChildren[0], $compoundLevel);
                if ($nextLevel == $level) {
                    $immediateChildren[] = $child;
                } elseif ($level < $nextLevel) {
                    // Re-assign immediateChildren to grandchildren
                    $grandchildren = array_merge($grandchildren, $immediateChildren);
                    // Set new children
                    $immediateChildren = [$child];
                } else {
                    $grandchildren[] = $child;
                }
            }
        }

        if ($immediateChildren) {
            $lowestLevel = $this->getNeededChildLevel($immediateChildren[0], $compoundLevel);

            // Determine which composite media type is needed to accommodate the
            // immediate children
            foreach ($this->compositeRanges as $mediaType => $range) {
                if ($lowestLevel > $range[0] && $lowestLevel <= $range[1]) {
                    $newContentType = $mediaType;

                    break;
                }
            }

            // Put any grandchildren in a subpart
            if (!empty($grandchildren)) {
                $subentity = $this->createChild();
                $subentity->setNestingLevel($lowestLevel);
                $subentity->setChildren($grandchildren, $compoundLevel);
                array_unshift($immediateChildren, $subentity);
            }
        }

        $this->immediateChildren = $immediateChildren;
        $this->children = $children;
        $this->setContentTypeInHeaders($newContentType);
        $this->fixHeaders();
        $this->sortChildren();

        return $this;
    }

    /**
     * Get the body of this entity as a string.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body instanceof Swift_OutputByteStream ? $this->readStream($this->body) : $this->body;
    }

    /**
     * Set the body of this entity, either as a string, or as an instance of
     * {@link Swift_OutputByteStream}.
     *
     * @param mixed  $body
     * @param string $contentType optional
     *
     * @return $this
     */
    public function setBody($body, $contentType = null)
    {
        if ($body !== $this->body) {
            $this->clearCache();
        }

        $this->body = $body;
        if (null !== $contentType) {
            $this->setContentType($contentType);
        }

        return $this;
    }

    /**
     * Get the encoder used for the body of this entity.
     *
     * @return Swift_Mime_ContentEncoder
     */
    public function getEncoder()
    {
        return $this->encoder;
    }

    /**
     * Set the encoder used for the body of this entity.
     *
     * @return $this
     */
    public function setEncoder(Swift_Mime_ContentEncoder $encoder)
    {
        if ($encoder !== $this->encoder) {
            $this->clearCache();
        }

        $this->encoder = $encoder;
        $this->setEncoding($encoder->getName());
        $this->notifyEncoderChanged($encoder);

        return $this;
    }

    /**
     * Get the boundary used to separate children in this entity.
     *
     * @return string
     */
    public function getBoundary()
    {
        if (!isset($this->boundary)) {
            $this->boundary = '_=_swift_'.time().'_'.bin2hex(random_bytes(16)).'_=_';
        }

        return $this->boundary;
    }

    /**
     * Set the boundary used to separate children in this entity.
     *
     * @param string $boundary
     *
     * @throws Swift_RfcComplianceException
     *
     * @return $this
     */
    public function setBoundary($boundary)
    {
        $this->assertValidBoundary($boundary);
        $this->boundary = $boundary;

        return $this;
    }

    /**
     * Receive notification that the charset of this entity, or a parent entity
     * has changed.
     *
     * @param string $charset
     */
    public function charsetChanged($charset)
    {
        $this->notifyCharsetChanged($charset);
    }

    /**
     * Receive notification that the encoder of this entity or a parent entity
     * has changed.
     */
    public function encoderChanged(Swift_Mime_ContentEncoder $encoder)
    {
        $this->notifyEncoderChanged($encoder);
    }

    /**
     * Get this entire entity as a string.
     *
     * @return string
     */
    public function toString()
    {
        $string = $this->headers->toString();
        $string .= $this->bodyToString();

        return $string;
    }

    /**
     * Get this entire entity as a string.
     *
     * @return string
     */
    protected function bodyToString()
    {
        $string = '';

        if (isset($this->body) && empty($this->immediateChildren)) {
            if ($this->cache->hasKey($this->cacheKey, 'body')) {
                $body = $this->cache->getString($this->cacheKey, 'body');
            } else {
                $body = "\r\n".$this->encoder->encodeString($this->getBody(), 0, $this->getMaxLineLength());
                $this->cache->setString($this->cacheKey, 'body', $body, Swift_KeyCache::MODE_WRITE);
            }
            $string .= $body;
        }

        if (!empty($this->immediateChildren)) {
            foreach ($this->immediateChildren as $child) {
                $string .= "\r\n\r\n--".$this->getBoundary()."\r\n";
                $string .= $child->toString();
            }
            $string .= "\r\n\r\n--".$this->getBoundary()."--\r\n";
        }

        return $string;
    }

    /**
     * Returns a string representation of this object.
     *
     * @see toString()
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Write this entire entity to a {@see Swift_InputByteStream}.
     */
    public function toByteStream(Swift_InputByteStream $is)
    {
        $is->write($this->headers->toString());
        $is->commit();

        $this->bodyToByteStream($is);
    }

    /**
     * Write this entire entity to a {@link Swift_InputByteStream}.
     */
    protected function bodyToByteStream(Swift_InputByteStream $is)
    {
        if (empty($this->immediateChildren)) {
            if (isset($this->body)) {
                if ($this->cache->hasKey($this->cacheKey, 'body')) {
                    $this->cache->exportToByteStream($this->cacheKey, 'body', $is);
                } else {
                    $cacheIs = $this->cache->getInputByteStream($this->cacheKey, 'body');
                    if ($cacheIs) {
                        $is->bind($cacheIs);
                    }

                    $is->write("\r\n");

                    if ($this->body instanceof Swift_OutputByteStream) {
                        $this->body->setReadPointer(0);

                        $this->encoder->encodeByteStream($this->body, $is, 0, $this->getMaxLineLength());
                    } else {
                        $is->write($this->encoder->encodeString($this->getBody(), 0, $this->getMaxLineLength()));
                    }

                    if ($cacheIs) {
                        $is->unbind($cacheIs);
                    }
                }
            }
        }

        if (!empty($this->immediateChildren)) {
            foreach ($this->immediateChildren as $child) {
                $is->write("\r\n\r\n--".$this->getBoundary()."\r\n");
                $child->toByteStream($is);
            }
            $is->write("\r\n\r\n--".$this->getBoundary()."--\r\n");
        }
    }

    /**
     * Get the name of the header that provides the ID of this entity.
     */
    protected function getIdField()
    {
        return 'Content-ID';
    }

    /**
     * Get the model data (usually an array or a string) for $field.
     */
    protected function getHeaderFieldModel($field)
    {
        if ($this->headers->has($field)) {
            return $this->headers->get($field)->getFieldBodyModel();
        }
    }

    /**
     * Set the model data for $field.
     */
    protected function setHeaderFieldModel($field, $model)
    {
        if ($this->headers->has($field)) {
            $this->headers->get($field)->setFieldBodyModel($model);

            return true;
        }

        return false;
    }

    /**
     * Get the parameter value of $parameter on $field header.
     */
    protected function getHeaderParameter($field, $parameter)
    {
        if ($this->headers->has($field)) {
            return $this->headers->get($field)->getParameter($parameter);
        }
    }

    /**
     * Set the parameter value of $parameter on $field header.
     */
    protected function setHeaderParameter($field, $parameter, $value)
    {
        if ($this->headers->has($field)) {
            $this->headers->get($field)->setParameter($parameter, $value);

            return true;
        }

        return false;
    }

    /**
     * Re-evaluate what content type and encoding should be used on this entity.
     */
    protected function fixHeaders()
    {
        if (\count($this->immediateChildren)) {
            $this->setHeaderParameter('Content-Type', 'boundary',
                $this->getBoundary()
                );
            $this->headers->remove('Content-Transfer-Encoding');
        } else {
            $this->setHeaderParameter('Content-Type', 'boundary', null);
            $this->setEncoding($this->encoder->getName());
        }
    }

    /**
     * Get the KeyCache used in this entity.
     *
     * @return Swift_KeyCache
     */
    protected function getCache()
    {
        return $this->cache;
    }

    /**
     * Get the ID generator.
     *
     * @return Swift_IdGenerator
     */
    protected function getIdGenerator()
    {
        return $this->idGenerator;
    }

    /**
     * Empty the KeyCache for this entity.
     */
    protected function clearCache()
    {
        $this->cache->clearKey($this->cacheKey, 'body');
    }

    private function readStream(Swift_OutputByteStream $os)
    {
        $string = '';
        while (false !== $bytes = $os->read(8192)) {
            $string .= $bytes;
        }

        $os->setReadPointer(0);

        return $string;
    }

    private function setEncoding($encoding)
    {
        if (!$this->setHeaderFieldModel('Content-Transfer-Encoding', $encoding)) {
            $this->headers->addTextHeader('Content-Transfer-Encoding', $encoding);
        }
    }

    private function assertValidBoundary($boundary)
    {
        if (!preg_match('/^[a-z0-9\'\(\)\+_\-,\.\/:=\?\ ]{0,69}[a-z0-9\'\(\)\+_\-,\.\/:=\?]$/Di', $boundary)) {
            throw new Swift_RfcComplianceException('Mime boundary set is not RFC 2046 compliant.');
        }
    }

    private function setContentTypeInHeaders($type)
    {
        if (!$this->setHeaderFieldModel('Content-Type', $type)) {
            $this->headers->addParameterizedHeader('Content-Type', $type);
        }
    }

    private function setNestingLevel($level)
    {
        $this->nestingLevel = $level;
    }

    private function getCompoundLevel($children)
    {
        $level = 0;
        foreach ($children as $child) {
            $level |= $child->getNestingLevel();
        }

        return $level;
    }

    private function getNeededChildLevel($child, $compoundLevel)
    {
        $filter = [];
        foreach ($this->compoundLevelFilters as $bitmask => $rules) {
            if (($compoundLevel & $bitmask) === $bitmask) {
                $filter = $rules + $filter;
            }
        }

        $realLevel = $child->getNestingLevel();
        $lowercaseType = strtolower($child->getContentType());

        if (isset($filter[$realLevel]) && isset($filter[$realLevel][$lowercaseType])) {
            return $filter[$realLevel][$lowercaseType];
        }

        return $realLevel;
    }

    private function createChild()
    {
        return new self($this->headers->newInstance(), $this->encoder, $this->cache, $this->idGenerator);
    }

    private function notifyEncoderChanged(Swift_Mime_ContentEncoder $encoder)
    {
        foreach ($this->immediateChildren as $child) {
            $child->encoderChanged($encoder);
        }
    }

    private function notifyCharsetChanged($charset)
    {
        $this->encoder->charsetChanged($charset);
        $this->headers->charsetChanged($charset);
        foreach ($this->immediateChildren as $child) {
            $child->charsetChanged($charset);
        }
    }

    private function sortChildren()
    {
        $shouldSort = false;
        foreach ($this->immediateChildren as $child) {
            // NOTE: This include alternative parts moved into a related part
            if (self::LEVEL_ALTERNATIVE == $child->getNestingLevel()) {
                $shouldSort = true;
                break;
            }
        }

        // Sort in order of preference, if there is one
        if ($shouldSort) {
            // Group the messages by order of preference
            $sorted = [];
            foreach ($this->immediateChildren as $child) {
                $type = $child->getContentType();
                $level = \array_key_exists($type, $this->alternativePartOrder) ? $this->alternativePartOrder[$type] : max($this->alternativePartOrder) + 1;

                if (empty($sorted[$level])) {
                    $sorted[$level] = [];
                }

                $sorted[$level][] = $child;
            }

            ksort($sorted);

            $this->immediateChildren = array_reduce($sorted, 'array_merge', []);
        }
    }

    /**
     * Empties it's own contents from the cache.
     */
    public function __destruct()
    {
        if ($this->cache instanceof Swift_KeyCache) {
            $this->cache->clearAll($this->cacheKey);
        }
    }

    /**
     * Make a deep copy of object.
     */
    public function __clone()
    {
        $this->headers = clone $this->headers;
        $this->encoder = clone $this->encoder;
        $this->cacheKey = bin2hex(random_bytes(16)); // set 32 hex values
        $children = [];
        foreach ($this->children as $pos => $child) {
            $children[$pos] = clone $child;
        }
        $this->setChildren($children);
    }

    public function __wakeup()
    {
        $this->cacheKey = bin2hex(random_bytes(16)); // set 32 hex values
        $this->cache = new Swift_KeyCache_ArrayKeyCache(new Swift_KeyCache_SimpleKeyCacheInputStream());
    }
}
