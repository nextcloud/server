<?php
/**
 * Basic autoloader for Hybridauth library which you may use as it is or as a template
 * to suit your application's environment.
 *
 * Note that you'd ONLY need this file if you are not using composer.
 */

if (version_compare(PHP_VERSION, '5.4.0', '<')) {
    throw new Exception('Hybridauth 3 requires PHP version 5.4 or higher.');
}

/**
 * Register the autoloader for Hybridauth classes.
 *
 * Based off the official PSR-4 autoloader example found at
 * http://www.php-fig.org/psr/psr-4/examples/
 *
 * @param string $class The fully-qualified class name.
 *
 * @return void
 */
spl_autoload_register(
    function ($class) {
        // project-specific namespace prefix. Will only kicks in for Hybridauth's namespace.
        $prefix = 'Hybridauth\\';

        // base directory for the namespace prefix.
        $base_dir = __DIR__;   // By default, it points to this same folder.
        // You may change this path if having trouble detecting the path to
        // the source files.

        // does the class use the namespace prefix?
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            // no, move to the next registered autoloader.
            return;
        }

        // get the relative class name.
        $relative_class = substr($class, $len);

        // replace the namespace prefix with the base directory, replace namespace
        // separators with directory separators in the relative class name, append
        // with .php
        $file = $base_dir . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';

        // if the file exists, require it
        if (file_exists($file)) {
            require $file;
        }
    }
);
