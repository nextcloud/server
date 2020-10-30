<?php

/*
 * This file is part of the webmozart/path-util package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\PathUtil;

use InvalidArgumentException;
use RuntimeException;
use Webmozart\Assert\Assert;

/**
 * Contains utility methods for handling path strings.
 *
 * The methods in this class are able to deal with both UNIX and Windows paths
 * with both forward and backward slashes. All methods return normalized parts
 * containing only forward slashes and no excess "." and ".." segments.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Thomas Schulz <mail@king2500.net>
 */
final class Path
{
    /**
     * The number of buffer entries that triggers a cleanup operation.
     */
    const CLEANUP_THRESHOLD = 1250;

    /**
     * The buffer size after the cleanup operation.
     */
    const CLEANUP_SIZE = 1000;

    /**
     * Buffers input/output of {@link canonicalize()}.
     *
     * @var array
     */
    private static $buffer = array();

    /**
     * The size of the buffer.
     *
     * @var int
     */
    private static $bufferSize = 0;

    /**
     * Canonicalizes the given path.
     *
     * During normalization, all slashes are replaced by forward slashes ("/").
     * Furthermore, all "." and ".." segments are removed as far as possible.
     * ".." segments at the beginning of relative paths are not removed.
     *
     * ```php
     * echo Path::canonicalize("\webmozart\puli\..\css\style.css");
     * // => /webmozart/css/style.css
     *
     * echo Path::canonicalize("../css/./style.css");
     * // => ../css/style.css
     * ```
     *
     * This method is able to deal with both UNIX and Windows paths.
     *
     * @param string $path A path string.
     *
     * @return string The canonical path.
     *
     * @since 1.0 Added method.
     * @since 2.0 Method now fails if $path is not a string.
     * @since 2.1 Added support for `~`.
     */
    public static function canonicalize($path)
    {
        if ('' === $path) {
            return '';
        }

        Assert::string($path, 'The path must be a string. Got: %s');

        // This method is called by many other methods in this class. Buffer
        // the canonicalized paths to make up for the severe performance
        // decrease.
        if (isset(self::$buffer[$path])) {
            return self::$buffer[$path];
        }

        // Replace "~" with user's home directory.
        if ('~' === $path[0]) {
            $path = static::getHomeDirectory().substr($path, 1);
        }

        $path = str_replace('\\', '/', $path);

        list($root, $pathWithoutRoot) = self::split($path);

        $parts = explode('/', $pathWithoutRoot);
        $canonicalParts = array();

        // Collapse "." and "..", if possible
        foreach ($parts as $part) {
            if ('.' === $part || '' === $part) {
                continue;
            }

            // Collapse ".." with the previous part, if one exists
            // Don't collapse ".." if the previous part is also ".."
            if ('..' === $part && count($canonicalParts) > 0
                    && '..' !== $canonicalParts[count($canonicalParts) - 1]) {
                array_pop($canonicalParts);

                continue;
            }

            // Only add ".." prefixes for relative paths
            if ('..' !== $part || '' === $root) {
                $canonicalParts[] = $part;
            }
        }

        // Add the root directory again
        self::$buffer[$path] = $canonicalPath = $root.implode('/', $canonicalParts);
        ++self::$bufferSize;

        // Clean up regularly to prevent memory leaks
        if (self::$bufferSize > self::CLEANUP_THRESHOLD) {
            self::$buffer = array_slice(self::$buffer, -self::CLEANUP_SIZE, null, true);
            self::$bufferSize = self::CLEANUP_SIZE;
        }

        return $canonicalPath;
    }

    /**
     * Normalizes the given path.
     *
     * During normalization, all slashes are replaced by forward slashes ("/").
     * Contrary to {@link canonicalize()}, this method does not remove invalid
     * or dot path segments. Consequently, it is much more efficient and should
     * be used whenever the given path is known to be a valid, absolute system
     * path.
     *
     * This method is able to deal with both UNIX and Windows paths.
     *
     * @param string $path A path string.
     *
     * @return string The normalized path.
     *
     * @since 2.2 Added method.
     */
    public static function normalize($path)
    {
        Assert::string($path, 'The path must be a string. Got: %s');

        return str_replace('\\', '/', $path);
    }

    /**
     * Returns the directory part of the path.
     *
     * This method is similar to PHP's dirname(), but handles various cases
     * where dirname() returns a weird result:
     *
     *  - dirname() does not accept backslashes on UNIX
     *  - dirname("C:/webmozart") returns "C:", not "C:/"
     *  - dirname("C:/") returns ".", not "C:/"
     *  - dirname("C:") returns ".", not "C:/"
     *  - dirname("webmozart") returns ".", not ""
     *  - dirname() does not canonicalize the result
     *
     * This method fixes these shortcomings and behaves like dirname()
     * otherwise.
     *
     * The result is a canonical path.
     *
     * @param string $path A path string.
     *
     * @return string The canonical directory part. Returns the root directory
     *                if the root directory is passed. Returns an empty string
     *                if a relative path is passed that contains no slashes.
     *                Returns an empty string if an empty string is passed.
     *
     * @since 1.0 Added method.
     * @since 2.0 Method now fails if $path is not a string.
     */
    public static function getDirectory($path)
    {
        if ('' === $path) {
            return '';
        }

        $path = static::canonicalize($path);

        // Maintain scheme
        if (false !== ($pos = strpos($path, '://'))) {
            $scheme = substr($path, 0, $pos + 3);
            $path = substr($path, $pos + 3);
        } else {
            $scheme = '';
        }

        if (false !== ($pos = strrpos($path, '/'))) {
            // Directory equals root directory "/"
            if (0 === $pos) {
                return $scheme.'/';
            }

            // Directory equals Windows root "C:/"
            if (2 === $pos && ctype_alpha($path[0]) && ':' === $path[1]) {
                return $scheme.substr($path, 0, 3);
            }

            return $scheme.substr($path, 0, $pos);
        }

        return '';
    }

    /**
     * Returns canonical path of the user's home directory.
     *
     * Supported operating systems:
     *
     *  - UNIX
     *  - Windows8 and upper
     *
     * If your operation system or environment isn't supported, an exception is thrown.
     *
     * The result is a canonical path.
     *
     * @return string The canonical home directory
     *
     * @throws RuntimeException If your operation system or environment isn't supported
     *
     * @since 2.1 Added method.
     */
    public static function getHomeDirectory()
    {
        // For UNIX support
        if (getenv('HOME')) {
            return static::canonicalize(getenv('HOME'));
        }

        // For >= Windows8 support
        if (getenv('HOMEDRIVE') && getenv('HOMEPATH')) {
            return static::canonicalize(getenv('HOMEDRIVE').getenv('HOMEPATH'));
        }

        throw new RuntimeException("Your environment or operation system isn't supported");
    }

    /**
     * Returns the root directory of a path.
     *
     * The result is a canonical path.
     *
     * @param string $path A path string.
     *
     * @return string The canonical root directory. Returns an empty string if
     *                the given path is relative or empty.
     *
     * @since 1.0 Added method.
     * @since 2.0 Method now fails if $path is not a string.
     */
    public static function getRoot($path)
    {
        if ('' === $path) {
            return '';
        }

        Assert::string($path, 'The path must be a string. Got: %s');

        // Maintain scheme
        if (false !== ($pos = strpos($path, '://'))) {
            $scheme = substr($path, 0, $pos + 3);
            $path = substr($path, $pos + 3);
        } else {
            $scheme = '';
        }

        // UNIX root "/" or "\" (Windows style)
        if ('/' === $path[0] || '\\' === $path[0]) {
            return $scheme.'/';
        }

        $length = strlen($path);

        // Windows root
        if ($length > 1 && ctype_alpha($path[0]) && ':' === $path[1]) {
            // Special case: "C:"
            if (2 === $length) {
                return $scheme.$path.'/';
            }

            // Normal case: "C:/ or "C:\"
            if ('/' === $path[2] || '\\' === $path[2]) {
                return $scheme.$path[0].$path[1].'/';
            }
        }

        return '';
    }

    /**
     * Returns the file name from a file path.
     *
     * @param string $path The path string.
     *
     * @return string The file name.
     *
     * @since 1.1 Added method.
     * @since 2.0 Method now fails if $path is not a string.
     */
    public static function getFilename($path)
    {
        if ('' === $path) {
            return '';
        }

        Assert::string($path, 'The path must be a string. Got: %s');

        return basename($path);
    }

    /**
     * Returns the file name without the extension from a file path.
     *
     * @param string      $path      The path string.
     * @param string|null $extension If specified, only that extension is cut
     *                               off (may contain leading dot).
     *
     * @return string The file name without extension.
     *
     * @since 1.1 Added method.
     * @since 2.0 Method now fails if $path or $extension have invalid types.
     */
    public static function getFilenameWithoutExtension($path, $extension = null)
    {
        if ('' === $path) {
            return '';
        }

        Assert::string($path, 'The path must be a string. Got: %s');
        Assert::nullOrString($extension, 'The extension must be a string or null. Got: %s');

        if (null !== $extension) {
            // remove extension and trailing dot
            return rtrim(basename($path, $extension), '.');
        }

        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Returns the extension from a file path.
     *
     * @param string $path           The path string.
     * @param bool   $forceLowerCase Forces the extension to be lower-case
     *                               (requires mbstring extension for correct
     *                               multi-byte character handling in extension).
     *
     * @return string The extension of the file path (without leading dot).
     *
     * @since 1.1 Added method.
     * @since 2.0 Method now fails if $path is not a string.
     */
    public static function getExtension($path, $forceLowerCase = false)
    {
        if ('' === $path) {
            return '';
        }

        Assert::string($path, 'The path must be a string. Got: %s');

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if ($forceLowerCase) {
            $extension = self::toLower($extension);
        }

        return $extension;
    }

    /**
     * Returns whether the path has an extension.
     *
     * @param string            $path       The path string.
     * @param string|array|null $extensions If null or not provided, checks if
     *                                      an extension exists, otherwise
     *                                      checks for the specified extension
     *                                      or array of extensions (with or
     *                                      without leading dot).
     * @param bool              $ignoreCase Whether to ignore case-sensitivity
     *                                      (requires mbstring extension for
     *                                      correct multi-byte character
     *                                      handling in the extension).
     *
     * @return bool Returns `true` if the path has an (or the specified)
     *              extension and `false` otherwise.
     *
     * @since 1.1 Added method.
     * @since 2.0 Method now fails if $path or $extensions have invalid types.
     */
    public static function hasExtension($path, $extensions = null, $ignoreCase = false)
    {
        if ('' === $path) {
            return false;
        }

        $extensions = is_object($extensions) ? array($extensions) : (array) $extensions;

        Assert::allString($extensions, 'The extensions must be strings. Got: %s');

        $actualExtension = self::getExtension($path, $ignoreCase);

        // Only check if path has any extension
        if (empty($extensions)) {
            return '' !== $actualExtension;
        }

        foreach ($extensions as $key => $extension) {
            if ($ignoreCase) {
                $extension = self::toLower($extension);
            }

            // remove leading '.' in extensions array
            $extensions[$key] = ltrim($extension, '.');
        }

        return in_array($actualExtension, $extensions);
    }

    /**
     * Changes the extension of a path string.
     *
     * @param string $path      The path string with filename.ext to change.
     * @param string $extension New extension (with or without leading dot).
     *
     * @return string The path string with new file extension.
     *
     * @since 1.1 Added method.
     * @since 2.0 Method now fails if $path or $extension is not a string.
     */
    public static function changeExtension($path, $extension)
    {
        if ('' === $path) {
            return '';
        }

        Assert::string($extension, 'The extension must be a string. Got: %s');

        $actualExtension = self::getExtension($path);
        $extension = ltrim($extension, '.');

        // No extension for paths
        if ('/' === substr($path, -1)) {
            return $path;
        }

        // No actual extension in path
        if (empty($actualExtension)) {
            return $path.('.' === substr($path, -1) ? '' : '.').$extension;
        }

        return substr($path, 0, -strlen($actualExtension)).$extension;
    }

    /**
     * Returns whether a path is absolute.
     *
     * @param string $path A path string.
     *
     * @return bool Returns true if the path is absolute, false if it is
     *              relative or empty.
     *
     * @since 1.0 Added method.
     * @since 2.0 Method now fails if $path is not a string.
     */
    public static function isAbsolute($path)
    {
        if ('' === $path) {
            return false;
        }

        Assert::string($path, 'The path must be a string. Got: %s');

        // Strip scheme
        if (false !== ($pos = strpos($path, '://'))) {
            $path = substr($path, $pos + 3);
        }

        // UNIX root "/" or "\" (Windows style)
        if ('/' === $path[0] || '\\' === $path[0]) {
            return true;
        }

        // Windows root
        if (strlen($path) > 1 && ctype_alpha($path[0]) && ':' === $path[1]) {
            // Special case: "C:"
            if (2 === strlen($path)) {
                return true;
            }

            // Normal case: "C:/ or "C:\"
            if ('/' === $path[2] || '\\' === $path[2]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether a path is relative.
     *
     * @param string $path A path string.
     *
     * @return bool Returns true if the path is relative or empty, false if
     *              it is absolute.
     *
     * @since 1.0 Added method.
     * @since 2.0 Method now fails if $path is not a string.
     */
    public static function isRelative($path)
    {
        return !static::isAbsolute($path);
    }

    /**
     * Turns a relative path into an absolute path.
     *
     * Usually, the relative path is appended to the given base path. Dot
     * segments ("." and "..") are removed/collapsed and all slashes turned
     * into forward slashes.
     *
     * ```php
     * echo Path::makeAbsolute("../style.css", "/webmozart/puli/css");
     * // => /webmozart/puli/style.css
     * ```
     *
     * If an absolute path is passed, that path is returned unless its root
     * directory is different than the one of the base path. In that case, an
     * exception is thrown.
     *
     * ```php
     * Path::makeAbsolute("/style.css", "/webmozart/puli/css");
     * // => /style.css
     *
     * Path::makeAbsolute("C:/style.css", "C:/webmozart/puli/css");
     * // => C:/style.css
     *
     * Path::makeAbsolute("C:/style.css", "/webmozart/puli/css");
     * // InvalidArgumentException
     * ```
     *
     * If the base path is not an absolute path, an exception is thrown.
     *
     * The result is a canonical path.
     *
     * @param string $path     A path to make absolute.
     * @param string $basePath An absolute base path.
     *
     * @return string An absolute path in canonical form.
     *
     * @throws InvalidArgumentException If the base path is not absolute or if
     *                                  the given path is an absolute path with
     *                                  a different root than the base path.
     *
     * @since 1.0   Added method.
     * @since 2.0   Method now fails if $path or $basePath is not a string.
     * @since 2.2.2 Method does not fail anymore of $path and $basePath are
     *              absolute, but on different partitions.
     */
    public static function makeAbsolute($path, $basePath)
    {
        Assert::stringNotEmpty($basePath, 'The base path must be a non-empty string. Got: %s');

        if (!static::isAbsolute($basePath)) {
            throw new InvalidArgumentException(sprintf(
                'The base path "%s" is not an absolute path.',
                $basePath
            ));
        }

        if (static::isAbsolute($path)) {
            return static::canonicalize($path);
        }

        if (false !== ($pos = strpos($basePath, '://'))) {
            $scheme = substr($basePath, 0, $pos + 3);
            $basePath = substr($basePath, $pos + 3);
        } else {
            $scheme = '';
        }

        return $scheme.self::canonicalize(rtrim($basePath, '/\\').'/'.$path);
    }

    /**
     * Turns a path into a relative path.
     *
     * The relative path is created relative to the given base path:
     *
     * ```php
     * echo Path::makeRelative("/webmozart/style.css", "/webmozart/puli");
     * // => ../style.css
     * ```
     *
     * If a relative path is passed and the base path is absolute, the relative
     * path is returned unchanged:
     *
     * ```php
     * Path::makeRelative("style.css", "/webmozart/puli/css");
     * // => style.css
     * ```
     *
     * If both paths are relative, the relative path is created with the
     * assumption that both paths are relative to the same directory:
     *
     * ```php
     * Path::makeRelative("style.css", "webmozart/puli/css");
     * // => ../../../style.css
     * ```
     *
     * If both paths are absolute, their root directory must be the same,
     * otherwise an exception is thrown:
     *
     * ```php
     * Path::makeRelative("C:/webmozart/style.css", "/webmozart/puli");
     * // InvalidArgumentException
     * ```
     *
     * If the passed path is absolute, but the base path is not, an exception
     * is thrown as well:
     *
     * ```php
     * Path::makeRelative("/webmozart/style.css", "webmozart/puli");
     * // InvalidArgumentException
     * ```
     *
     * If the base path is not an absolute path, an exception is thrown.
     *
     * The result is a canonical path.
     *
     * @param string $path     A path to make relative.
     * @param string $basePath A base path.
     *
     * @return string A relative path in canonical form.
     *
     * @throws InvalidArgumentException If the base path is not absolute or if
     *                                  the given path has a different root
     *                                  than the base path.
     *
     * @since 1.0 Added method.
     * @since 2.0 Method now fails if $path or $basePath is not a string.
     */
    public static function makeRelative($path, $basePath)
    {
        Assert::string($basePath, 'The base path must be a string. Got: %s');

        $path = static::canonicalize($path);
        $basePath = static::canonicalize($basePath);

        list($root, $relativePath) = self::split($path);
        list($baseRoot, $relativeBasePath) = self::split($basePath);

        // If the base path is given as absolute path and the path is already
        // relative, consider it to be relative to the given absolute path
        // already
        if ('' === $root && '' !== $baseRoot) {
            // If base path is already in its root
            if ('' === $relativeBasePath) {
                $relativePath = ltrim($relativePath, './\\');
            }

            return $relativePath;
        }

        // If the passed path is absolute, but the base path is not, we
        // cannot generate a relative path
        if ('' !== $root && '' === $baseRoot) {
            throw new InvalidArgumentException(sprintf(
                'The absolute path "%s" cannot be made relative to the '.
                'relative path "%s". You should provide an absolute base '.
                'path instead.',
                $path,
                $basePath
            ));
        }

        // Fail if the roots of the two paths are different
        if ($baseRoot && $root !== $baseRoot) {
            throw new InvalidArgumentException(sprintf(
                'The path "%s" cannot be made relative to "%s", because they '.
                'have different roots ("%s" and "%s").',
                $path,
                $basePath,
                $root,
                $baseRoot
            ));
        }

        if ('' === $relativeBasePath) {
            return $relativePath;
        }

        // Build a "../../" prefix with as many "../" parts as necessary
        $parts = explode('/', $relativePath);
        $baseParts = explode('/', $relativeBasePath);
        $dotDotPrefix = '';

        // Once we found a non-matching part in the prefix, we need to add
        // "../" parts for all remaining parts
        $match = true;

        foreach ($baseParts as $i => $basePart) {
            if ($match && isset($parts[$i]) && $basePart === $parts[$i]) {
                unset($parts[$i]);

                continue;
            }

            $match = false;
            $dotDotPrefix .= '../';
        }

        return rtrim($dotDotPrefix.implode('/', $parts), '/');
    }

    /**
     * Returns whether the given path is on the local filesystem.
     *
     * @param string $path A path string.
     *
     * @return bool Returns true if the path is local, false for a URL.
     *
     * @since 1.0 Added method.
     * @since 2.0 Method now fails if $path is not a string.
     */
    public static function isLocal($path)
    {
        Assert::string($path, 'The path must be a string. Got: %s');

        return '' !== $path && false === strpos($path, '://');
    }

    /**
     * Returns the longest common base path of a set of paths.
     *
     * Dot segments ("." and "..") are removed/collapsed and all slashes turned
     * into forward slashes.
     *
     * ```php
     * $basePath = Path::getLongestCommonBasePath(array(
     *     '/webmozart/css/style.css',
     *     '/webmozart/css/..'
     * ));
     * // => /webmozart
     * ```
     *
     * The root is returned if no common base path can be found:
     *
     * ```php
     * $basePath = Path::getLongestCommonBasePath(array(
     *     '/webmozart/css/style.css',
     *     '/puli/css/..'
     * ));
     * // => /
     * ```
     *
     * If the paths are located on different Windows partitions, `null` is
     * returned.
     *
     * ```php
     * $basePath = Path::getLongestCommonBasePath(array(
     *     'C:/webmozart/css/style.css',
     *     'D:/webmozart/css/..'
     * ));
     * // => null
     * ```
     *
     * @param array $paths A list of paths.
     *
     * @return string|null The longest common base path in canonical form or
     *                     `null` if the paths are on different Windows
     *                     partitions.
     *
     * @since 1.0 Added method.
     * @since 2.0 Method now fails if $paths are not strings.
     */
    public static function getLongestCommonBasePath(array $paths)
    {
        Assert::allString($paths, 'The paths must be strings. Got: %s');

        list($bpRoot, $basePath) = self::split(self::canonicalize(reset($paths)));

        for (next($paths); null !== key($paths) && '' !== $basePath; next($paths)) {
            list($root, $path) = self::split(self::canonicalize(current($paths)));

            // If we deal with different roots (e.g. C:/ vs. D:/), it's time
            // to quit
            if ($root !== $bpRoot) {
                return null;
            }

            // Make the base path shorter until it fits into path
            while (true) {
                if ('.' === $basePath) {
                    // No more base paths
                    $basePath = '';

                    // Next path
                    continue 2;
                }

                // Prevent false positives for common prefixes
                // see isBasePath()
                if (0 === strpos($path.'/', $basePath.'/')) {
                    // Next path
                    continue 2;
                }

                $basePath = dirname($basePath);
            }
        }

        return $bpRoot.$basePath;
    }

    /**
     * Joins two or more path strings.
     *
     * The result is a canonical path.
     *
     * @param string[]|string $paths Path parts as parameters or array.
     *
     * @return string The joint path.
     *
     * @since 2.0 Added method.
     */
    public static function join($paths)
    {
        if (!is_array($paths)) {
            $paths = func_get_args();
        }

        Assert::allString($paths, 'The paths must be strings. Got: %s');

        $finalPath = null;
        $wasScheme = false;

        foreach ($paths as $path) {
            $path = (string) $path;

            if ('' === $path) {
                continue;
            }

            if (null === $finalPath) {
                // For first part we keep slashes, like '/top', 'C:\' or 'phar://'
                $finalPath = $path;
                $wasScheme = (strpos($path, '://') !== false);
                continue;
            }

            // Only add slash if previous part didn't end with '/' or '\'
            if (!in_array(substr($finalPath, -1), array('/', '\\'))) {
                $finalPath .= '/';
            }

            // If first part included a scheme like 'phar://' we allow current part to start with '/', otherwise trim
            $finalPath .= $wasScheme ? $path : ltrim($path, '/');
            $wasScheme = false;
        }

        if (null === $finalPath) {
            return '';
        }

        return self::canonicalize($finalPath);
    }

    /**
     * Returns whether a path is a base path of another path.
     *
     * Dot segments ("." and "..") are removed/collapsed and all slashes turned
     * into forward slashes.
     *
     * ```php
     * Path::isBasePath('/webmozart', '/webmozart/css');
     * // => true
     *
     * Path::isBasePath('/webmozart', '/webmozart');
     * // => true
     *
     * Path::isBasePath('/webmozart', '/webmozart/..');
     * // => false
     *
     * Path::isBasePath('/webmozart', '/puli');
     * // => false
     * ```
     *
     * @param string $basePath The base path to test.
     * @param string $ofPath   The other path.
     *
     * @return bool Whether the base path is a base path of the other path.
     *
     * @since 1.0 Added method.
     * @since 2.0 Method now fails if $basePath or $ofPath is not a string.
     */
    public static function isBasePath($basePath, $ofPath)
    {
        Assert::string($basePath, 'The base path must be a string. Got: %s');

        $basePath = self::canonicalize($basePath);
        $ofPath = self::canonicalize($ofPath);

        // Append slashes to prevent false positives when two paths have
        // a common prefix, for example /base/foo and /base/foobar.
        // Don't append a slash for the root "/", because then that root
        // won't be discovered as common prefix ("//" is not a prefix of
        // "/foobar/").
        return 0 === strpos($ofPath.'/', rtrim($basePath, '/').'/');
    }

    /**
     * Splits a part into its root directory and the remainder.
     *
     * If the path has no root directory, an empty root directory will be
     * returned.
     *
     * If the root directory is a Windows style partition, the resulting root
     * will always contain a trailing slash.
     *
     * list ($root, $path) = Path::split("C:/webmozart")
     * // => array("C:/", "webmozart")
     *
     * list ($root, $path) = Path::split("C:")
     * // => array("C:/", "")
     *
     * @param string $path The canonical path to split.
     *
     * @return string[] An array with the root directory and the remaining
     *                  relative path.
     */
    private static function split($path)
    {
        if ('' === $path) {
            return array('', '');
        }

        // Remember scheme as part of the root, if any
        if (false !== ($pos = strpos($path, '://'))) {
            $root = substr($path, 0, $pos + 3);
            $path = substr($path, $pos + 3);
        } else {
            $root = '';
        }

        $length = strlen($path);

        // Remove and remember root directory
        if ('/' === $path[0]) {
            $root .= '/';
            $path = $length > 1 ? substr($path, 1) : '';
        } elseif ($length > 1 && ctype_alpha($path[0]) && ':' === $path[1]) {
            if (2 === $length) {
                // Windows special case: "C:"
                $root .= $path.'/';
                $path = '';
            } elseif ('/' === $path[2]) {
                // Windows normal case: "C:/"..
                $root .= substr($path, 0, 3);
                $path = $length > 3 ? substr($path, 3) : '';
            }
        }

        return array($root, $path);
    }

    /**
     * Converts string to lower-case (multi-byte safe if mbstring is installed).
     *
     * @param string $str The string
     *
     * @return string Lower case string
     */
    private static function toLower($str)
    {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($str, mb_detect_encoding($str));
        }

        return strtolower($str);
    }

    private function __construct()
    {
    }
}
