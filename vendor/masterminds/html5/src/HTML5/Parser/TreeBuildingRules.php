<?php

namespace Masterminds\HTML5\Parser;

/**
 * Handles special-case rules for the DOM tree builder.
 *
 * Many tags have special rules that need to be accomodated on an
 * individual basis. This class handles those rules.
 *
 * See section 8.1.2.4 of the spec.
 *
 * @todo - colgroup and col special behaviors
 *       - body and head special behaviors
 */
class TreeBuildingRules
{
    protected static $tags = array(
        'li' => 1,
        'dd' => 1,
        'dt' => 1,
        'rt' => 1,
        'rp' => 1,
        'tr' => 1,
        'th' => 1,
        'td' => 1,
        'thead' => 1,
        'tfoot' => 1,
        'tbody' => 1,
        'table' => 1,
        'optgroup' => 1,
        'option' => 1,
    );

    /**
     * Returns true if the given tagname has special processing rules.
     */
    public function hasRules($tagname)
    {
        return isset(static::$tags[$tagname]);
    }

    /**
     * Evaluate the rule for the current tag name.
     *
     * This may modify the existing DOM.
     *
     * @return \DOMElement The new Current DOM element.
     */
    public function evaluate($new, $current)
    {
        switch ($new->tagName) {
            case 'li':
                return $this->handleLI($new, $current);
            case 'dt':
            case 'dd':
                return $this->handleDT($new, $current);
            case 'rt':
            case 'rp':
                return $this->handleRT($new, $current);
            case 'optgroup':
                return $this->closeIfCurrentMatches($new, $current, array(
                    'optgroup',
                ));
            case 'option':
                return $this->closeIfCurrentMatches($new, $current, array(
                    'option',
                ));
            case 'tr':
                return $this->closeIfCurrentMatches($new, $current, array(
                    'tr',
                ));
            case 'td':
            case 'th':
                return $this->closeIfCurrentMatches($new, $current, array(
                    'th',
                    'td',
                ));
            case 'tbody':
            case 'thead':
            case 'tfoot':
            case 'table': // Spec isn't explicit about this, but it's necessary.

                return $this->closeIfCurrentMatches($new, $current, array(
                    'thead',
                    'tfoot',
                    'tbody',
                ));
        }

        return $current;
    }

    protected function handleLI($ele, $current)
    {
        return $this->closeIfCurrentMatches($ele, $current, array(
            'li',
        ));
    }

    protected function handleDT($ele, $current)
    {
        return $this->closeIfCurrentMatches($ele, $current, array(
            'dt',
            'dd',
        ));
    }

    protected function handleRT($ele, $current)
    {
        return $this->closeIfCurrentMatches($ele, $current, array(
            'rt',
            'rp',
        ));
    }

    protected function closeIfCurrentMatches($ele, $current, $match)
    {
        if (in_array($current->tagName, $match, true)) {
            $current->parentNode->appendChild($ele);
        } else {
            $current->appendChild($ele);
        }

        return $ele;
    }
}
