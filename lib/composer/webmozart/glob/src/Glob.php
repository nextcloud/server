<?php

/*
 * This file is part of the webmozart/glob package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Glob;

use InvalidArgumentException;
use Webmozart\Glob\Iterator\GlobIterator;
use Webmozart\PathUtil\Path;

/**
 * Searches and matches file paths using Ant-like globs.
 *
 * This class implements an Ant-like version of PHP's `glob()` function. The
 * wildcard "*" matches any number of characters except directory separators.
 * The double wildcard "**" matches any number of characters, including
 * directory separators.
 *
 * Use {@link glob()} to glob the filesystem for paths:
 *
 * ```php
 * foreach (Glob::glob('/project/**.twig') as $path) {
 *     // do something...
 * }
 * ```
 *
 * Use {@link match()} to match a file path against a glob:
 *
 * ```php
 * if (Glob::match('/project/views/index.html.twig', '/project/**.twig')) {
 *     // path matches
 * }
 * ```
 *
 * You can also filter an array of paths for all paths that match your glob with
 * {@link filter()}:
 *
 * ```php
 * $filteredPaths = Glob::filter($paths, '/project/**.twig');
 * ```
 *
 * Internally, the methods described above convert the glob into a regular
 * expression that is then matched against the matched paths. If you need to
 * match many paths against the same glob, you should convert the glob manually
 * and use {@link preg_match()} to test the paths:
 *
 * ```php
 * $staticPrefix = Glob::getStaticPrefix('/project/**.twig');
 * $regEx = Glob::toRegEx('/project/**.twig');
 *
 * if (0 !== strpos($path, $staticPrefix)) {
 *     // no match
 * }
 *
 * if (!preg_match($regEx, $path)) {
 *     // no match
 * }
 * ```
 *
 * The method {@link getStaticPrefix()} returns the part of the glob up to the
 * first wildcard "*". You should always test whether a path has this prefix
 * before calling the much more expensive {@link preg_match()}.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
final class Glob
{
    /**
     * Flag: Filter the values in {@link Glob::filter()}.
     */
    const FILTER_VALUE = 1;

    /**
     * Flag: Filter the keys in {@link Glob::filter()}.
     */
    const FILTER_KEY = 2;

    /**
     * Globs the file system paths matching the glob.
     *
     * The glob may contain the wildcard "*". This wildcard matches any number
     * of characters, *including* directory separators.
     *
     * ```php
     * foreach (Glob::glob('/project/**.twig') as $path) {
     *     // do something...
     * }
     * ```
     *
     * @param string $glob  The canonical glob. The glob should contain forward
     *                      slashes as directory separators only. It must not
     *                      contain any "." or ".." segments. Use the
     *                      "webmozart/path-util" utility to canonicalize globs
     *                      prior to calling this method.
     * @param int    $flags A bitwise combination of the flag constants in this
     *                      class.
     *
     * @return string[] The matching paths. The keys of the array are
     *                  incrementing integers.
     */
    public static function glob($glob, $flags = 0)
    {
        $results = iterator_to_array(new GlobIterator($glob, $flags));

        sort($results);

        return $results;
    }

    /**
     * Matches a path against a glob.
     *
     * ```php
     * if (Glob::match('/project/views/index.html.twig', '/project/**.twig')) {
     *     // path matches
     * }
     * ```
     *
     * @param string $path  The path to match.
     * @param string $glob  The canonical glob. The glob should contain forward
     *                      slashes as directory separators only. It must not
     *                      contain any "." or ".." segments. Use the
     *                      "webmozart/path-util" utility to canonicalize globs
     *                      prior to calling this method.
     * @param int    $flags A bitwise combination of the flag constants in
     *                      this class.
     *
     * @return bool Returns `true` if the path is matched by the glob.
     */
    public static function match($path, $glob, $flags = 0)
    {
        if (!self::isDynamic($glob)) {
            return $glob === $path;
        }

        if (0 !== strpos($path, self::getStaticPrefix($glob, $flags))) {
            return false;
        }

        if (!preg_match(self::toRegEx($glob, $flags), $path)) {
            return false;
        }

        return true;
    }

    /**
     * Filters an array for paths matching a glob.
     *
     * The filtered array is returned. This array preserves the keys of the
     * passed array.
     *
     * ```php
     * $filteredPaths = Glob::filter($paths, '/project/**.twig');
     * ```
     *
     * @param string[] $paths A list of paths.
     * @param string   $glob  The canonical glob. The glob should contain
     *                        forward slashes as directory separators only. It
     *                        must not contain any "." or ".." segments. Use the
     *                        "webmozart/path-util" utility to canonicalize
     *                        globs prior to calling this method.
     * @param int      $flags A bitwise combination of the flag constants in
     *                        this class.
     *
     * @return string[] The paths matching the glob indexed by their original
     *                  keys.
     */
    public static function filter(array $paths, $glob, $flags = self::FILTER_VALUE)
    {
        if (($flags & self::FILTER_VALUE) && ($flags & self::FILTER_KEY)) {
            throw new InvalidArgumentException('The flags Glob::FILTER_VALUE and Glob::FILTER_KEY cannot be passed at the same time.');
        }

        if (!self::isDynamic($glob)) {
            if ($flags & self::FILTER_KEY) {
                return isset($paths[$glob]) ? array($glob => $paths[$glob]) : array();
            }

            $key = array_search($glob, $paths);

            return false !== $key ? array($key => $glob) : array();
        }

        $staticPrefix = self::getStaticPrefix($glob, $flags);
        $regExp = self::toRegEx($glob, $flags);
        $filter = function ($path) use ($staticPrefix, $regExp) {
            return 0 === strpos($path, $staticPrefix) && preg_match($regExp, $path);
        };

        if (PHP_VERSION_ID >= 50600) {
            $filterFlags = ($flags & self::FILTER_KEY) ? ARRAY_FILTER_USE_KEY : 0;

            return array_filter($paths, $filter, $filterFlags);
        }

        // No support yet for the third argument of array_filter()
        if ($flags & self::FILTER_KEY) {
            $result = array();

            foreach ($paths as $path => $value) {
                if ($filter($path)) {
                    $result[$path] = $value;
                }
            }

            return $result;
        }

        return array_filter($paths, $filter);
    }

    /**
     * Returns the base path of a glob.
     *
     * This method returns the most specific directory that contains all files
     * matched by the glob. If this directory does not exist on the file system,
     * it's not necessary to execute the glob algorithm.
     *
     * More specifically, the "base path" is the longest path trailed by a "/"
     * on the left of the first wildcard "*". If the glob does not contain
     * wildcards, the directory name of the glob is returned.
     *
     * ```php
     * Glob::getBasePath('/css/*.css');
     * // => /css
     *
     * Glob::getBasePath('/css/style.css');
     * // => /css
     *
     * Glob::getBasePath('/css/st*.css');
     * // => /css
     *
     * Glob::getBasePath('/*.css');
     * // => /
     * ```
     *
     * @param string $glob  The canonical glob. The glob should contain forward
     *                      slashes as directory separators only. It must not
     *                      contain any "." or ".." segments. Use the
     *                      "webmozart/path-util" utility to canonicalize globs
     *                      prior to calling this method.
     * @param int    $flags A bitwise combination of the flag constants in this
     *                      class.
     *
     * @return string The base path of the glob.
     */
    public static function getBasePath($glob, $flags = 0)
    {
        // Search the static prefix for the last "/"
        $staticPrefix = self::getStaticPrefix($glob, $flags);

        if (false !== ($pos = strrpos($staticPrefix, '/'))) {
            // Special case: Return "/" if the only slash is at the beginning
            // of the glob
            if (0 === $pos) {
                return '/';
            }

            // Special case: Include trailing slash of "scheme:///foo"
            if ($pos - 3 === strpos($glob, '://')) {
                return substr($staticPrefix, 0, $pos + 1);
            }

            return substr($staticPrefix, 0, $pos);
        }

        // Glob contains no slashes on the left of the wildcard
        // Return an empty string
        return '';
    }

    /**
     * Converts a glob to a regular expression.
     *
     * Use this method if you need to match many paths against a glob:
     *
     * ```php
     * $staticPrefix = Glob::getStaticPrefix('/project/**.twig');
     * $regEx = Glob::toRegEx('/project/**.twig');
     *
     * if (0 !== strpos($path, $staticPrefix)) {
     *     // no match
     * }
     *
     * if (!preg_match($regEx, $path)) {
     *     // no match
     * }
     * ```
     *
     * You should always test whether a path contains the static prefix of the
     * glob returned by {@link getStaticPrefix()} to reduce the number of calls
     * to the expensive {@link preg_match()}.
     *
     * @param string $glob  The canonical glob. The glob should contain forward
     *                      slashes as directory separators only. It must not
     *                      contain any "." or ".." segments. Use the
     *                      "webmozart/path-util" utility to canonicalize globs
     *                      prior to calling this method.
     * @param int    $flags A bitwise combination of the flag constants in this
     *                      class.
     *
     * @return string The regular expression for matching the glob.
     */
    public static function toRegEx($glob, $flags = 0, $delimiter = '~')
    {
        if (!Path::isAbsolute($glob) && false === strpos($glob, '://')) {
            throw new InvalidArgumentException(sprintf(
                'The glob "%s" is not absolute and not a URI.',
                $glob
            ));
        }

        $inSquare = false;
        $curlyLevels = 0;
        $regex = '';
        $length = strlen($glob);

        for ($i = 0; $i < $length; ++$i) {
            $c = $glob[$i];

            switch ($c) {
                case '.':
                case '(':
                case ')':
                case '|':
                case '+':
                case '^':
                case '$':
                case $delimiter:
                    $regex .= "\\$c";
                    break;

                case '/':
                    if (isset($glob[$i + 3]) && '**/' === $glob[$i + 1].$glob[$i + 2].$glob[$i + 3]) {
                        $regex .= '/([^/]+/)*';
                        $i += 3;
                    } else {
                        $regex .= '/';
                    }
                    break;

                case '*':
                    $regex .= '[^/]*';
                    break;

                case '?':
                    $regex .= '.';
                    break;

                case '{':
                    $regex .= '(';
                    ++$curlyLevels;
                    break;

                case '}':
                    if ($curlyLevels > 0) {
                        $regex .= ')';
                        --$curlyLevels;
                    } else {
                        $regex .= '}';
                    }
                    break;

                case ',':
                    $regex .= $curlyLevels > 0 ? '|' : ',';
                    break;

                case '[':
                    $regex .= '[';
                    $inSquare = true;
                    if (isset($glob[$i + 1]) && '^' === $glob[$i + 1]) {
                        $regex .= '^';
                        ++$i;
                    }
                    break;

                case ']':
                    $regex .= $inSquare ? ']' : '\\]';
                    $inSquare = false;
                    break;

                case '-':
                    $regex .= $inSquare ? '-' : '\\-';
                    break;

                case '\\':
                    if (isset($glob[$i + 1])) {
                        switch ($glob[$i + 1]) {
                            case '*':
                            case '?':
                            case '{':
                            case '}':
                            case '[':
                            case ']':
                            case '-':
                            case '^':
                            case '\\':
                                $regex .= '\\'.$glob[$i + 1];
                                ++$i;
                                break;

                            default:
                                $regex .= '\\\\';
                        }
                    } else {
                        $regex .= '\\\\';
                    }
                    break;

                default:
                    $regex .= $c;
                    break;
            }
        }

        if ($inSquare) {
            throw new InvalidArgumentException(sprintf(
                'Invalid glob: missing ] in %s',
                $glob
            ));
        }

        if ($curlyLevels > 0) {
            throw new InvalidArgumentException(sprintf(
                'Invalid glob: missing } in %s',
                $glob
            ));
        }

        return $delimiter.'^'.$regex.'$'.$delimiter;
    }

    /**
     * Returns the static prefix of a glob.
     *
     * The "static prefix" is the part of the glob up to the first wildcard "*".
     * If the glob does not contain wildcards, the full glob is returned.
     *
     * @param string $glob  The canonical glob. The glob should contain forward
     *                      slashes as directory separators only. It must not
     *                      contain any "." or ".." segments. Use the
     *                      "webmozart/path-util" utility to canonicalize globs
     *                      prior to calling this method.
     * @param int    $flags A bitwise combination of the flag constants in this
     *                      class.
     *
     * @return string The static prefix of the glob.
     */
    public static function getStaticPrefix($glob, $flags = 0)
    {
        if (!Path::isAbsolute($glob) && false === strpos($glob, '://')) {
            throw new InvalidArgumentException(sprintf(
                'The glob "%s" is not absolute and not a URI.',
                $glob
            ));
        }

        $prefix = '';
        $length = strlen($glob);

        for ($i = 0; $i < $length; ++$i) {
            $c = $glob[$i];

            switch ($c) {
                case '/':
                    $prefix .= '/';
                    if (isset($glob[$i + 3]) && '**/' === $glob[$i + 1].$glob[$i + 2].$glob[$i + 3]) {
                        break 2;
                    }
                    break;

                case '*':
                case '?':
                case '{':
                case '[':
                    break 2;

                case '\\':
                    if (isset($glob[$i + 1])) {
                        switch ($glob[$i + 1]) {
                            case '*':
                            case '?':
                            case '{':
                            case '[':
                            case '\\':
                                $prefix .= $glob[$i + 1];
                                ++$i;
                                break;

                            default:
                                $prefix .= '\\';
                        }
                    } else {
                        $prefix .= '\\';
                    }
                    break;

                default:
                    $prefix .= $c;
                    break;
            }
        }

        return $prefix;
    }

    /**
     * Returns whether the glob contains a dynamic part.
     *
     * The glob contains a dynamic part if it contains an unescaped "*" or
     * "{" character.
     *
     * @param string $glob The glob to test.
     *
     * @return bool Returns `true` if the glob contains a dynamic part and
     *              `false` otherwise.
     */
    public static function isDynamic($glob)
    {
        return false !== strpos($glob, '*') || false !== strpos($glob, '{') || false !== strpos($glob, '?') || false !== strpos($glob, '[');
    }

    private function __construct()
    {
    }
}
