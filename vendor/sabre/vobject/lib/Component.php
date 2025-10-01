<?php

namespace Sabre\VObject;

use Sabre\Xml;

/**
 * Component.
 *
 * A component represents a group of properties, such as VCALENDAR, VEVENT, or
 * VCARD.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Component extends Node
{
    /**
     * Component name.
     *
     * This will contain a string such as VEVENT, VTODO, VCALENDAR, VCARD.
     *
     * @var string
     */
    public $name;

    /**
     * A list of properties and/or sub-components.
     *
     * @var array<string, Component|Property>
     */
    protected $children = [];

    /**
     * Creates a new component.
     *
     * You can specify the children either in key=>value syntax, in which case
     * properties will automatically be created, or you can just pass a list of
     * Component and Property object.
     *
     * By default, a set of sensible values will be added to the component. For
     * an iCalendar object, this may be something like CALSCALE:GREGORIAN. To
     * ensure that this does not happen, set $defaults to false.
     *
     * @param string|null $name     such as VCALENDAR, VEVENT
     * @param bool        $defaults
     */
    public function __construct(Document $root, $name, array $children = [], $defaults = true)
    {
        $this->name = isset($name) ? strtoupper($name) : '';
        $this->root = $root;

        if ($defaults) {
            // This is a terribly convoluted way to do this, but this ensures
            // that the order of properties as they are specified in both
            // defaults and the childrens list, are inserted in the object in a
            // natural way.
            $list = $this->getDefaults();
            $nodes = [];
            foreach ($children as $key => $value) {
                if ($value instanceof Node) {
                    if (isset($list[$value->name])) {
                        unset($list[$value->name]);
                    }
                    $nodes[] = $value;
                } else {
                    $list[$key] = $value;
                }
            }
            foreach ($list as $key => $value) {
                $this->add($key, $value);
            }
            foreach ($nodes as $node) {
                $this->add($node);
            }
        } else {
            foreach ($children as $k => $child) {
                if ($child instanceof Node) {
                    // Component or Property
                    $this->add($child);
                } else {
                    // Property key=>value
                    $this->add($k, $child);
                }
            }
        }
    }

    /**
     * Adds a new property or component, and returns the new item.
     *
     * This method has 3 possible signatures:
     *
     * add(Component $comp) // Adds a new component
     * add(Property $prop)  // Adds a new property
     * add($name, $value, array $parameters = []) // Adds a new property
     * add($name, array $children = []) // Adds a new component
     * by name.
     *
     * @return Node
     */
    public function add()
    {
        $arguments = func_get_args();

        if ($arguments[0] instanceof Node) {
            if (isset($arguments[1])) {
                throw new \InvalidArgumentException('The second argument must not be specified, when passing a VObject Node');
            }
            $arguments[0]->parent = $this;
            $newNode = $arguments[0];
        } elseif (is_string($arguments[0])) {
            $newNode = call_user_func_array([$this->root, 'create'], $arguments);
        } else {
            throw new \InvalidArgumentException('The first argument must either be a \\Sabre\\VObject\\Node or a string');
        }

        $name = $newNode->name;
        if (isset($this->children[$name])) {
            $this->children[$name][] = $newNode;
        } else {
            $this->children[$name] = [$newNode];
        }

        return $newNode;
    }

    /**
     * This method removes a component or property from this component.
     *
     * You can either specify the item by name (like DTSTART), in which case
     * all properties/components with that name will be removed, or you can
     * pass an instance of a property or component, in which case only that
     * exact item will be removed.
     *
     * @param string|Property|Component $item
     */
    public function remove($item)
    {
        if (is_string($item)) {
            // If there's no dot in the name, it's an exact property name and
            // we can just wipe out all those properties.
            //
            if (false === strpos($item, '.')) {
                unset($this->children[strtoupper($item)]);

                return;
            }
            // If there was a dot, we need to ask select() to help us out and
            // then we just call remove recursively.
            foreach ($this->select($item) as $child) {
                $this->remove($child);
            }
        } else {
            foreach ($this->select($item->name) as $k => $child) {
                if ($child === $item) {
                    unset($this->children[$item->name][$k]);

                    return;
                }
            }

            throw new \InvalidArgumentException('The item you passed to remove() was not a child of this component');
        }
    }

    /**
     * Returns a flat list of all the properties and components in this
     * component.
     *
     * @return array
     */
    public function children()
    {
        $result = [];
        foreach ($this->children as $childGroup) {
            $result = array_merge($result, $childGroup);
        }

        return $result;
    }

    /**
     * This method only returns a list of sub-components. Properties are
     * ignored.
     *
     * @return array
     */
    public function getComponents()
    {
        $result = [];

        foreach ($this->children as $childGroup) {
            foreach ($childGroup as $child) {
                if ($child instanceof self) {
                    $result[] = $child;
                }
            }
        }

        return $result;
    }

    /**
     * Returns an array with elements that match the specified name.
     *
     * This function is also aware of MIME-Directory groups (as they appear in
     * vcards). This means that if a property is grouped as "HOME.EMAIL", it
     * will also be returned when searching for just "EMAIL". If you want to
     * search for a property in a specific group, you can select on the entire
     * string ("HOME.EMAIL"). If you want to search on a specific property that
     * has not been assigned a group, specify ".EMAIL".
     *
     * @param string $name
     *
     * @return array
     */
    public function select($name)
    {
        $group = null;
        $name = strtoupper($name);
        if (false !== strpos($name, '.')) {
            list($group, $name) = explode('.', $name, 2);
        }
        if ('' === $name) {
            $name = null;
        }

        if (!is_null($name)) {
            $result = isset($this->children[$name]) ? $this->children[$name] : [];

            if (is_null($group)) {
                return $result;
            } else {
                // If we have a group filter as well, we need to narrow it down
                // more.
                return array_filter(
                    $result,
                    function ($child) use ($group) {
                        return $child instanceof Property && (null !== $child->group ? strtoupper($child->group) : '') === $group;
                    }
                );
            }
        }

        // If we got to this point, it means there was no 'name' specified for
        // searching, implying that this is a group-only search.
        $result = [];
        foreach ($this->children as $childGroup) {
            foreach ($childGroup as $child) {
                if ($child instanceof Property && (null !== $child->group ? strtoupper($child->group) : '') === $group) {
                    $result[] = $child;
                }
            }
        }

        return $result;
    }

    /**
     * Turns the object back into a serialized blob.
     *
     * @return string
     */
    public function serialize()
    {
        $str = 'BEGIN:'.$this->name."\r\n";

        /**
         * Gives a component a 'score' for sorting purposes.
         *
         * This is solely used by the childrenSort method.
         *
         * A higher score means the item will be lower in the list.
         * To avoid score collisions, each "score category" has a reasonable
         * space to accommodate elements. The $key is added to the $score to
         * preserve the original relative order of elements.
         *
         * @param int   $key
         * @param array $array
         *
         * @return int
         */
        $sortScore = function ($key, $array) {
            if ($array[$key] instanceof Component) {
                // We want to encode VTIMEZONE first, this is a personal
                // preference.
                if ('VTIMEZONE' === $array[$key]->name) {
                    $score = 300000000;

                    return $score + $key;
                } else {
                    $score = 400000000;

                    return $score + $key;
                }
            } else {
                // Properties get encoded first
                // VCARD version 4.0 wants the VERSION property to appear first
                if ($array[$key] instanceof Property) {
                    if ('VERSION' === $array[$key]->name) {
                        $score = 100000000;

                        return $score + $key;
                    } else {
                        // All other properties
                        $score = 200000000;

                        return $score + $key;
                    }
                }
            }
        };

        $children = $this->children();
        $tmp = $children;
        uksort(
            $children,
            function ($a, $b) use ($sortScore, $tmp) {
                $sA = $sortScore($a, $tmp);
                $sB = $sortScore($b, $tmp);

                return $sA - $sB;
            }
        );

        foreach ($children as $child) {
            $str .= $child->serialize();
        }
        $str .= 'END:'.$this->name."\r\n";

        return $str;
    }

    /**
     * This method returns an array, with the representation as it should be
     * encoded in JSON. This is used to create jCard or jCal documents.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $components = [];
        $properties = [];

        foreach ($this->children as $childGroup) {
            foreach ($childGroup as $child) {
                if ($child instanceof self) {
                    $components[] = $child->jsonSerialize();
                } else {
                    $properties[] = $child->jsonSerialize();
                }
            }
        }

        return [
            strtolower($this->name),
            $properties,
            $components,
        ];
    }

    /**
     * This method serializes the data into XML. This is used to create xCard or
     * xCal documents.
     *
     * @param Xml\Writer $writer XML writer
     */
    public function xmlSerialize(Xml\Writer $writer): void
    {
        $components = [];
        $properties = [];

        foreach ($this->children as $childGroup) {
            foreach ($childGroup as $child) {
                if ($child instanceof self) {
                    $components[] = $child;
                } else {
                    $properties[] = $child;
                }
            }
        }

        $writer->startElement(strtolower($this->name));

        if (!empty($properties)) {
            $writer->startElement('properties');

            foreach ($properties as $property) {
                $property->xmlSerialize($writer);
            }

            $writer->endElement();
        }

        if (!empty($components)) {
            $writer->startElement('components');

            foreach ($components as $component) {
                $component->xmlSerialize($writer);
            }

            $writer->endElement();
        }

        $writer->endElement();
    }

    /**
     * This method should return a list of default property values.
     *
     * @return array
     */
    protected function getDefaults()
    {
        return [];
    }

    /* Magic property accessors {{{ */

    /**
     * Using 'get' you will either get a property or component.
     *
     * If there were no child-elements found with the specified name,
     * null is returned.
     *
     * To use this, this may look something like this:
     *
     * $event = $calendar->VEVENT;
     *
     * @param string $name
     *
     * @return Property|null
     */
    public function __get($name)
    {
        if ('children' === $name) {
            throw new \RuntimeException('Starting sabre/vobject 4.0 the children property is now protected. You should use the children() method instead');
        }

        $matches = $this->select($name);
        if (0 === count($matches)) {
            return;
        } else {
            $firstMatch = current($matches);
            /* @var $firstMatch Property */
            $firstMatch->setIterator(new ElementList(array_values($matches)));

            return $firstMatch;
        }
    }

    /**
     * This method checks if a sub-element with the specified name exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        $matches = $this->select($name);

        return count($matches) > 0;
    }

    /**
     * Using the setter method you can add properties or subcomponents.
     *
     * You can either pass a Component, Property
     * object, or a string to automatically create a Property.
     *
     * If the item already exists, it will be removed. If you want to add
     * a new item with the same name, always use the add() method.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        $name = strtoupper($name);
        $this->remove($name);
        if ($value instanceof self || $value instanceof Property) {
            $this->add($value);
        } else {
            $this->add($name, $value);
        }
    }

    /**
     * Removes all properties and components within this component with the
     * specified name.
     *
     * @param string $name
     */
    public function __unset($name)
    {
        $this->remove($name);
    }

    /* }}} */

    /**
     * This method is automatically called when the object is cloned.
     * Specifically, this will ensure all child elements are also cloned.
     */
    public function __clone()
    {
        foreach ($this->children as $childName => $childGroup) {
            foreach ($childGroup as $key => $child) {
                $clonedChild = clone $child;
                $clonedChild->parent = $this;
                $clonedChild->root = $this->root;
                $this->children[$childName][$key] = $clonedChild;
            }
        }
    }

    /**
     * A simple list of validation rules.
     *
     * This is simply a list of properties, and how many times they either
     * must or must not appear.
     *
     * Possible values per property:
     *   * 0 - Must not appear.
     *   * 1 - Must appear exactly once.
     *   * + - Must appear at least once.
     *   * * - Can appear any number of times.
     *   * ? - May appear, but not more than once.
     *
     * It is also possible to specify defaults and severity levels for
     * violating the rule.
     *
     * See the VEVENT implementation for getValidationRules for a more complex
     * example.
     *
     * @var array
     */
    public function getValidationRules()
    {
        return [];
    }

    /**
     * Validates the node for correctness.
     *
     * The following options are supported:
     *   Node::REPAIR - May attempt to automatically repair the problem.
     *   Node::PROFILE_CARDDAV - Validate the vCard for CardDAV purposes.
     *   Node::PROFILE_CALDAV - Validate the iCalendar for CalDAV purposes.
     *
     * This method returns an array with detected problems.
     * Every element has the following properties:
     *
     *  * level - problem level.
     *  * message - A human-readable string describing the issue.
     *  * node - A reference to the problematic node.
     *
     * The level means:
     *   1 - The issue was repaired (only happens if REPAIR was turned on).
     *   2 - A warning.
     *   3 - An error.
     *
     * @param int $options
     *
     * @return array
     */
    public function validate($options = 0)
    {
        $rules = $this->getValidationRules();
        $defaults = $this->getDefaults();

        $propertyCounters = [];

        $messages = [];

        foreach ($this->children() as $child) {
            $name = strtoupper($child->name);
            if (!isset($propertyCounters[$name])) {
                $propertyCounters[$name] = 1;
            } else {
                ++$propertyCounters[$name];
            }
            $messages = array_merge($messages, $child->validate($options));
        }

        foreach ($rules as $propName => $rule) {
            switch ($rule) {
                case '0':
                    if (isset($propertyCounters[$propName])) {
                        $messages[] = [
                            'level' => 3,
                            'message' => $propName.' MUST NOT appear in a '.$this->name.' component',
                            'node' => $this,
                        ];
                    }
                    break;
                case '1':
                    if (!isset($propertyCounters[$propName]) || 1 !== $propertyCounters[$propName]) {
                        $repaired = false;
                        if ($options & self::REPAIR && isset($defaults[$propName])) {
                            $this->add($propName, $defaults[$propName]);
                            $repaired = true;
                        }
                        $messages[] = [
                            'level' => $repaired ? 1 : 3,
                            'message' => $propName.' MUST appear exactly once in a '.$this->name.' component',
                            'node' => $this,
                        ];
                    }
                    break;
                case '+':
                    if (!isset($propertyCounters[$propName]) || $propertyCounters[$propName] < 1) {
                        $messages[] = [
                            'level' => 3,
                            'message' => $propName.' MUST appear at least once in a '.$this->name.' component',
                            'node' => $this,
                        ];
                    }
                    break;
                case '*':
                    break;
                case '?':
                    if (isset($propertyCounters[$propName]) && $propertyCounters[$propName] > 1) {
                        $level = 3;

                        // We try to repair the same property appearing multiple times with the exact same value
                        // by removing the duplicates and keeping only one property
                        if ($options & self::REPAIR) {
                            $properties = array_unique($this->select($propName), SORT_REGULAR);

                            if (1 === count($properties)) {
                                $this->remove($propName);
                                $this->add($properties[0]);

                                $level = 1;
                            }
                        }

                        $messages[] = [
                            'level' => $level,
                            'message' => $propName.' MUST NOT appear more than once in a '.$this->name.' component',
                            'node' => $this,
                        ];
                    }
                    break;
            }
        }

        return $messages;
    }

    /**
     * Call this method on a document if you're done using it.
     *
     * It's intended to remove all circular references, so PHP can easily clean
     * it up.
     */
    public function destroy()
    {
        parent::destroy();
        foreach ($this->children as $childGroup) {
            foreach ($childGroup as $child) {
                $child->destroy();
            }
        }
        $this->children = [];
    }
}
