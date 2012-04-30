<?php
/**
 *  base include file for SimpleTest
 *  @package    SimpleTest
 *  @subpackage UnitTester
 *  @version    $Id: dumper.php 1909 2009-07-29 15:58:11Z dgheath $
 */

/**
 *    Parses the command line arguments.
 *    @package  SimpleTest
 *    @subpackage   UnitTester
 */
class SimpleArguments {
    private $all = array();

    /**
     * Parses the command line arguments. The usual formats
     * are supported:
     * -f value
     * -f=value
     * --flag=value
     * --flag value
     * -f           (true)
     * --flag       (true)
     * @param array $arguments      Normally the PHP $argv.
     */
    function __construct($arguments) {
        array_shift($arguments);
        while (count($arguments) > 0) {
            list($key, $value) = $this->parseArgument($arguments);
            $this->assign($key, $value);
        }
    }

    /**
     * Sets the value in the argments object. If multiple
     * values are added under the same key, the key will
     * give an array value in the order they were added.
     * @param string $key    The variable to assign to.
     * @param string value   The value that would norally
     *                       be colected on the command line.
     */
    function assign($key, $value) {
        if ($this->$key === false) {
            $this->all[$key] = $value;
        } elseif (! is_array($this->$key)) {
            $this->all[$key] = array($this->$key, $value);
        } else {
            $this->all[$key][] = $value;
        }
    }

    /**
     * Extracts the next key and value from the argument list.
     * @param array $arguments      The remaining arguments to be parsed.
     *                              The argument list will be reduced.
     * @return array                Two item array of key and value.
     *                              If no value can be found it will
     *                              have the value true assigned instead.
     */
    private function parseArgument(&$arguments) {
        $argument = array_shift($arguments);
        if (preg_match('/^-(\w)=(.+)$/', $argument, $matches)) {
            return array($matches[1], $matches[2]);
        } elseif (preg_match('/^-(\w)$/', $argument, $matches)) {
            return array($matches[1], $this->nextNonFlagElseTrue($arguments));
        } elseif (preg_match('/^--(\w+)=(.+)$/', $argument, $matches)) {
            return array($matches[1], $matches[2]);
        } elseif (preg_match('/^--(\w+)$/', $argument, $matches)) {
            return array($matches[1], $this->nextNonFlagElseTrue($arguments));
        }
    }

    /**
     * Attempts to use the next argument as a value. It
     * won't use what it thinks is a flag.
     * @param array $arguments    Remaining arguments to be parsed.
     *                            This variable is modified if there
     *                            is a value to be extracted.
     * @return string/boolean     The next value unless it's a flag.
     */
    private function nextNonFlagElseTrue(&$arguments) {
        return $this->valueIsNext($arguments) ? array_shift($arguments) : true;
    }

    /**
     * Test to see if the next available argument is a valid value.
     * If it starts with "-" or "--" it's a flag and doesn't count.
     * @param array $arguments    Remaining arguments to be parsed.
     *                            Not affected by this call.
     * boolean                    True if valid value.
     */
    function valueIsNext($arguments) {
        return isset($arguments[0]) && ! $this->isFlag($arguments[0]);
    }

    /**
     * It's a flag if it starts with "-" or "--".
     * @param string $argument       Value to be tested.
     * @return boolean               True if it's a flag.
     */
    function isFlag($argument) {
        return strncmp($argument, '-', 1) == 0;
    }

    /**
     * The arguments are available as individual member
     * variables on the object.
     * @param string $key              Argument name.
     * @return string/array/boolean    Either false for no value,
     *                                 the value as a string or
     *                                 a list of multiple values if
     *                                 the flag had been specified more
     *                                 than once.
     */
    function __get($key) {
        if (isset($this->all[$key])) {
            return $this->all[$key];
        }
        return false;
    }

    /**
     * The entire argument set as a hash.
     * @return hash         Each argument and it's value(s).
     */
    function all() {
        return $this->all;
    }
}

/**
 *    Renders the help for the command line arguments.
 *    @package  SimpleTest
 *    @subpackage   UnitTester
 */
class SimpleHelp {
    private $overview;
    private $flag_sets = array();
    private $explanations = array();

    /**
     * Sets up the top level explanation for the program.
     * @param string $overview        Summary of program.
     */
    function __construct($overview = '') {
        $this->overview = $overview;
    }

    /**
     * Adds the explanation for a group of flags that all
     * have the same function.
     * @param string/array $flags       Flag and alternates. Don't
     *                                  worry about leading dashes
     *                                  as these are inserted automatically.
     * @param string $explanation       What that flag group does.
     */
    function explainFlag($flags, $explanation) {
        $flags = is_array($flags) ? $flags : array($flags);
        $this->flag_sets[] = $flags;
        $this->explanations[] = $explanation;
    }

    /**
     * Generates the help text.
     * @returns string      The complete formatted text.
     */
    function render() {
        $tab_stop = $this->longestFlag($this->flag_sets) + 4;
        $text = $this->overview . "\n";
        for ($i = 0; $i < count($this->flag_sets); $i++) {
            $text .= $this->renderFlagSet($this->flag_sets[$i], $this->explanations[$i], $tab_stop);
        }
        return $this->noDuplicateNewLines($text);
    }

    /**
     * Works out the longest flag for formatting purposes.
     * @param array $flag_sets      The internal flag set list.
     */
    private function longestFlag($flag_sets) {
        $longest = 0;
        foreach ($flag_sets as $flags) {
            foreach ($flags as $flag) {
                $longest = max($longest, strlen($this->renderFlag($flag)));
            }
        }
        return $longest;
    }

    /**
     * Generates the text for a single flag and it's alternate flags.
     * @returns string           Help text for that flag group.
     */
    private function renderFlagSet($flags, $explanation, $tab_stop) {
        $flag = array_shift($flags);
        $text = str_pad($this->renderFlag($flag), $tab_stop, ' ') . $explanation . "\n";
        foreach ($flags as $flag) {
            $text .= '  ' . $this->renderFlag($flag) . "\n";
        }
        return $text;
    }

    /**
     * Generates the flag name including leading dashes.
     * @param string $flag          Just the name.
     * @returns                     Fag with apropriate dashes.
     */
    private function renderFlag($flag) {
        return (strlen($flag) == 1 ? '-' : '--') . $flag;
    }

    /**
     * Converts multiple new lines into a single new line.
     * Just there to trap accidental duplicate new lines.
     * @param string $text      Text to clean up.
     * @returns string          Text with no blank lines.
     */
    private function noDuplicateNewLines($text) {
        return preg_replace('/(\n+)/', "\n", $text);
    }
}
?>