<?php
/**
 * @package        SimpleTest
 * @subpackage     Extensions
 */
/**
 * @package        SimpleTest
 * @subpackage     Extensions
 */
class CoverageUtils {

    static function mkdir($dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, True);
        } else {
            if (!is_dir($dir)) {
                throw new Exception($dir .' exists as a file, not a directory');
            }
        }
    }

    static function requireSqlite() {
        if (!self::isPackageClassAvailable('DB/sqlite.php', 'SQLiteDatabase')) {
            echo "sqlite library is required to be installed and available in include_path";
            exit(1);
        }
    }

    static function isPackageClassAvailable($includeFile, $class) {
        @include_once($includeFile);
        return class_exists($class);
    }

    /**
     * Parses simple parameters from CLI.
     *
     * Puts trailing parameters into string array in 'extraArguments'
     *
     * Example:
     * $args = CoverageUtil::parseArguments($_SERVER['argv']);
     * if ($args['verbose']) echo "Verbose Mode On\n";
     * $files = $args['extraArguments'];
     *
     * Example CLI:
     *  --foo=blah -x -h  some trailing arguments
     *
     * if multiValueMode is true
     * Example CLI:
     *  --include=a --include=b --exclude=c
     * Then
     *  $args = CoverageUtil::parseArguments($_SERVER['argv']);
     *  $args['include[]'] will equal array('a', 'b')
     *  $args['exclude[]'] will equal array('c')
     *  $args['exclude'] will equal c
     *  $args['include'] will equal b   NOTE: only keeps last value
     *
     * @param unknown_type $argv
     * @param supportMutliValue - will store 2nd copy of value in an array with key "foo[]"
     * @return unknown
     */
    static public function parseArguments($argv, $mutliValueMode = False) {
        $args = array();
        $args['extraArguments'] = array();
        array_shift($argv); // scriptname
        foreach ($argv as $arg) {
            if (ereg('^--([^=]+)=(.*)', $arg, $reg)) {
                $args[$reg[1]] = $reg[2];
                if ($mutliValueMode) {
                    self::addItemAsArray($args, $reg[1], $reg[2]);
                }
            } elseif (ereg('^[-]{1,2}([^[:blank:]]+)', $arg, $reg)) {
                $nonnull = '';
                $args[$reg[1]] = $nonnull;
                if ($mutliValueMode) {
                    self::addItemAsArray($args, $reg[1], $nonnull);
                }
            } else {
                $args['extraArguments'][] = $arg;
            }
        }

        return $args;
    }

    /**
     * Adds a value as an array of one, or appends to an existing array elements
     *
     * @param unknown_type $array
     * @param unknown_type $item
     */
    static function addItemAsArray(&$array, $key, $item) {
        $array_key = $key .'[]';
        if (array_key_exists($array_key, $array)) {
            $array[$array_key][] = $item;
        } else {
            $array[$array_key] = array($item);
        }
    }

    /**
     * isset function with default value
     *
     * Example:  $z = CoverageUtils::issetOr($array[$key], 'no value given')
     *
     * @param unknown_type $val
     * @param unknown_type $default
     * @return first value unless value is not set then returns 2nd arg or null if no 2nd arg
     */
    static public function issetOr(&$val, $default = null)
    {
        return isset($val) ? $val : $default;
    }
}
?>