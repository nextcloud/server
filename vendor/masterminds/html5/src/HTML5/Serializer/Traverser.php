<?php

namespace Masterminds\HTML5\Serializer;

/**
 * Traverser for walking a DOM tree.
 *
 * This is a concrete traverser designed to convert a DOM tree into an
 * HTML5 document. It is not intended to be a generic DOMTreeWalker
 * implementation.
 *
 * @see http://www.w3.org/TR/2012/CR-html5-20121217/syntax.html#serializing-html-fragments
 */
class Traverser
{
    /**
     * Namespaces that should be treated as "local" to HTML5.
     */
    protected static $local_ns = array(
        'http://www.w3.org/1999/xhtml' => 'html',
        'http://www.w3.org/1998/Math/MathML' => 'math',
        'http://www.w3.org/2000/svg' => 'svg',
    );

    protected $dom;

    protected $options;

    protected $encode = false;

    protected $rules;

    protected $out;

    /**
     * Create a traverser.
     *
     * @param \DOMNode|\DOMNodeList $dom     The document or node to traverse.
     * @param resource              $out     A stream that allows writing. The traverser will output into this
     *                                       stream.
     * @param array                 $options An array of options for the traverser as key/value pairs. These include:
     *                                       - encode_entities: A bool to specify if full encding should happen for all named
     *                                       charachter references. Defaults to false which escapes &'<>".
     *                                       - output_rules: The path to the class handling the output rules.
     */
    public function __construct($dom, $out, RulesInterface $rules, $options = array())
    {
        $this->dom = $dom;
        $this->out = $out;
        $this->rules = $rules;
        $this->options = $options;

        $this->rules->setTraverser($this);
    }

    /**
     * Tell the traverser to walk the DOM.
     *
     * @return resource $out Returns the output stream.
     */
    public function walk()
    {
        if ($this->dom instanceof \DOMDocument) {
            $this->rules->document($this->dom);
        } elseif ($this->dom instanceof \DOMDocumentFragment) {
            // Document fragments are a special case. Only the children need to
            // be serialized.
            if ($this->dom->hasChildNodes()) {
                $this->children($this->dom->childNodes);
            }
        }        // If NodeList, loop
        elseif ($this->dom instanceof \DOMNodeList) {
            // If this is a NodeList of DOMDocuments this will not work.
            $this->children($this->dom);
        }         // Else assume this is a DOMNode-like datastructure.
        else {
            $this->node($this->dom);
        }

        return $this->out;
    }

    /**
     * Process a node in the DOM.
     *
     * @param mixed $node A node implementing \DOMNode.
     */
    public function node($node)
    {
        // A listing of types is at http://php.net/manual/en/dom.constants.php
        switch ($node->nodeType) {
            case XML_ELEMENT_NODE:
                $this->rules->element($node);
                break;
            case XML_TEXT_NODE:
                $this->rules->text($node);
                break;
            case XML_CDATA_SECTION_NODE:
                $this->rules->cdata($node);
                break;
            case XML_PI_NODE:
                $this->rules->processorInstruction($node);
                break;
            case XML_COMMENT_NODE:
                $this->rules->comment($node);
                break;
            // Currently we don't support embedding DTDs.
            default:
                //print '<!-- Skipped -->';
                break;
        }
    }

    /**
     * Walk through all the nodes on a node list.
     *
     * @param \DOMNodeList $nl A list of child elements to walk through.
     */
    public function children($nl)
    {
        foreach ($nl as $node) {
            $this->node($node);
        }
    }

    /**
     * Is an element local?
     *
     * @param mixed $ele An element that implement \DOMNode.
     *
     * @return bool true if local and false otherwise.
     */
    public function isLocalElement($ele)
    {
        $uri = $ele->namespaceURI;
        if (empty($uri)) {
            return false;
        }

        return isset(static::$local_ns[$uri]);
    }
}
