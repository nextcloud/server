<?php
/**
 *  base include file for SimpleTest
 *  @package    SimpleTest
 *  @subpackage WebTester
 *  @version    $Id: php_parser.php 1911 2009-07-29 16:38:04Z lastcraft $
 */

/**
 *    Builds the page object.
 *    @package SimpleTest
 *    @subpackage WebTester
 */
class SimpleTidyPageBuilder {
    private $page;
    private $forms = array();
    private $labels = array();
    private $widgets_by_id = array();

    public function __destruct() {
        $this->free();
    }

    /**
     *    Frees up any references so as to allow the PHP garbage
     *    collection from unset() to work.
     */
    private function free() {
        unset($this->page);
        $this->forms = array();
        $this->labels = array();
    }

    /**
     *    This builder is only available if the 'tidy' extension is loaded.
     *    @return boolean       True if available.
     */
    function can() {
        return extension_loaded('tidy');
    }

    /**
     *    Reads the raw content the page using HTML Tidy.
     *    @param $response SimpleHttpResponse  Fetched response.
     *    @return SimplePage                   Newly parsed page.
     */
    function parse($response) {
        $this->page = new SimplePage($response);
        $tidied = tidy_parse_string($input = $this->insertGuards($response->getContent()),
                                    array('output-xml' => false, 'wrap' => '0', 'indent' => 'no'),
                                    'latin1');
        $this->walkTree($tidied->html());
        $this->attachLabels($this->widgets_by_id, $this->labels);
        $this->page->setForms($this->forms);
        $page = $this->page;
        $this->free();
        return $page;
    }

    /**
     *    Stops HTMLTidy stripping content that we wish to preserve.
     *    @param string      The raw html.
     *    @return string     The html with guard tags inserted.
     */
    private function insertGuards($html) {
        return $this->insertEmptyTagGuards($this->insertTextareaSimpleWhitespaceGuards($html));
    }

    /**
     *    Removes the extra content added during the parse stage
     *    in order to preserve content we don't want stripped
     *    out by HTMLTidy.
     *    @param string      The raw html.
     *    @return string     The html with guard tags removed.
     */
    private function stripGuards($html) {
        return $this->stripTextareaWhitespaceGuards($this->stripEmptyTagGuards($html));
    }

    /**
     *    HTML tidy strips out empty tags such as <option> which we
     *    need to preserve. This method inserts an additional marker.
     *    @param string      The raw html.
     *    @return string     The html with guards inserted.
     */
    private function insertEmptyTagGuards($html) {
        return preg_replace('#<(option|textarea)([^>]*)>(\s*)</(option|textarea)>#is',
                            '<\1\2>___EMPTY___\3</\4>',
                            $html);
    }

    /**
     *    HTML tidy strips out empty tags such as <option> which we
     *    need to preserve. This method strips additional markers
     *    inserted by SimpleTest to the tidy output used to make the
     *    tags non-empty. This ensures their preservation.
     *    @param string      The raw html.
     *    @return string     The html with guards removed.
     */
    private function stripEmptyTagGuards($html) {
        return preg_replace('#(^|>)(\s*)___EMPTY___(\s*)(</|$)#i', '\2\3', $html);
    }

    /**
     *    By parsing the XML output of tidy, we lose some whitespace
     *    information in textarea tags. We temporarily recode this
     *    data ourselves so as not to lose it.
     *    @param string      The raw html.
     *    @return string     The html with guards inserted.
     */
    private function insertTextareaSimpleWhitespaceGuards($html) {
        return preg_replace_callback('#<textarea([^>]*)>(.*?)</textarea>#is',
                                     array($this, 'insertWhitespaceGuards'),
                                     $html);
    }

    /**
     *  Callback for insertTextareaSimpleWhitespaceGuards().
     *  @param array $matches       Result of preg_replace_callback().
     *  @return string              Guard tags now replace whitespace.
     */
    private function insertWhitespaceGuards($matches) {
        return '<textarea' . $matches[1] . '>' .
                str_replace(array("\n", "\r", "\t", ' '),
                            array('___NEWLINE___', '___CR___', '___TAB___', '___SPACE___'),
                            $matches[2]) .
                '</textarea>';
    }

    /**
     *    Removes the whitespace preserving guards we added
     *    before parsing.
     *    @param string      The raw html.
     *    @return string     The html with guards removed.
     */
    private function stripTextareaWhitespaceGuards($html) {
        return str_replace(array('___NEWLINE___', '___CR___', '___TAB___', '___SPACE___'),
                           array("\n", "\r", "\t", ' '),
                           $html);
    }

    /**
     *  Visits the given node and all children
     *  @param object $node      Tidy XML node.
     */
    private function walkTree($node) {
        if ($node->name == 'a') {
            $this->page->addLink($this->tags()->createTag($node->name, (array)$node->attribute)
                                        ->addContent($this->innerHtml($node)));
        } elseif ($node->name == 'base' and isset($node->attribute['href'])) {
            $this->page->setBase($node->attribute['href']);
        } elseif ($node->name == 'title') {
            $this->page->setTitle($this->tags()->createTag($node->name, (array)$node->attribute)
                                         ->addContent($this->innerHtml($node)));
        } elseif ($node->name == 'frameset') {
            $this->page->setFrames($this->collectFrames($node));
        } elseif ($node->name == 'form') {
            $this->forms[] = $this->walkForm($node, $this->createEmptyForm($node));
        } elseif ($node->name == 'label') {
            $this->labels[] = $this->tags()->createTag($node->name, (array)$node->attribute)
                                           ->addContent($this->innerHtml($node));
        } else {
            $this->walkChildren($node);
        }
    }

    /**
     *  Helper method for traversing the XML tree.
     *  @param object $node     Tidy XML node.
     */
    private function walkChildren($node) {
        if ($node->hasChildren()) {
            foreach ($node->child as $child) {
                $this->walkTree($child);
            }
        }
    }

    /**
     *  Facade for forms containing preparsed widgets.
     *  @param object $node     Tidy XML node.
     *  @return SimpleForm      Facade for SimpleBrowser.
     */
    private function createEmptyForm($node) {
        return new SimpleForm($this->tags()->createTag($node->name, (array)$node->attribute), $this->page);
    }

    /**
     *  Visits the given node and all children
     *  @param object $node      Tidy XML node.
     */
    private function walkForm($node, $form, $enclosing_label = '') {
        if ($node->name == 'a') {
            $this->page->addLink($this->tags()->createTag($node->name, (array)$node->attribute)
                                              ->addContent($this->innerHtml($node)));
        } elseif (in_array($node->name, array('input', 'button', 'textarea', 'select'))) {
            $this->addWidgetToForm($node, $form, $enclosing_label);
        } elseif ($node->name == 'label') {
            $this->labels[] = $this->tags()->createTag($node->name, (array)$node->attribute)
                                           ->addContent($this->innerHtml($node));
            if ($node->hasChildren()) {
                foreach ($node->child as $child) {
                    $this->walkForm($child, $form, SimplePage::normalise($this->innerHtml($node)));
                }
            }
        } elseif ($node->hasChildren()) {
            foreach ($node->child as $child) {
                $this->walkForm($child, $form);
            }
        }
        return $form;
    }

    /**
     *  Tests a node for a "for" atribute. Used for
     *  attaching labels.
     *  @param object $node      Tidy XML node.
     *  @return boolean          True if the "for" attribute exists.
     */
    private function hasFor($node) {
        return isset($node->attribute) and $node->attribute['for'];
    }

    /**
     *  Adds the widget into the form container.
     *  @param object $node             Tidy XML node of widget.
     *  @param SimpleForm $form         Form to add it to.
     *  @param string $enclosing_label  The label of any label
     *                                  tag we might be in.
     */
    private function addWidgetToForm($node, $form, $enclosing_label) {
        $widget = $this->tags()->createTag($node->name, $this->attributes($node));
        if (! $widget) {
            return;
        }
        $widget->setLabel($enclosing_label)
               ->addContent($this->innerHtml($node));
        if ($node->name == 'select') {
            $widget->addTags($this->collectSelectOptions($node));
        }
        $form->addWidget($widget);
        $this->indexWidgetById($widget);
    }

    /**
     *  Fills the widget cache to speed up searching.
     *  @param SimpleTag $widget    Parsed widget to cache.
     */
    private function indexWidgetById($widget) {
        $id = $widget->getAttribute('id');
        if (! $id) {
            return;
        }
        if (! isset($this->widgets_by_id[$id])) {
            $this->widgets_by_id[$id] = array();
        }
        $this->widgets_by_id[$id][] = $widget;
    }

    /**
     *  Parses the options from inside an XML select node.
     *  @param object $node      Tidy XML node.
     *  @return array            List of SimpleTag options.
     */
    private function collectSelectOptions($node) {
        $options = array();
        if ($node->name == 'option') {
            $options[] = $this->tags()->createTag($node->name, $this->attributes($node))
                                      ->addContent($this->innerHtml($node));
        }
        if ($node->hasChildren()) {
            foreach ($node->child as $child) {
                $options = array_merge($options, $this->collectSelectOptions($child));
            }
        }
        return $options;
    }

    /**
     *  Convenience method for collecting all the attributes
     *  of a tag. Not sure why Tidy does not have this.
     *  @param object $node      Tidy XML node.
     *  @return array            Hash of attribute strings.
     */
    private function attributes($node) {
        if (! preg_match('|<[^ ]+\s(.*?)/?>|s', $node->value, $first_tag_contents)) {
            return array();
        }
        $attributes = array();
        preg_match_all('/\S+\s*=\s*\'[^\']*\'|(\S+\s*=\s*"[^"]*")|([^ =]+\s*=\s*[^ "\']+?)|[^ "\']+/', $first_tag_contents[1], $matches);
        foreach($matches[0] as $unparsed) {
            $attributes = $this->mergeAttribute($attributes, $unparsed);
        }
        return $attributes;
    }

    /**
     *  Overlay an attribute into the attributes hash.
     *  @param array $attributes        Current attribute list.
     *  @param string $raw              Raw attribute string with
     *                                  both key and value.
     *  @return array                   New attribute hash.
     */
    private function mergeAttribute($attributes, $raw) {
        $parts = explode('=', $raw);
        list($name, $value) = count($parts) == 1 ? array($parts[0], $parts[0]) : $parts;
        $attributes[trim($name)] = html_entity_decode($this->dequote(trim($value)), ENT_QUOTES);
        return $attributes;
    }

    /**
     *  Remove start and end quotes.
     *  @param string $quoted    A quoted string.
     *  @return string           Quotes are gone.
     */
    private function dequote($quoted) {
        if (preg_match('/^(\'([^\']*)\'|"([^"]*)")$/', $quoted, $matches)) {
            return isset($matches[3]) ? $matches[3] : $matches[2];
        }
        return $quoted;
    }

    /**
     *  Collects frame information inside a frameset tag.
     *  @param object $node     Tidy XML node.
     *  @return array           List of SimpleTag frame descriptions.
     */
    private function collectFrames($node) {
        $frames = array();
        if ($node->name == 'frame') {
            $frames = array($this->tags()->createTag($node->name, (array)$node->attribute));
        } else if ($node->hasChildren()) {
            $frames = array();
            foreach ($node->child as $child) {
                $frames = array_merge($frames, $this->collectFrames($child));
            }
        }
        return $frames;
    }

    /**
     *  Extracts the XML node text.
     *  @param object $node     Tidy XML node.
     *  @return string          The text only.
     */
    private function innerHtml($node) {
        $raw = '';
        if ($node->hasChildren()) {
            foreach ($node->child as $child) {
                $raw .= $child->value;
            }
        }
        return $this->stripGuards($raw);
    }

    /**
     *  Factory for parsed content holders.
     *  @return SimpleTagBuilder    Factory.
     */
    private function tags() {
        return new SimpleTagBuilder();
    }

    /**
     *  Called at the end of a parse run. Attaches any
     *  non-wrapping labels to their form elements.
     *  @param array $widgets_by_id     Cached SimpleTag hash.
     *  @param array $labels            SimpleTag label elements.
     */
    private function attachLabels($widgets_by_id, $labels) {
        foreach ($labels as $label) {
            $for = $label->getFor();
            if ($for and isset($widgets_by_id[$for])) {
                $text = $label->getText();
                foreach ($widgets_by_id[$for] as $widget) {
                    $widget->setLabel($text);
                }
            }
        }
    }
}
?>