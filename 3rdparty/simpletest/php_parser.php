<?php
/**
 *  base include file for SimpleTest
 *  @package    SimpleTest
 *  @subpackage WebTester
 *  @version    $Id: php_parser.php 1927 2009-07-31 12:45:36Z dgheath $
 */

/**#@+
 * Lexer mode stack constants
 */
foreach (array('LEXER_ENTER', 'LEXER_MATCHED',
                'LEXER_UNMATCHED', 'LEXER_EXIT',
                'LEXER_SPECIAL') as $i => $constant) {
    if (! defined($constant)) {
        define($constant, $i + 1);
    }
}
/**#@-*/

/**
 *    Compounded regular expression. Any of
 *    the contained patterns could match and
 *    when one does, it's label is returned.
 *    @package SimpleTest
 *    @subpackage WebTester
 */
class ParallelRegex {
    private $patterns;
    private $labels;
    private $regex;
    private $case;

    /**
     *    Constructor. Starts with no patterns.
     *    @param boolean $case    True for case sensitive, false
     *                            for insensitive.
     *    @access public
     */
    function __construct($case) {
        $this->case = $case;
        $this->patterns = array();
        $this->labels = array();
        $this->regex = null;
    }

    /**
     *    Adds a pattern with an optional label.
     *    @param string $pattern      Perl style regex, but ( and )
     *                                lose the usual meaning.
     *    @param string $label        Label of regex to be returned
     *                                on a match.
     *    @access public
     */
    function addPattern($pattern, $label = true) {
        $count = count($this->patterns);
        $this->patterns[$count] = $pattern;
        $this->labels[$count] = $label;
        $this->regex = null;
    }

    /**
     *    Attempts to match all patterns at once against
     *    a string.
     *    @param string $subject      String to match against.
     *    @param string $match        First matched portion of
     *                                subject.
     *    @return boolean             True on success.
     *    @access public
     */
    function match($subject, &$match) {
        if (count($this->patterns) == 0) {
            return false;
        }
        if (! preg_match($this->getCompoundedRegex(), $subject, $matches)) {
            $match = '';
            return false;
        }
        $match = $matches[0];
        for ($i = 1; $i < count($matches); $i++) {
            if ($matches[$i]) {
                return $this->labels[$i - 1];
            }
        }
        return true;
    }

    /**
     *    Compounds the patterns into a single
     *    regular expression separated with the
     *    "or" operator. Caches the regex.
     *    Will automatically escape (, ) and / tokens.
     *    @param array $patterns    List of patterns in order.
     *    @access private
     */
    protected function getCompoundedRegex() {
        if ($this->regex == null) {
            for ($i = 0, $count = count($this->patterns); $i < $count; $i++) {
                $this->patterns[$i] = '(' . str_replace(
                        array('/', '(', ')'),
                        array('\/', '\(', '\)'),
                        $this->patterns[$i]) . ')';
            }
            $this->regex = "/" . implode("|", $this->patterns) . "/" . $this->getPerlMatchingFlags();
        }
        return $this->regex;
    }

    /**
     *    Accessor for perl regex mode flags to use.
     *    @return string       Perl regex flags.
     *    @access private
     */
    protected function getPerlMatchingFlags() {
        return ($this->case ? "msS" : "msSi");
    }
}

/**
 *    States for a stack machine.
 *    @package SimpleTest
 *    @subpackage WebTester
 */
class SimpleStateStack {
    private $stack;

    /**
     *    Constructor. Starts in named state.
     *    @param string $start        Starting state name.
     *    @access public
     */
    function __construct($start) {
        $this->stack = array($start);
    }

    /**
     *    Accessor for current state.
     *    @return string       State.
     *    @access public
     */
    function getCurrent() {
        return $this->stack[count($this->stack) - 1];
    }

    /**
     *    Adds a state to the stack and sets it
     *    to be the current state.
     *    @param string $state        New state.
     *    @access public
     */
    function enter($state) {
        array_push($this->stack, $state);
    }

    /**
     *    Leaves the current state and reverts
     *    to the previous one.
     *    @return boolean    False if we drop off
     *                       the bottom of the list.
     *    @access public
     */
    function leave() {
        if (count($this->stack) == 1) {
            return false;
        }
        array_pop($this->stack);
        return true;
    }
}

/**
 *    Accepts text and breaks it into tokens.
 *    Some optimisation to make the sure the
 *    content is only scanned by the PHP regex
 *    parser once. Lexer modes must not start
 *    with leading underscores.
 *    @package SimpleTest
 *    @subpackage WebTester
 */
class SimpleLexer {
    private $regexes;
    private $parser;
    private $mode;
    private $mode_handlers;
    private $case;

    /**
     *    Sets up the lexer in case insensitive matching
     *    by default.
     *    @param SimpleSaxParser $parser  Handling strategy by
     *                                    reference.
     *    @param string $start            Starting handler.
     *    @param boolean $case            True for case sensitive.
     *    @access public
     */
    function __construct($parser, $start = "accept", $case = false) {
        $this->case = $case;
        $this->regexes = array();
        $this->parser = $parser;
        $this->mode = new SimpleStateStack($start);
        $this->mode_handlers = array($start => $start);
    }

    /**
     *    Adds a token search pattern for a particular
     *    parsing mode. The pattern does not change the
     *    current mode.
     *    @param string $pattern      Perl style regex, but ( and )
     *                                lose the usual meaning.
     *    @param string $mode         Should only apply this
     *                                pattern when dealing with
     *                                this type of input.
     *    @access public
     */
    function addPattern($pattern, $mode = "accept") {
        if (! isset($this->regexes[$mode])) {
            $this->regexes[$mode] = new ParallelRegex($this->case);
        }
        $this->regexes[$mode]->addPattern($pattern);
        if (! isset($this->mode_handlers[$mode])) {
            $this->mode_handlers[$mode] = $mode;
        }
    }

    /**
     *    Adds a pattern that will enter a new parsing
     *    mode. Useful for entering parenthesis, strings,
     *    tags, etc.
     *    @param string $pattern      Perl style regex, but ( and )
     *                                lose the usual meaning.
     *    @param string $mode         Should only apply this
     *                                pattern when dealing with
     *                                this type of input.
     *    @param string $new_mode     Change parsing to this new
     *                                nested mode.
     *    @access public
     */
    function addEntryPattern($pattern, $mode, $new_mode) {
        if (! isset($this->regexes[$mode])) {
            $this->regexes[$mode] = new ParallelRegex($this->case);
        }
        $this->regexes[$mode]->addPattern($pattern, $new_mode);
        if (! isset($this->mode_handlers[$new_mode])) {
            $this->mode_handlers[$new_mode] = $new_mode;
        }
    }

    /**
     *    Adds a pattern that will exit the current mode
     *    and re-enter the previous one.
     *    @param string $pattern      Perl style regex, but ( and )
     *                                lose the usual meaning.
     *    @param string $mode         Mode to leave.
     *    @access public
     */
    function addExitPattern($pattern, $mode) {
        if (! isset($this->regexes[$mode])) {
            $this->regexes[$mode] = new ParallelRegex($this->case);
        }
        $this->regexes[$mode]->addPattern($pattern, "__exit");
        if (! isset($this->mode_handlers[$mode])) {
            $this->mode_handlers[$mode] = $mode;
        }
    }

    /**
     *    Adds a pattern that has a special mode. Acts as an entry
     *    and exit pattern in one go, effectively calling a special
     *    parser handler for this token only.
     *    @param string $pattern      Perl style regex, but ( and )
     *                                lose the usual meaning.
     *    @param string $mode         Should only apply this
     *                                pattern when dealing with
     *                                this type of input.
     *    @param string $special      Use this mode for this one token.
     *    @access public
     */
    function addSpecialPattern($pattern, $mode, $special) {
        if (! isset($this->regexes[$mode])) {
            $this->regexes[$mode] = new ParallelRegex($this->case);
        }
        $this->regexes[$mode]->addPattern($pattern, "_$special");
        if (! isset($this->mode_handlers[$special])) {
            $this->mode_handlers[$special] = $special;
        }
    }

    /**
     *    Adds a mapping from a mode to another handler.
     *    @param string $mode        Mode to be remapped.
     *    @param string $handler     New target handler.
     *    @access public
     */
    function mapHandler($mode, $handler) {
        $this->mode_handlers[$mode] = $handler;
    }

    /**
     *    Splits the page text into tokens. Will fail
     *    if the handlers report an error or if no
     *    content is consumed. If successful then each
     *    unparsed and parsed token invokes a call to the
     *    held listener.
     *    @param string $raw        Raw HTML text.
     *    @return boolean           True on success, else false.
     *    @access public
     */
    function parse($raw) {
        if (! isset($this->parser)) {
            return false;
        }
        $length = strlen($raw);
        while (is_array($parsed = $this->reduce($raw))) {
            list($raw, $unmatched, $matched, $mode) = $parsed;
            if (! $this->dispatchTokens($unmatched, $matched, $mode)) {
                return false;
            }
            if ($raw === '') {
                return true;
            }
            if (strlen($raw) == $length) {
                return false;
            }
            $length = strlen($raw);
        }
        if (! $parsed) {
            return false;
        }
        return $this->invokeParser($raw, LEXER_UNMATCHED);
    }

    /**
     *    Sends the matched token and any leading unmatched
     *    text to the parser changing the lexer to a new
     *    mode if one is listed.
     *    @param string $unmatched    Unmatched leading portion.
     *    @param string $matched      Actual token match.
     *    @param string $mode         Mode after match. A boolean
     *                                false mode causes no change.
     *    @return boolean             False if there was any error
     *                                from the parser.
     *    @access private
     */
    protected function dispatchTokens($unmatched, $matched, $mode = false) {
        if (! $this->invokeParser($unmatched, LEXER_UNMATCHED)) {
            return false;
        }
        if (is_bool($mode)) {
            return $this->invokeParser($matched, LEXER_MATCHED);
        }
        if ($this->isModeEnd($mode)) {
            if (! $this->invokeParser($matched, LEXER_EXIT)) {
                return false;
            }
            return $this->mode->leave();
        }
        if ($this->isSpecialMode($mode)) {
            $this->mode->enter($this->decodeSpecial($mode));
            if (! $this->invokeParser($matched, LEXER_SPECIAL)) {
                return false;
            }
            return $this->mode->leave();
        }
        $this->mode->enter($mode);
        return $this->invokeParser($matched, LEXER_ENTER);
    }

    /**
     *    Tests to see if the new mode is actually to leave
     *    the current mode and pop an item from the matching
     *    mode stack.
     *    @param string $mode    Mode to test.
     *    @return boolean        True if this is the exit mode.
     *    @access private
     */
    protected function isModeEnd($mode) {
        return ($mode === "__exit");
    }

    /**
     *    Test to see if the mode is one where this mode
     *    is entered for this token only and automatically
     *    leaves immediately afterwoods.
     *    @param string $mode    Mode to test.
     *    @return boolean        True if this is the exit mode.
     *    @access private
     */
    protected function isSpecialMode($mode) {
        return (strncmp($mode, "_", 1) == 0);
    }

    /**
     *    Strips the magic underscore marking single token
     *    modes.
     *    @param string $mode    Mode to decode.
     *    @return string         Underlying mode name.
     *    @access private
     */
    protected function decodeSpecial($mode) {
        return substr($mode, 1);
    }

    /**
     *    Calls the parser method named after the current
     *    mode. Empty content will be ignored. The lexer
     *    has a parser handler for each mode in the lexer.
     *    @param string $content        Text parsed.
     *    @param boolean $is_match      Token is recognised rather
     *                                  than unparsed data.
     *    @access private
     */
    protected function invokeParser($content, $is_match) {
        if (($content === '') || ($content === false)) {
            return true;
        }
        $handler = $this->mode_handlers[$this->mode->getCurrent()];
        return $this->parser->$handler($content, $is_match);
    }

    /**
     *    Tries to match a chunk of text and if successful
     *    removes the recognised chunk and any leading
     *    unparsed data. Empty strings will not be matched.
     *    @param string $raw         The subject to parse. This is the
     *                               content that will be eaten.
     *    @return array/boolean      Three item list of unparsed
     *                               content followed by the
     *                               recognised token and finally the
     *                               action the parser is to take.
     *                               True if no match, false if there
     *                               is a parsing error.
     *    @access private
     */
    protected function reduce($raw) {
        if ($action = $this->regexes[$this->mode->getCurrent()]->match($raw, $match)) {
            $unparsed_character_count = strpos($raw, $match);
            $unparsed = substr($raw, 0, $unparsed_character_count);
            $raw = substr($raw, $unparsed_character_count + strlen($match));
            return array($raw, $unparsed, $match, $action);
        }
        return true;
    }
}

/**
 *    Breaks HTML into SAX events.
 *    @package SimpleTest
 *    @subpackage WebTester
 */
class SimpleHtmlLexer extends SimpleLexer {

    /**
     *    Sets up the lexer with case insensitive matching
     *    and adds the HTML handlers.
     *    @param SimpleSaxParser $parser  Handling strategy by
     *                                    reference.
     *    @access public
     */
    function __construct($parser) {
        parent::__construct($parser, 'text');
        $this->mapHandler('text', 'acceptTextToken');
        $this->addSkipping();
        foreach ($this->getParsedTags() as $tag) {
            $this->addTag($tag);
        }
        $this->addInTagTokens();
    }

    /**
     *    List of parsed tags. Others are ignored.
     *    @return array        List of searched for tags.
     *    @access private
     */
    protected function getParsedTags() {
        return array('a', 'base', 'title', 'form', 'input', 'button', 'textarea', 'select',
                'option', 'frameset', 'frame', 'label');
    }

    /**
     *    The lexer has to skip certain sections such
     *    as server code, client code and styles.
     *    @access private
     */
    protected function addSkipping() {
        $this->mapHandler('css', 'ignore');
        $this->addEntryPattern('<style', 'text', 'css');
        $this->addExitPattern('</style>', 'css');
        $this->mapHandler('js', 'ignore');
        $this->addEntryPattern('<script', 'text', 'js');
        $this->addExitPattern('</script>', 'js');
        $this->mapHandler('comment', 'ignore');
        $this->addEntryPattern('<!--', 'text', 'comment');
        $this->addExitPattern('-->', 'comment');
    }

    /**
     *    Pattern matches to start and end a tag.
     *    @param string $tag          Name of tag to scan for.
     *    @access private
     */
    protected function addTag($tag) {
        $this->addSpecialPattern("</$tag>", 'text', 'acceptEndToken');
        $this->addEntryPattern("<$tag", 'text', 'tag');
    }

    /**
     *    Pattern matches to parse the inside of a tag
     *    including the attributes and their quoting.
     *    @access private
     */
    protected function addInTagTokens() {
        $this->mapHandler('tag', 'acceptStartToken');
        $this->addSpecialPattern('\s+', 'tag', 'ignore');
        $this->addAttributeTokens();
        $this->addExitPattern('/>', 'tag');
        $this->addExitPattern('>', 'tag');
    }

    /**
     *    Matches attributes that are either single quoted,
     *    double quoted or unquoted.
     *    @access private
     */
    protected function addAttributeTokens() {
        $this->mapHandler('dq_attribute', 'acceptAttributeToken');
        $this->addEntryPattern('=\s*"', 'tag', 'dq_attribute');
        $this->addPattern("\\\\\"", 'dq_attribute');
        $this->addExitPattern('"', 'dq_attribute');
        $this->mapHandler('sq_attribute', 'acceptAttributeToken');
        $this->addEntryPattern("=\s*'", 'tag', 'sq_attribute');
        $this->addPattern("\\\\'", 'sq_attribute');
        $this->addExitPattern("'", 'sq_attribute');
        $this->mapHandler('uq_attribute', 'acceptAttributeToken');
        $this->addSpecialPattern('=\s*[^>\s]*', 'tag', 'uq_attribute');
    }
}

/**
 *    Converts HTML tokens into selected SAX events.
 *    @package SimpleTest
 *    @subpackage WebTester
 */
class SimpleHtmlSaxParser {
    private $lexer;
    private $listener;
    private $tag;
    private $attributes;
    private $current_attribute;

    /**
     *    Sets the listener.
     *    @param SimplePhpPageBuilder $listener    SAX event handler.
     *    @access public
     */
    function __construct($listener) {
        $this->listener = $listener;
        $this->lexer = $this->createLexer($this);
        $this->tag = '';
        $this->attributes = array();
        $this->current_attribute = '';
    }

    /**
     *    Runs the content through the lexer which
     *    should call back to the acceptors.
     *    @param string $raw      Page text to parse.
     *    @return boolean         False if parse error.
     *    @access public
     */
    function parse($raw) {
        return $this->lexer->parse($raw);
    }

    /**
     *    Sets up the matching lexer. Starts in 'text' mode.
     *    @param SimpleSaxParser $parser    Event generator, usually $self.
     *    @return SimpleLexer               Lexer suitable for this parser.
     *    @access public
     */
    static function createLexer(&$parser) {
        return new SimpleHtmlLexer($parser);
    }

    /**
     *    Accepts a token from the tag mode. If the
     *    starting element completes then the element
     *    is dispatched and the current attributes
     *    set back to empty. The element or attribute
     *    name is converted to lower case.
     *    @param string $token     Incoming characters.
     *    @param integer $event    Lexer event type.
     *    @return boolean          False if parse error.
     *    @access public
     */
    function acceptStartToken($token, $event) {
        if ($event == LEXER_ENTER) {
            $this->tag = strtolower(substr($token, 1));
            return true;
        }
        if ($event == LEXER_EXIT) {
            $success = $this->listener->startElement(
                    $this->tag,
                    $this->attributes);
            $this->tag = '';
            $this->attributes = array();
            return $success;
        }
        if ($token != '=') {
            $this->current_attribute = strtolower(html_entity_decode($token, ENT_QUOTES));
            $this->attributes[$this->current_attribute] = '';
        }
        return true;
    }

    /**
     *    Accepts a token from the end tag mode.
     *    The element name is converted to lower case.
     *    @param string $token     Incoming characters.
     *    @param integer $event    Lexer event type.
     *    @return boolean          False if parse error.
     *    @access public
     */
    function acceptEndToken($token, $event) {
        if (! preg_match('/<\/(.*)>/', $token, $matches)) {
            return false;
        }
        return $this->listener->endElement(strtolower($matches[1]));
    }

    /**
     *    Part of the tag data.
     *    @param string $token     Incoming characters.
     *    @param integer $event    Lexer event type.
     *    @return boolean          False if parse error.
     *    @access public
     */
    function acceptAttributeToken($token, $event) {
        if ($this->current_attribute) {
            if ($event == LEXER_UNMATCHED) {
                $this->attributes[$this->current_attribute] .=
                        html_entity_decode($token, ENT_QUOTES);
            }
            if ($event == LEXER_SPECIAL) {
                $this->attributes[$this->current_attribute] .=
                        preg_replace('/^=\s*/' , '', html_entity_decode($token, ENT_QUOTES));
            }
        }
        return true;
    }

    /**
     *    A character entity.
     *    @param string $token    Incoming characters.
     *    @param integer $event   Lexer event type.
     *    @return boolean         False if parse error.
     *    @access public
     */
    function acceptEntityToken($token, $event) {
    }

    /**
     *    Character data between tags regarded as
     *    important.
     *    @param string $token     Incoming characters.
     *    @param integer $event    Lexer event type.
     *    @return boolean          False if parse error.
     *    @access public
     */
    function acceptTextToken($token, $event) {
        return $this->listener->addContent($token);
    }

    /**
     *    Incoming data to be ignored.
     *    @param string $token     Incoming characters.
     *    @param integer $event    Lexer event type.
     *    @return boolean          False if parse error.
     *    @access public
     */
    function ignore($token, $event) {
        return true;
    }
}

/**
 *    SAX event handler. Maintains a list of
 *    open tags and dispatches them as they close.
 *    @package SimpleTest
 *    @subpackage WebTester
 */
class SimplePhpPageBuilder {
    private $tags;
    private $page;
    private $private_content_tag;
    private $open_forms = array();
    private $complete_forms = array();
    private $frameset = false;
    private $loading_frames = array();
    private $frameset_nesting_level = 0;
    private $left_over_labels = array();

    /**
     *    Frees up any references so as to allow the PHP garbage
     *    collection from unset() to work.
     *    @access public
     */
    function free() {
        unset($this->tags);
        unset($this->page);
        unset($this->private_content_tags);
        $this->open_forms = array();
        $this->complete_forms = array();
        $this->frameset = false;
        $this->loading_frames = array();
        $this->frameset_nesting_level = 0;
        $this->left_over_labels = array();
    }

    /**
     *    This builder is always available.
     *    @return boolean       Always true.
     */
    function can() {
        return true;
    }

    /**
     *    Reads the raw content and send events
     *    into the page to be built.
     *    @param $response SimpleHttpResponse  Fetched response.
     *    @return SimplePage                   Newly parsed page.
     *    @access public
     */
    function parse($response) {
        $this->tags = array();
        $this->page = $this->createPage($response);
        $parser = $this->createParser($this);
        $parser->parse($response->getContent());
        $this->acceptPageEnd();
        $page = $this->page;
        $this->free();
        return $page;
    }

    /**
     *    Creates an empty page.
     *    @return SimplePage        New unparsed page.
     *    @access protected
     */
    protected function createPage($response) {
        return new SimplePage($response);
    }

    /**
     *    Creates the parser used with the builder.
     *    @param SimplePhpPageBuilder $listener   Target of parser.
     *    @return SimpleSaxParser              Parser to generate
     *                                         events for the builder.
     *    @access protected
     */
    protected function createParser(&$listener) {
        return new SimpleHtmlSaxParser($listener);
    }

    /**
     *    Start of element event. Opens a new tag.
     *    @param string $name         Element name.
     *    @param hash $attributes     Attributes without content
     *                                are marked as true.
     *    @return boolean             False on parse error.
     *    @access public
     */
    function startElement($name, $attributes) {
        $factory = new SimpleTagBuilder();
        $tag = $factory->createTag($name, $attributes);
        if (! $tag) {
            return true;
        }
        if ($tag->getTagName() == 'label') {
            $this->acceptLabelStart($tag);
            $this->openTag($tag);
            return true;
        }
        if ($tag->getTagName() == 'form') {
            $this->acceptFormStart($tag);
            return true;
        }
        if ($tag->getTagName() == 'frameset') {
            $this->acceptFramesetStart($tag);
            return true;
        }
        if ($tag->getTagName() == 'frame') {
            $this->acceptFrame($tag);
            return true;
        }
        if ($tag->isPrivateContent() && ! isset($this->private_content_tag)) {
            $this->private_content_tag = &$tag;
        }
        if ($tag->expectEndTag()) {
            $this->openTag($tag);
            return true;
        }
        $this->acceptTag($tag);
        return true;
    }

    /**
     *    End of element event.
     *    @param string $name        Element name.
     *    @return boolean            False on parse error.
     *    @access public
     */
    function endElement($name) {
        if ($name == 'label') {
            $this->acceptLabelEnd();
            return true;
        }
        if ($name == 'form') {
            $this->acceptFormEnd();
            return true;
        }
        if ($name == 'frameset') {
            $this->acceptFramesetEnd();
            return true;
        }
        if ($this->hasNamedTagOnOpenTagStack($name)) {
            $tag = array_pop($this->tags[$name]);
            if ($tag->isPrivateContent() && $this->private_content_tag->getTagName() == $name) {
                unset($this->private_content_tag);
            }
            $this->addContentTagToOpenTags($tag);
            $this->acceptTag($tag);
            return true;
        }
        return true;
    }

    /**
     *    Test to see if there are any open tags awaiting
     *    closure that match the tag name.
     *    @param string $name        Element name.
     *    @return boolean            True if any are still open.
     *    @access private
     */
    protected function hasNamedTagOnOpenTagStack($name) {
        return isset($this->tags[$name]) && (count($this->tags[$name]) > 0);
    }

    /**
     *    Unparsed, but relevant data. The data is added
     *    to every open tag.
     *    @param string $text        May include unparsed tags.
     *    @return boolean            False on parse error.
     *    @access public
     */
    function addContent($text) {
        if (isset($this->private_content_tag)) {
            $this->private_content_tag->addContent($text);
        } else {
            $this->addContentToAllOpenTags($text);
        }
        return true;
    }

    /**
     *    Any content fills all currently open tags unless it
     *    is part of an option tag.
     *    @param string $text        May include unparsed tags.
     *    @access private
     */
    protected function addContentToAllOpenTags($text) {
        foreach (array_keys($this->tags) as $name) {
            for ($i = 0, $count = count($this->tags[$name]); $i < $count; $i++) {
                $this->tags[$name][$i]->addContent($text);
            }
        }
    }

    /**
     *    Parsed data in tag form. The parsed tag is added
     *    to every open tag. Used for adding options to select
     *    fields only.
     *    @param SimpleTag $tag        Option tags only.
     *    @access private
     */
    protected function addContentTagToOpenTags(&$tag) {
        if ($tag->getTagName() != 'option') {
            return;
        }
        foreach (array_keys($this->tags) as $name) {
            for ($i = 0, $count = count($this->tags[$name]); $i < $count; $i++) {
                $this->tags[$name][$i]->addTag($tag);
            }
        }
    }

    /**
     *    Opens a tag for receiving content. Multiple tags
     *    will be receiving input at the same time.
     *    @param SimpleTag $tag        New content tag.
     *    @access private
     */
    protected function openTag($tag) {
        $name = $tag->getTagName();
        if (! in_array($name, array_keys($this->tags))) {
            $this->tags[$name] = array();
        }
        $this->tags[$name][] = $tag;
    }

    /**
     *    Adds a tag to the page.
     *    @param SimpleTag $tag        Tag to accept.
     *    @access public
     */
    protected function acceptTag($tag) {
        if ($tag->getTagName() == "a") {
            $this->page->addLink($tag);
        } elseif ($tag->getTagName() == "base") {
            $this->page->setBase($tag->getAttribute('href'));
        } elseif ($tag->getTagName() == "title") {
            $this->page->setTitle($tag);
        } elseif ($this->isFormElement($tag->getTagName())) {
            for ($i = 0; $i < count($this->open_forms); $i++) {
                $this->open_forms[$i]->addWidget($tag);
            }
            $this->last_widget = $tag;
        }
    }

    /**
     *    Opens a label for a described widget.
     *    @param SimpleFormTag $tag      Tag to accept.
     *    @access public
     */
    protected function acceptLabelStart($tag) {
        $this->label = $tag;
        unset($this->last_widget);
    }

    /**
     *    Closes the most recently opened label.
     *    @access public
     */
    protected function acceptLabelEnd() {
        if (isset($this->label)) {
            if (isset($this->last_widget)) {
                $this->last_widget->setLabel($this->label->getText());
                unset($this->last_widget);
            } else {
                $this->left_over_labels[] = SimpleTestCompatibility::copy($this->label);
            }
            unset($this->label);
        }
    }

    /**
     *    Tests to see if a tag is a possible form
     *    element.
     *    @param string $name     HTML element name.
     *    @return boolean         True if form element.
     *    @access private
     */
    protected function isFormElement($name) {
        return in_array($name, array('input', 'button', 'textarea', 'select'));
    }

    /**
     *    Opens a form. New widgets go here.
     *    @param SimpleFormTag $tag      Tag to accept.
     *    @access public
     */
    protected function acceptFormStart($tag) {
        $this->open_forms[] = new SimpleForm($tag, $this->page);
    }

    /**
     *    Closes the most recently opened form.
     *    @access public
     */
    protected function acceptFormEnd() {
        if (count($this->open_forms)) {
            $this->complete_forms[] = array_pop($this->open_forms);
        }
    }

    /**
     *    Opens a frameset. A frameset may contain nested
     *    frameset tags.
     *    @param SimpleFramesetTag $tag      Tag to accept.
     *    @access public
     */
    protected function acceptFramesetStart($tag) {
        if (! $this->isLoadingFrames()) {
            $this->frameset = $tag;
        }
        $this->frameset_nesting_level++;
    }

    /**
     *    Closes the most recently opened frameset.
     *    @access public
     */
    protected function acceptFramesetEnd() {
        if ($this->isLoadingFrames()) {
            $this->frameset_nesting_level--;
        }
    }

    /**
     *    Takes a single frame tag and stashes it in
     *    the current frame set.
     *    @param SimpleFrameTag $tag      Tag to accept.
     *    @access public
     */
    protected function acceptFrame($tag) {
        if ($this->isLoadingFrames()) {
            if ($tag->getAttribute('src')) {
                $this->loading_frames[] = $tag;
            }
        }
    }

    /**
     *    Test to see if in the middle of reading
     *    a frameset.
     *    @return boolean        True if inframeset.
     *    @access private
     */
    protected function isLoadingFrames() {
        return $this->frameset and $this->frameset_nesting_level > 0;
    }

    /**
     *    Marker for end of complete page. Any work in
     *    progress can now be closed.
     *    @access public
     */
    protected function acceptPageEnd() {
        while (count($this->open_forms)) {
            $this->complete_forms[] = array_pop($this->open_forms);
        }
        foreach ($this->left_over_labels as $label) {
            for ($i = 0, $count = count($this->complete_forms); $i < $count; $i++) {
                $this->complete_forms[$i]->attachLabelBySelector(
                        new SimpleById($label->getFor()),
                        $label->getText());
            }
        }
        $this->page->setForms($this->complete_forms);
        $this->page->setFrames($this->loading_frames);
    }
}
?>