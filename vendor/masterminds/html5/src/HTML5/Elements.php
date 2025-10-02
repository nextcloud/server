<?php
/**
 * Provide general element functions.
 */

namespace Masterminds\HTML5;

/**
 * This class provides general information about HTML5 elements,
 * including syntactic and semantic issues.
 * Parsers and serializers can
 * use this class as a reference point for information about the rules
 * of various HTML5 elements.
 *
 * @todo consider using a bitmask table lookup. There is enough overlap in
 *       naming that this could significantly shrink the size and maybe make it
 *       faster. See the Go teams implementation at https://code.google.com/p/go/source/browse/html/atom.
 */
class Elements
{
    /**
     * Indicates an element is described in the specification.
     */
    const KNOWN_ELEMENT = 1;

    // From section 8.1.2: "script", "style"
    // From 8.2.5.4.7 ("in body" insertion mode): "noembed"
    // From 8.4 "style", "xmp", "iframe", "noembed", "noframes"
    /**
     * Indicates the contained text should be processed as raw text.
     */
    const TEXT_RAW = 2;

    // From section 8.1.2: "textarea", "title"
    /**
     * Indicates the contained text should be processed as RCDATA.
     */
    const TEXT_RCDATA = 4;

    /**
     * Indicates the tag cannot have content.
     */
    const VOID_TAG = 8;

    // "address", "article", "aside", "blockquote", "center", "details", "dialog", "dir", "div", "dl",
    // "fieldset", "figcaption", "figure", "footer", "header", "hgroup", "menu",
    // "nav", "ol", "p", "section", "summary", "ul"
    // "h1", "h2", "h3", "h4", "h5", "h6"
    // "pre", "listing"
    // "form"
    // "plaintext"
    /**
     * Indicates that if a previous event is for a P tag, that element
     * should be considered closed.
     */
    const AUTOCLOSE_P = 16;

    /**
     * Indicates that the text inside is plaintext (pre).
     */
    const TEXT_PLAINTEXT = 32;

    // See https://developer.mozilla.org/en-US/docs/HTML/Block-level_elements
    /**
     * Indicates that the tag is a block.
     */
    const BLOCK_TAG = 64;

    /**
     * Indicates that the tag allows only inline elements as child nodes.
     */
    const BLOCK_ONLY_INLINE = 128;

    /**
     * Elements with optional end tags that cause auto-closing of previous and parent tags,
     * as example most of the table related tags, see https://www.w3.org/TR/html401/struct/tables.html
     * Structure is as follows:
     * TAG-NAME => [PARENT-TAG-NAME-TO-CLOSE1, PARENT-TAG-NAME-TO-CLOSE2, ...].
     *
     * Order is important, after auto-closing one parent with might have to close also their parent.
     *
     * @var array<string, string[]>
     */
    public static $optionalEndElementsParentsToClose = array(
        'tr' => array('td', 'tr'),
        'td' => array('td', 'th'),
        'th' => array('td', 'th'),
        'tfoot' => array('td', 'th', 'tr', 'tbody', 'thead'),
        'tbody' => array('td', 'th', 'tr', 'thead'),
    );

    /**
     * The HTML5 elements as defined in http://dev.w3.org/html5/markup/elements.html.
     *
     * @var array
     */
    public static $html5 = array(
        'a' => 1,
        'abbr' => 1,
        'address' => 65, // NORMAL | BLOCK_TAG
        'area' => 9, // NORMAL | VOID_TAG
        'article' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'aside' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'audio' => 1, // NORMAL
        'b' => 1,
        'base' => 9, // NORMAL | VOID_TAG
        'bdi' => 1,
        'bdo' => 1,
        'blockquote' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'body' => 1,
        'br' => 9, // NORMAL | VOID_TAG
        'button' => 1,
        'canvas' => 65, // NORMAL | BLOCK_TAG
        'caption' => 1,
        'cite' => 1,
        'code' => 1,
        'col' => 9, // NORMAL | VOID_TAG
        'colgroup' => 1,
        'command' => 9, // NORMAL | VOID_TAG
                        // "data" => 1, // This is highly experimental and only part of the whatwg spec (not w3c). See https://developer.mozilla.org/en-US/docs/HTML/Element/data
        'datalist' => 1,
        'dd' => 65, // NORMAL | BLOCK_TAG
        'del' => 1,
        'details' => 17, // NORMAL | AUTOCLOSE_P,
        'dfn' => 1,
        'dialog' => 17, // NORMAL | AUTOCLOSE_P,
        'div' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'dl' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'dt' => 1,
        'em' => 1,
        'embed' => 9, // NORMAL | VOID_TAG
        'fieldset' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'figcaption' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'figure' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'footer' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'form' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'h1' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'h2' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'h3' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'h4' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'h5' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'h6' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'head' => 1,
        'header' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'hgroup' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'hr' => 73, // NORMAL | VOID_TAG
        'html' => 1,
        'i' => 1,
        'iframe' => 3, // NORMAL | TEXT_RAW
        'img' => 9, // NORMAL | VOID_TAG
        'input' => 9, // NORMAL | VOID_TAG
        'kbd' => 1,
        'ins' => 1,
        'keygen' => 9, // NORMAL | VOID_TAG
        'label' => 1,
        'legend' => 1,
        'li' => 1,
        'link' => 9, // NORMAL | VOID_TAG
        'map' => 1,
        'mark' => 1,
        'menu' => 17, // NORMAL | AUTOCLOSE_P,
        'meta' => 9, // NORMAL | VOID_TAG
        'meter' => 1,
        'nav' => 17, // NORMAL | AUTOCLOSE_P,
        'noscript' => 65, // NORMAL | BLOCK_TAG
        'object' => 1,
        'ol' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'optgroup' => 1,
        'option' => 1,
        'output' => 65, // NORMAL | BLOCK_TAG
        'p' => 209, // NORMAL | AUTOCLOSE_P | BLOCK_TAG | BLOCK_ONLY_INLINE
        'param' => 9, // NORMAL | VOID_TAG
        'pre' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'progress' => 1,
        'q' => 1,
        'rp' => 1,
        'rt' => 1,
        'ruby' => 1,
        's' => 1,
        'samp' => 1,
        'script' => 3, // NORMAL | TEXT_RAW
        'section' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'select' => 1,
        'small' => 1,
        'source' => 9, // NORMAL | VOID_TAG
        'span' => 1,
        'strong' => 1,
        'style' => 3, // NORMAL | TEXT_RAW
        'sub' => 1,
        'summary' => 17, // NORMAL | AUTOCLOSE_P,
        'sup' => 1,
        'table' => 65, // NORMAL | BLOCK_TAG
        'tbody' => 1,
        'td' => 1,
        'textarea' => 5, // NORMAL | TEXT_RCDATA
        'tfoot' => 65, // NORMAL | BLOCK_TAG
        'th' => 1,
        'thead' => 1,
        'time' => 1,
        'title' => 5, // NORMAL | TEXT_RCDATA
        'tr' => 1,
        'track' => 9, // NORMAL | VOID_TAG
        'u' => 1,
        'ul' => 81, // NORMAL | AUTOCLOSE_P | BLOCK_TAG
        'var' => 1,
        'video' => 1,
        'wbr' => 9, // NORMAL | VOID_TAG

        // Legacy?
        'basefont' => 8, // VOID_TAG
        'bgsound' => 8, // VOID_TAG
        'noframes' => 2, // RAW_TEXT
        'frame' => 9, // NORMAL | VOID_TAG
        'frameset' => 1,
        'center' => 16,
        'dir' => 16,
        'listing' => 16, // AUTOCLOSE_P
        'plaintext' => 48, // AUTOCLOSE_P | TEXT_PLAINTEXT
        'applet' => 0,
        'marquee' => 0,
        'isindex' => 8, // VOID_TAG
        'xmp' => 20, // AUTOCLOSE_P | VOID_TAG | RAW_TEXT
        'noembed' => 2, // RAW_TEXT
        );

    /**
     * The MathML elements.
     * See http://www.w3.org/wiki/MathML/Elements.
     *
     * In our case we are only concerned with presentation MathML and not content
     * MathML. There is a nice list of this subset at https://developer.mozilla.org/en-US/docs/MathML/Element.
     *
     * @var array
     */
    public static $mathml = array(
        'maction' => 1,
        'maligngroup' => 1,
        'malignmark' => 1,
        'math' => 1,
        'menclose' => 1,
        'merror' => 1,
        'mfenced' => 1,
        'mfrac' => 1,
        'mglyph' => 1,
        'mi' => 1,
        'mlabeledtr' => 1,
        'mlongdiv' => 1,
        'mmultiscripts' => 1,
        'mn' => 1,
        'mo' => 1,
        'mover' => 1,
        'mpadded' => 1,
        'mphantom' => 1,
        'mroot' => 1,
        'mrow' => 1,
        'ms' => 1,
        'mscarries' => 1,
        'mscarry' => 1,
        'msgroup' => 1,
        'msline' => 1,
        'mspace' => 1,
        'msqrt' => 1,
        'msrow' => 1,
        'mstack' => 1,
        'mstyle' => 1,
        'msub' => 1,
        'msup' => 1,
        'msubsup' => 1,
        'mtable' => 1,
        'mtd' => 1,
        'mtext' => 1,
        'mtr' => 1,
        'munder' => 1,
        'munderover' => 1,
    );

    /**
     * The svg elements.
     *
     * The Mozilla documentation has a good list at https://developer.mozilla.org/en-US/docs/SVG/Element.
     * The w3c list appears to be lacking in some areas like filter effect elements.
     * That list can be found at http://www.w3.org/wiki/SVG/Elements.
     *
     * Note, FireFox appears to do a better job rendering filter effects than chrome.
     * While they are in the spec I'm not sure how widely implemented they are.
     *
     * @var array
     */
    public static $svg = array(
        'a' => 1,
        'altGlyph' => 1,
        'altGlyphDef' => 1,
        'altGlyphItem' => 1,
        'animate' => 1,
        'animateColor' => 1,
        'animateMotion' => 1,
        'animateTransform' => 1,
        'circle' => 1,
        'clipPath' => 1,
        'color-profile' => 1,
        'cursor' => 1,
        'defs' => 1,
        'desc' => 1,
        'ellipse' => 1,
        'feBlend' => 1,
        'feColorMatrix' => 1,
        'feComponentTransfer' => 1,
        'feComposite' => 1,
        'feConvolveMatrix' => 1,
        'feDiffuseLighting' => 1,
        'feDisplacementMap' => 1,
        'feDistantLight' => 1,
        'feFlood' => 1,
        'feFuncA' => 1,
        'feFuncB' => 1,
        'feFuncG' => 1,
        'feFuncR' => 1,
        'feGaussianBlur' => 1,
        'feImage' => 1,
        'feMerge' => 1,
        'feMergeNode' => 1,
        'feMorphology' => 1,
        'feOffset' => 1,
        'fePointLight' => 1,
        'feSpecularLighting' => 1,
        'feSpotLight' => 1,
        'feTile' => 1,
        'feTurbulence' => 1,
        'filter' => 1,
        'font' => 1,
        'font-face' => 1,
        'font-face-format' => 1,
        'font-face-name' => 1,
        'font-face-src' => 1,
        'font-face-uri' => 1,
        'foreignObject' => 1,
        'g' => 1,
        'glyph' => 1,
        'glyphRef' => 1,
        'hkern' => 1,
        'image' => 1,
        'line' => 1,
        'linearGradient' => 1,
        'marker' => 1,
        'mask' => 1,
        'metadata' => 1,
        'missing-glyph' => 1,
        'mpath' => 1,
        'path' => 1,
        'pattern' => 1,
        'polygon' => 1,
        'polyline' => 1,
        'radialGradient' => 1,
        'rect' => 1,
        'script' => 3, // NORMAL | RAW_TEXT
        'set' => 1,
        'stop' => 1,
        'style' => 3, // NORMAL | RAW_TEXT
        'svg' => 1,
        'switch' => 1,
        'symbol' => 1,
        'text' => 1,
        'textPath' => 1,
        'title' => 1,
        'tref' => 1,
        'tspan' => 1,
        'use' => 1,
        'view' => 1,
        'vkern' => 1,
    );

    /**
     * Some attributes in SVG are case sensitive.
     *
     * This map contains key/value pairs with the key as the lowercase attribute
     * name and the value with the correct casing.
     */
    public static $svgCaseSensitiveAttributeMap = array(
        'attributename' => 'attributeName',
        'attributetype' => 'attributeType',
        'basefrequency' => 'baseFrequency',
        'baseprofile' => 'baseProfile',
        'calcmode' => 'calcMode',
        'clippathunits' => 'clipPathUnits',
        'contentscripttype' => 'contentScriptType',
        'contentstyletype' => 'contentStyleType',
        'diffuseconstant' => 'diffuseConstant',
        'edgemode' => 'edgeMode',
        'externalresourcesrequired' => 'externalResourcesRequired',
        'filterres' => 'filterRes',
        'filterunits' => 'filterUnits',
        'glyphref' => 'glyphRef',
        'gradienttransform' => 'gradientTransform',
        'gradientunits' => 'gradientUnits',
        'kernelmatrix' => 'kernelMatrix',
        'kernelunitlength' => 'kernelUnitLength',
        'keypoints' => 'keyPoints',
        'keysplines' => 'keySplines',
        'keytimes' => 'keyTimes',
        'lengthadjust' => 'lengthAdjust',
        'limitingconeangle' => 'limitingConeAngle',
        'markerheight' => 'markerHeight',
        'markerunits' => 'markerUnits',
        'markerwidth' => 'markerWidth',
        'maskcontentunits' => 'maskContentUnits',
        'maskunits' => 'maskUnits',
        'numoctaves' => 'numOctaves',
        'pathlength' => 'pathLength',
        'patterncontentunits' => 'patternContentUnits',
        'patterntransform' => 'patternTransform',
        'patternunits' => 'patternUnits',
        'pointsatx' => 'pointsAtX',
        'pointsaty' => 'pointsAtY',
        'pointsatz' => 'pointsAtZ',
        'preservealpha' => 'preserveAlpha',
        'preserveaspectratio' => 'preserveAspectRatio',
        'primitiveunits' => 'primitiveUnits',
        'refx' => 'refX',
        'refy' => 'refY',
        'repeatcount' => 'repeatCount',
        'repeatdur' => 'repeatDur',
        'requiredextensions' => 'requiredExtensions',
        'requiredfeatures' => 'requiredFeatures',
        'specularconstant' => 'specularConstant',
        'specularexponent' => 'specularExponent',
        'spreadmethod' => 'spreadMethod',
        'startoffset' => 'startOffset',
        'stddeviation' => 'stdDeviation',
        'stitchtiles' => 'stitchTiles',
        'surfacescale' => 'surfaceScale',
        'systemlanguage' => 'systemLanguage',
        'tablevalues' => 'tableValues',
        'targetx' => 'targetX',
        'targety' => 'targetY',
        'textlength' => 'textLength',
        'viewbox' => 'viewBox',
        'viewtarget' => 'viewTarget',
        'xchannelselector' => 'xChannelSelector',
        'ychannelselector' => 'yChannelSelector',
        'zoomandpan' => 'zoomAndPan',
    );

    /**
     * Some SVG elements are case sensitive.
     * This map contains these.
     *
     * The map contains key/value store of the name is lowercase as the keys and
     * the correct casing as the value.
     */
    public static $svgCaseSensitiveElementMap = array(
        'altglyph' => 'altGlyph',
        'altglyphdef' => 'altGlyphDef',
        'altglyphitem' => 'altGlyphItem',
        'animatecolor' => 'animateColor',
        'animatemotion' => 'animateMotion',
        'animatetransform' => 'animateTransform',
        'clippath' => 'clipPath',
        'feblend' => 'feBlend',
        'fecolormatrix' => 'feColorMatrix',
        'fecomponenttransfer' => 'feComponentTransfer',
        'fecomposite' => 'feComposite',
        'feconvolvematrix' => 'feConvolveMatrix',
        'fediffuselighting' => 'feDiffuseLighting',
        'fedisplacementmap' => 'feDisplacementMap',
        'fedistantlight' => 'feDistantLight',
        'feflood' => 'feFlood',
        'fefunca' => 'feFuncA',
        'fefuncb' => 'feFuncB',
        'fefuncg' => 'feFuncG',
        'fefuncr' => 'feFuncR',
        'fegaussianblur' => 'feGaussianBlur',
        'feimage' => 'feImage',
        'femerge' => 'feMerge',
        'femergenode' => 'feMergeNode',
        'femorphology' => 'feMorphology',
        'feoffset' => 'feOffset',
        'fepointlight' => 'fePointLight',
        'fespecularlighting' => 'feSpecularLighting',
        'fespotlight' => 'feSpotLight',
        'fetile' => 'feTile',
        'feturbulence' => 'feTurbulence',
        'foreignobject' => 'foreignObject',
        'glyphref' => 'glyphRef',
        'lineargradient' => 'linearGradient',
        'radialgradient' => 'radialGradient',
        'textpath' => 'textPath',
    );

    /**
     * Check whether the given element meets the given criterion.
     *
     * Example:
     *
     * Elements::isA('script', Elements::TEXT_RAW); // Returns true.
     *
     * Elements::isA('script', Elements::TEXT_RCDATA); // Returns false.
     *
     * @param string $name The element name.
     * @param int    $mask One of the constants on this class.
     *
     * @return bool true if the element matches the mask, false otherwise.
     */
    public static function isA($name, $mask)
    {
        return (static::element($name) & $mask) === $mask;
    }

    /**
     * Test if an element is a valid html5 element.
     *
     * @param string $name The name of the element.
     *
     * @return bool true if a html5 element and false otherwise.
     */
    public static function isHtml5Element($name)
    {
        // html5 element names are case insensitive. Forcing lowercase for the check.
        // Do we need this check or will all data passed here already be lowercase?
        return isset(static::$html5[strtolower($name)]);
    }

    /**
     * Test if an element name is a valid MathML presentation element.
     *
     * @param string $name The name of the element.
     *
     * @return bool true if a MathML name and false otherwise.
     */
    public static function isMathMLElement($name)
    {
        // MathML is case-sensitive unlike html5 elements.
        return isset(static::$mathml[$name]);
    }

    /**
     * Test if an element is a valid SVG element.
     *
     * @param string $name The name of the element.
     *
     * @return bool true if a SVG element and false otherise.
     */
    public static function isSvgElement($name)
    {
        // SVG is case-sensitive unlike html5 elements.
        return isset(static::$svg[$name]);
    }

    /**
     * Is an element name valid in an html5 document.
     * This includes html5 elements along with other allowed embedded content
     * such as svg and mathml.
     *
     * @param string $name The name of the element.
     *
     * @return bool true if valid and false otherwise.
     */
    public static function isElement($name)
    {
        return static::isHtml5Element($name) || static::isMathMLElement($name) || static::isSvgElement($name);
    }

    /**
     * Get the element mask for the given element name.
     *
     * @param string $name The name of the element.
     *
     * @return int the element mask.
     */
    public static function element($name)
    {
        if (isset(static::$html5[$name])) {
            return static::$html5[$name];
        }
        if (isset(static::$svg[$name])) {
            return static::$svg[$name];
        }
        if (isset(static::$mathml[$name])) {
            return static::$mathml[$name];
        }

        return 0;
    }

    /**
     * Normalize a SVG element name to its proper case and form.
     *
     * @param string $name The name of the element.
     *
     * @return string the normalized form of the element name.
     */
    public static function normalizeSvgElement($name)
    {
        $name = strtolower($name);
        if (isset(static::$svgCaseSensitiveElementMap[$name])) {
            $name = static::$svgCaseSensitiveElementMap[$name];
        }

        return $name;
    }

    /**
     * Normalize a SVG attribute name to its proper case and form.
     *
     * @param string $name The name of the attribute.
     *
     * @return string The normalized form of the attribute name.
     */
    public static function normalizeSvgAttribute($name)
    {
        $name = strtolower($name);
        if (isset(static::$svgCaseSensitiveAttributeMap[$name])) {
            $name = static::$svgCaseSensitiveAttributeMap[$name];
        }

        return $name;
    }

    /**
     * Normalize a MathML attribute name to its proper case and form.
     * Note, all MathML element names are lowercase.
     *
     * @param string $name The name of the attribute.
     *
     * @return string The normalized form of the attribute name.
     */
    public static function normalizeMathMlAttribute($name)
    {
        $name = strtolower($name);

        // Only one attribute has a mixed case form for MathML.
        if ('definitionurl' === $name) {
            $name = 'definitionURL';
        }

        return $name;
    }
}
