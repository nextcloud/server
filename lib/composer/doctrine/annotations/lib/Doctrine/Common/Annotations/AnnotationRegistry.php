<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Common\Annotations;

final class AnnotationRegistry
{
    /**
     * A map of namespaces to use for autoloading purposes based on a PSR-0 convention.
     *
     * Contains the namespace as key and an array of directories as value. If the value is NULL
     * the include path is used for checking for the corresponding file.
     *
     * This autoloading mechanism does not utilize the PHP autoloading but implements autoloading on its own.
     *
     * @var string[][]|string[]|null[]
     */
    static private $autoloadNamespaces = [];

    /**
     * A map of autoloader callables.
     *
     * @var callable[]
     */
    static private $loaders = [];

    /**
     * An array of classes which cannot be found
     *
     * @var null[] indexed by class name
     */
    static private $failedToAutoload = [];

    /**
     * Whenever registerFile() was used. Disables use of standard autoloader.
     *
     * @var bool
     */
    static private $registerFileUsed = false;

    public static function reset() : void
    {
        self::$autoloadNamespaces = [];
        self::$loaders            = [];
        self::$failedToAutoload   = [];
        self::$registerFileUsed   = false;
    }

    /**
     * Registers file.
     *
     * @deprecated This method is deprecated and will be removed in doctrine/annotations 2.0. Annotations will be autoloaded in 2.0.
     */
    public static function registerFile(string $file) : void
    {
        self::$registerFileUsed = true;

        require_once $file;
    }

    /**
     * Adds a namespace with one or many directories to look for files or null for the include path.
     *
     * Loading of this namespaces will be done with a PSR-0 namespace loading algorithm.
     *
     * @param string            $namespace
     * @param string|array|null $dirs
     *
     * @deprecated This method is deprecated and will be removed in doctrine/annotations 2.0. Annotations will be autoloaded in 2.0.
     */
    public static function registerAutoloadNamespace(string $namespace, $dirs = null) : void
    {
        self::$autoloadNamespaces[$namespace] = $dirs;
    }

    /**
     * Registers multiple namespaces.
     *
     * Loading of this namespaces will be done with a PSR-0 namespace loading algorithm.
     *
     * @param string[][]|string[]|null[] $namespaces indexed by namespace name
     *
     * @deprecated This method is deprecated and will be removed in doctrine/annotations 2.0. Annotations will be autoloaded in 2.0.
     */
    public static function registerAutoloadNamespaces(array $namespaces) : void
    {
        self::$autoloadNamespaces = \array_merge(self::$autoloadNamespaces, $namespaces);
    }

    /**
     * Registers an autoloading callable for annotations, much like spl_autoload_register().
     *
     * NOTE: These class loaders HAVE to be silent when a class was not found!
     * IMPORTANT: Loaders have to return true if they loaded a class that could contain the searched annotation class.
     *
     * @deprecated This method is deprecated and will be removed in doctrine/annotations 2.0. Annotations will be autoloaded in 2.0.
     */
    public static function registerLoader(callable $callable) : void
    {
        // Reset our static cache now that we have a new loader to work with
        self::$failedToAutoload   = [];
        self::$loaders[]          = $callable;
    }

    /**
     * Registers an autoloading callable for annotations, if it is not already registered
     *
     * @deprecated This method is deprecated and will be removed in doctrine/annotations 2.0. Annotations will be autoloaded in 2.0.
     */
    public static function registerUniqueLoader(callable $callable) : void
    {
        if ( ! in_array($callable, self::$loaders, true) ) {
            self::registerLoader($callable);
        }
    }

    /**
     * Autoloads an annotation class silently.
     */
    public static function loadAnnotationClass(string $class) : bool
    {
        if (\class_exists($class, false)) {
            return true;
        }

        if (\array_key_exists($class, self::$failedToAutoload)) {
            return false;
        }

        foreach (self::$autoloadNamespaces AS $namespace => $dirs) {
            if (\strpos($class, $namespace) === 0) {
                $file = \str_replace('\\', \DIRECTORY_SEPARATOR, $class) . '.php';

                if ($dirs === null) {
                    if ($path = stream_resolve_include_path($file)) {
                        require $path;
                        return true;
                    }
                } else {
                    foreach((array) $dirs AS $dir) {
                        if (is_file($dir . \DIRECTORY_SEPARATOR . $file)) {
                            require $dir . \DIRECTORY_SEPARATOR . $file;
                            return true;
                        }
                    }
                }
            }
        }

        foreach (self::$loaders AS $loader) {
            if ($loader($class) === true) {
                return true;
            }
        }

        if (self::$loaders === [] && self::$autoloadNamespaces === [] && self::$registerFileUsed === false && \class_exists($class)) {
            return true;
        }

        self::$failedToAutoload[$class] = null;

        return false;
    }
}
