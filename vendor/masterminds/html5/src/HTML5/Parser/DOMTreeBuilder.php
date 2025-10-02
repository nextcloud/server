<?php

namespace Masterminds\HTML5\Parser;

use Masterminds\HTML5\Elements;
use Masterminds\HTML5\InstructionProcessor;

/**
 * Create an HTML5 DOM tree from events.
 *
 * This attempts to create a DOM from events emitted by a parser. This
 * attempts (but does not guarantee) to up-convert older HTML documents
 * to HTML5. It does this by applying HTML5's rules, but it will not
 * change the architecture of the document itself.
 *
 * Many of the error correction and quirks features suggested in the specification
 * are implemented herein; however, not all of them are. Since we do not
 * assume a graphical user agent, no presentation-specific logic is conducted
 * during tree building.
 *
 * FIXME: The present tree builder does not exactly follow the state machine rules
 * for insert modes as outlined in the HTML5 spec. The processor needs to be
 * re-written to accomodate this. See, for example, the Go language HTML5
 * parser.
 */
class DOMTreeBuilder implements EventHandler
{
    /**
     * Defined in http://www.w3.org/TR/html51/infrastructure.html#html-namespace-0.
     */
    const NAMESPACE_HTML = 'http://www.w3.org/1999/xhtml';

    const NAMESPACE_MATHML = 'http://www.w3.org/1998/Math/MathML';

    const NAMESPACE_SVG = 'http://www.w3.org/2000/svg';

    const NAMESPACE_XLINK = 'http://www.w3.org/1999/xlink';

    const NAMESPACE_XML = 'http://www.w3.org/XML/1998/namespace';

    const NAMESPACE_XMLNS = 'http://www.w3.org/2000/xmlns/';

    const OPT_DISABLE_HTML_NS = 'disable_html_ns';

    const OPT_TARGET_DOC = 'target_document';

    const OPT_IMPLICIT_NS = 'implicit_namespaces';

    /**
     * Holds the HTML5 element names that causes a namespace switch.
     *
     * @var array
     */
    protected $nsRoots = array(
        'html' => self::NAMESPACE_HTML,
        'svg' => self::NAMESPACE_SVG,
        'math' => self::NAMESPACE_MATHML,
    );

    /**
     * Holds the always available namespaces (which does not require the XMLNS declaration).
     *
     * @var array
     */
    protected $implicitNamespaces = array(
        'xml' => self::NAMESPACE_XML,
        'xmlns' => self::NAMESPACE_XMLNS,
        'xlink' => self::NAMESPACE_XLINK,
    );

    /**
     * Holds a stack of currently active namespaces.
     *
     * @var array
     */
    protected $nsStack = array();

    /**
     * Holds the number of namespaces declared by a node.
     *
     * @var array
     */
    protected $pushes = array();

    /**
     * Defined in 8.2.5.
     */
    const IM_INITIAL = 0;

    const IM_BEFORE_HTML = 1;

    const IM_BEFORE_HEAD = 2;

    const IM_IN_HEAD = 3;

    const IM_IN_HEAD_NOSCRIPT = 4;

    const IM_AFTER_HEAD = 5;

    const IM_IN_BODY = 6;

    const IM_TEXT = 7;

    const IM_IN_TABLE = 8;

    const IM_IN_TABLE_TEXT = 9;

    const IM_IN_CAPTION = 10;

    const IM_IN_COLUMN_GROUP = 11;

    const IM_IN_TABLE_BODY = 12;

    const IM_IN_ROW = 13;

    const IM_IN_CELL = 14;

    const IM_IN_SELECT = 15;

    const IM_IN_SELECT_IN_TABLE = 16;

    const IM_AFTER_BODY = 17;

    const IM_IN_FRAMESET = 18;

    const IM_AFTER_FRAMESET = 19;

    const IM_AFTER_AFTER_BODY = 20;

    const IM_AFTER_AFTER_FRAMESET = 21;

    const IM_IN_SVG = 22;

    const IM_IN_MATHML = 23;

    protected $options = array();

    protected $stack = array();

    protected $current; // Pointer in the tag hierarchy.
    protected $rules;
    protected $doc;

    protected $frag;

    protected $processor;

    protected $insertMode = 0;

    /**
     * Track if we are in an element that allows only inline child nodes.
     *
     * @var string|null
     */
    protected $onlyInline;

    /**
     * Quirks mode is enabled by default.
     * Any document that is missing the DT will be considered to be in quirks mode.
     */
    protected $quirks = true;

    protected $errors = array();

    public function __construct($isFragment = false, array $options = array())
    {
        $this->options = $options;

        if (isset($options[self::OPT_TARGET_DOC])) {
            $this->doc = $options[self::OPT_TARGET_DOC];
        } else {
            $impl = new \DOMImplementation();
            // XXX:
            // Create the doctype. For now, we are always creating HTML5
            // documents, and attempting to up-convert any older DTDs to HTML5.
            $dt = $impl->createDocumentType('html');
            // $this->doc = \DOMImplementation::createDocument(NULL, 'html', $dt);
            $this->doc = $impl->createDocument(null, '', $dt);
            $this->doc->encoding = !empty($options['encoding']) ? $options['encoding'] : 'UTF-8';
        }

        $this->errors = array();

        $this->current = $this->doc; // ->documentElement;

        // Create a rules engine for tags.
        $this->rules = new TreeBuildingRules();

        $implicitNS = array();
        if (isset($this->options[self::OPT_IMPLICIT_NS])) {
            $implicitNS = $this->options[self::OPT_IMPLICIT_NS];
        } elseif (isset($this->options['implicitNamespaces'])) {
            $implicitNS = $this->options['implicitNamespaces'];
        }

        // Fill $nsStack with the defalut HTML5 namespaces, plus the "implicitNamespaces" array taken form $options
        array_unshift($this->nsStack, $implicitNS + array('' => self::NAMESPACE_HTML) + $this->implicitNamespaces);

        if ($isFragment) {
            $this->insertMode = static::IM_IN_BODY;
            $this->frag = $this->doc->createDocumentFragment();
            $this->current = $this->frag;
        }
    }

    /**
     * Get the document.
     */
    public function document()
    {
        return $this->doc;
    }

    /**
     * Get the DOM fragment for the body.
     *
     * This returns a DOMNodeList because a fragment may have zero or more
     * DOMNodes at its root.
     *
     * @see http://www.w3.org/TR/2012/CR-html5-20121217/syntax.html#concept-frag-parse-context
     *
     * @return \DOMDocumentFragment
     */
    public function fragment()
    {
        return $this->frag;
    }

    /**
     * Provide an instruction processor.
     *
     * This is used for handling Processor Instructions as they are
     * inserted. If omitted, PI's are inserted directly into the DOM tree.
     *
     * @param InstructionProcessor $proc
     */
    public function setInstructionProcessor(InstructionProcessor $proc)
    {
        $this->processor = $proc;
    }

    public function doctype($name, $idType = 0, $id = null, $quirks = false)
    {
        // This is used solely for setting quirks mode. Currently we don't
        // try to preserve the inbound DT. We convert it to HTML5.
        $this->quirks = $quirks;

        if ($this->insertMode > static::IM_INITIAL) {
            $this->parseError('Illegal placement of DOCTYPE tag. Ignoring: ' . $name);

            return;
        }

        $this->insertMode = static::IM_BEFORE_HTML;
    }

    /**
     * Process the start tag.
     *
     * @todo - XMLNS namespace handling (we need to parse, even if it's not valid)
     *       - XLink, MathML and SVG namespace handling
     *       - Omission rules: 8.1.2.4 Optional tags
     *
     * @param string $name
     * @param array  $attributes
     * @param bool   $selfClosing
     *
     * @return int
     */
    public function startTag($name, $attributes = array(), $selfClosing = false)
    {
        $lname = $this->normalizeTagName($name);

        // Make sure we have an html element.
        if (!$this->doc->documentElement && 'html' !== $name && !$this->frag) {
            $this->startTag('html');
        }

        // Set quirks mode if we're at IM_INITIAL with no doctype.
        if ($this->insertMode === static::IM_INITIAL) {
            $this->quirks = true;
            $this->parseError('No DOCTYPE specified.');
        }

        // SPECIAL TAG HANDLING:
        // Spec says do this, and "don't ask."
        // find the spec where this is defined... looks problematic
        if ('image' === $name && !($this->insertMode === static::IM_IN_SVG || $this->insertMode === static::IM_IN_MATHML)) {
            $name = 'img';
        }

        // Autoclose p tags where appropriate.
        if ($this->insertMode >= static::IM_IN_BODY && Elements::isA($name, Elements::AUTOCLOSE_P)) {
            $this->autoclose('p');
        }

        // Set insert mode:
        switch ($name) {
            case 'html':
                $this->insertMode = static::IM_BEFORE_HEAD;
                break;
            case 'head':
                if ($this->insertMode > static::IM_BEFORE_HEAD) {
                    $this->parseError('Unexpected head tag outside of head context.');
                } else {
                    $this->insertMode = static::IM_IN_HEAD;
                }
                break;
            case 'body':
                $this->insertMode = static::IM_IN_BODY;
                break;
            case 'svg':
                $this->insertMode = static::IM_IN_SVG;
                break;
            case 'math':
                $this->insertMode = static::IM_IN_MATHML;
                break;
            case 'noscript':
                if ($this->insertMode === static::IM_IN_HEAD) {
                    $this->insertMode = static::IM_IN_HEAD_NOSCRIPT;
                }
                break;
        }

        // Special case handling for SVG.
        if ($this->insertMode === static::IM_IN_SVG) {
            $lname = Elements::normalizeSvgElement($lname);
        }

        $pushes = 0;
        // when we found a tag thats appears inside $nsRoots, we have to switch the defalut namespace
        if (isset($this->nsRoots[$lname]) && $this->nsStack[0][''] !== $this->nsRoots[$lname]) {
            array_unshift($this->nsStack, array(
                '' => $this->nsRoots[$lname],
            ) + $this->nsStack[0]);
            ++$pushes;
        }
        $needsWorkaround = false;
        if (isset($this->options['xmlNamespaces']) && $this->options['xmlNamespaces']) {
            // when xmlNamespaces is true a and we found a 'xmlns' or 'xmlns:*' attribute, we should add a new item to the $nsStack
            foreach ($attributes as $aName => $aVal) {
                if ('xmlns' === $aName) {
                    $needsWorkaround = $aVal;
                    array_unshift($this->nsStack, array(
                        '' => $aVal,
                    ) + $this->nsStack[0]);
                    ++$pushes;
                } elseif ('xmlns' === (($pos = strpos($aName, ':')) ? substr($aName, 0, $pos) : '')) {
                    array_unshift($this->nsStack, array(
                        substr($aName, $pos + 1) => $aVal,
                    ) + $this->nsStack[0]);
                    ++$pushes;
                }
            }
        }

        if ($this->onlyInline && Elements::isA($lname, Elements::BLOCK_TAG)) {
            $this->autoclose($this->onlyInline);
            $this->onlyInline = null;
        }

        // some elements as table related tags might have optional end tags that force us to auto close multiple tags
        // https://www.w3.org/TR/html401/struct/tables.html
        if ($this->current instanceof \DOMElement && isset(Elements::$optionalEndElementsParentsToClose[$lname])) {
            foreach (Elements::$optionalEndElementsParentsToClose[$lname] as $parentElName) {
                if ($this->current instanceof \DOMElement && $this->current->tagName === $parentElName) {
                    $this->autoclose($parentElName);
                }
            }
        }

        try {
            $prefix = ($pos = strpos($lname, ':')) ? substr($lname, 0, $pos) : '';

            if (false !== $needsWorkaround) {
                $xml = "<$lname xmlns=\"$needsWorkaround\" " . (strlen($prefix) && isset($this->nsStack[0][$prefix]) ? ("xmlns:$prefix=\"" . $this->nsStack[0][$prefix] . '"') : '') . '/>';

                $frag = new \DOMDocument('1.0', 'UTF-8');
                $frag->loadXML($xml);

                $ele = $this->doc->importNode($frag->documentElement, true);
            } else {
                if (!isset($this->nsStack[0][$prefix]) || ('' === $prefix && isset($this->options[self::OPT_DISABLE_HTML_NS]) && $this->options[self::OPT_DISABLE_HTML_NS])) {
                    $ele = $this->doc->createElement($lname);
                } else {
                    $ele = $this->doc->createElementNS($this->nsStack[0][$prefix], $lname);
                }
            }
        } catch (\DOMException $e) {
            $this->parseError("Illegal tag name: <$lname>. Replaced with <invalid>.");
            $ele = $this->doc->createElement('invalid');
        }

        if (Elements::isA($lname, Elements::BLOCK_ONLY_INLINE)) {
            $this->onlyInline = $lname;
        }

        // When we add some namespacess, we have to track them. Later, when "endElement" is invoked, we have to remove them.
        // When we are on a void tag, we do not need to care about namesapce nesting.
        if ($pushes > 0 && !Elements::isA($name, Elements::VOID_TAG)) {
            // PHP tends to free the memory used by DOM,
            // to avoid spl_object_hash collisions whe have to avoid garbage collection of $ele storing it into $pushes
            // see https://bugs.php.net/bug.php?id=67459
            $this->pushes[spl_object_hash($ele)] = array($pushes, $ele);
        }

        foreach ($attributes as $aName => $aVal) {
            // xmlns attributes can't be set
            if ('xmlns' === $aName) {
                continue;
            }

            if ($this->insertMode === static::IM_IN_SVG) {
                $aName = Elements::normalizeSvgAttribute($aName);
            } elseif ($this->insertMode === static::IM_IN_MATHML) {
                $aName = Elements::normalizeMathMlAttribute($aName);
            }

            $aVal = (string) $aVal;

            try {
                $prefix = ($pos = strpos($aName, ':')) ? substr($aName, 0, $pos) : false;

                if ('xmlns' === $prefix) {
                    $ele->setAttributeNS(self::NAMESPACE_XMLNS, $aName, $aVal);
                } elseif (false !== $prefix && isset($this->nsStack[0][$prefix])) {
                    $ele->setAttributeNS($this->nsStack[0][$prefix], $aName, $aVal);
                } else {
                    $ele->setAttribute($aName, $aVal);
                }
            } catch (\DOMException $e) {
                $this->parseError("Illegal attribute name for tag $name. Ignoring: $aName");
                continue;
            }

            // This is necessary on a non-DTD schema, like HTML5.
            if ('id' === $aName) {
                $ele->setIdAttribute('id', true);
            }
        }

        if ($this->frag !== $this->current && $this->rules->hasRules($name)) {
            // Some elements have special processing rules. Handle those separately.
            $this->current = $this->rules->evaluate($ele, $this->current);
        } else {
            // Otherwise, it's a standard element.
            $this->current->appendChild($ele);

            if (!Elements::isA($name, Elements::VOID_TAG)) {
                $this->current = $ele;
            }

            // Self-closing tags should only be respected on foreign elements
            // (and are implied on void elements)
            // See: https://www.w3.org/TR/html5/syntax.html#start-tags
            if (Elements::isHtml5Element($name)) {
                $selfClosing = false;
            }
        }

        // This is sort of a last-ditch attempt to correct for cases where no head/body
        // elements are provided.
        if ($this->insertMode <= static::IM_BEFORE_HEAD && 'head' !== $name && 'html' !== $name) {
            $this->insertMode = static::IM_IN_BODY;
        }

        // When we are on a void tag, we do not need to care about namesapce nesting,
        // but we have to remove the namespaces pushed to $nsStack.
        if ($pushes > 0 && Elements::isA($name, Elements::VOID_TAG)) {
            // remove the namespaced definded by current node
            for ($i = 0; $i < $pushes; ++$i) {
                array_shift($this->nsStack);
            }
        }

        if ($selfClosing) {
            $this->endTag($name);
        }

        // Return the element mask, which the tokenizer can then use to set
        // various processing rules.
        return Elements::element($name);
    }

    public function endTag($name)
    {
        $lname = $this->normalizeTagName($name);

        // Special case within 12.2.6.4.7: An end tag whose tag name is "br" should be treated as an opening tag
        if ('br' === $name) {
            $this->parseError('Closing tag encountered for void element br.');

            $this->startTag('br');
        }
        // Ignore closing tags for other unary elements.
        elseif (Elements::isA($name, Elements::VOID_TAG)) {
            return;
        }

        if ($this->insertMode <= static::IM_BEFORE_HTML) {
            // 8.2.5.4.2
            if (in_array($name, array(
                'html',
                'br',
                'head',
                'title',
            ))) {
                $this->startTag('html');
                $this->endTag($name);
                $this->insertMode = static::IM_BEFORE_HEAD;

                return;
            }

            // Ignore the tag.
            $this->parseError('Illegal closing tag at global scope.');

            return;
        }

        // Special case handling for SVG.
        if ($this->insertMode === static::IM_IN_SVG) {
            $lname = Elements::normalizeSvgElement($lname);
        }

        $cid = spl_object_hash($this->current);

        // XXX: HTML has no parent. What do we do, though,
        // if this element appears in the wrong place?
        if ('html' === $lname) {
            return;
        }

        // remove the namespaced definded by current node
        if (isset($this->pushes[$cid])) {
            for ($i = 0; $i < $this->pushes[$cid][0]; ++$i) {
                array_shift($this->nsStack);
            }
            unset($this->pushes[$cid]);
        }

        if (!$this->autoclose($lname)) {
            $this->parseError('Could not find closing tag for ' . $lname);
        }

        switch ($lname) {
            case 'head':
                $this->insertMode = static::IM_AFTER_HEAD;
                break;
            case 'body':
                $this->insertMode = static::IM_AFTER_BODY;
                break;
            case 'svg':
            case 'mathml':
                $this->insertMode = static::IM_IN_BODY;
                break;
        }
    }

    public function comment($cdata)
    {
        // TODO: Need to handle case where comment appears outside of the HTML tag.
        $node = $this->doc->createComment($cdata);
        $this->current->appendChild($node);
    }

    public function text($data)
    {
        // XXX: Hmmm.... should we really be this strict?
        if ($this->insertMode < static::IM_IN_HEAD) {
            // Per '8.2.5.4.3 The "before head" insertion mode' the characters
            // " \t\n\r\f" should be ignored but no mention of a parse error. This is
            // practical as most documents contain these characters. Other text is not
            // expected here so recording a parse error is necessary.
            $dataTmp = trim($data, " \t\n\r\f");
            if (!empty($dataTmp)) {
                // fprintf(STDOUT, "Unexpected insert mode: %d", $this->insertMode);
                $this->parseError('Unexpected text. Ignoring: ' . $dataTmp);
            }

            return;
        }
        // fprintf(STDOUT, "Appending text %s.", $data);
        $node = $this->doc->createTextNode($data);
        $this->current->appendChild($node);
    }

    public function eof()
    {
        // If the $current isn't the $root, do we need to do anything?
    }

    public function parseError($msg, $line = 0, $col = 0)
    {
        $this->errors[] = sprintf('Line %d, Col %d: %s', $line, $col, $msg);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function cdata($data)
    {
        $node = $this->doc->createCDATASection($data);
        $this->current->appendChild($node);
    }

    public function processingInstruction($name, $data = null)
    {
        // XXX: Ignore initial XML declaration, per the spec.
        if ($this->insertMode === static::IM_INITIAL && 'xml' === strtolower($name)) {
            return;
        }

        // Important: The processor may modify the current DOM tree however it sees fit.
        if ($this->processor instanceof InstructionProcessor) {
            $res = $this->processor->process($this->current, $name, $data);
            if (!empty($res)) {
                $this->current = $res;
            }

            return;
        }

        // Otherwise, this is just a dumb PI element.
        $node = $this->doc->createProcessingInstruction($name, $data);

        $this->current->appendChild($node);
    }

    // ==========================================================================
    // UTILITIES
    // ==========================================================================

    /**
     * Apply normalization rules to a tag name.
     * See sections 2.9 and 8.1.2.
     *
     * @param string $tagName
     *
     * @return string The normalized tag name.
     */
    protected function normalizeTagName($tagName)
    {
        /*
         * Section 2.9 suggests that we should not do this. if (strpos($name, ':') !== false) { // We know from the grammar that there must be at least one other // char besides :, since : is not a legal tag start. $parts = explode(':', $name); return array_pop($parts); }
         */
        return $tagName;
    }

    protected function quirksTreeResolver($name)
    {
        throw new \Exception('Not implemented.');
    }

    /**
     * Automatically climb the tree and close the closest node with the matching $tag.
     *
     * @param string $tagName
     *
     * @return bool
     */
    protected function autoclose($tagName)
    {
        $working = $this->current;
        do {
            if (XML_ELEMENT_NODE !== $working->nodeType) {
                return false;
            }
            if ($working->tagName === $tagName) {
                $this->current = $working->parentNode;

                return true;
            }
        } while ($working = $working->parentNode);

        return false;
    }

    /**
     * Checks if the given tagname is an ancestor of the present candidate.
     *
     * If $this->current or anything above $this->current matches the given tag
     * name, this returns true.
     *
     * @param string $tagName
     *
     * @return bool
     */
    protected function isAncestor($tagName)
    {
        $candidate = $this->current;
        while (XML_ELEMENT_NODE === $candidate->nodeType) {
            if ($candidate->tagName === $tagName) {
                return true;
            }
            $candidate = $candidate->parentNode;
        }

        return false;
    }

    /**
     * Returns true if the immediate parent element is of the given tagname.
     *
     * @param string $tagName
     *
     * @return bool
     */
    protected function isParent($tagName)
    {
        return $this->current->tagName === $tagName;
    }
}
