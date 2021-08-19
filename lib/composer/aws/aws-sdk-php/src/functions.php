<?php
namespace Aws;

use GuzzleHttp\Client;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\FulfilledPromise;

//-----------------------------------------------------------------------------
// Functional functions
//-----------------------------------------------------------------------------

/**
 * Returns a function that always returns the same value;
 *
 * @param mixed $value Value to return.
 *
 * @return callable
 */
function constantly($value)
{
    return function () use ($value) { return $value; };
}

/**
 * Filters values that do not satisfy the predicate function $pred.
 *
 * @param mixed    $iterable Iterable sequence of data.
 * @param callable $pred Function that accepts a value and returns true/false
 *
 * @return \Generator
 */
function filter($iterable, callable $pred)
{
    foreach ($iterable as $value) {
        if ($pred($value)) {
            yield $value;
        }
    }
}

/**
 * Applies a map function $f to each value in a collection.
 *
 * @param mixed    $iterable Iterable sequence of data.
 * @param callable $f        Map function to apply.
 *
 * @return \Generator
 */
function map($iterable, callable $f)
{
    foreach ($iterable as $value) {
        yield $f($value);
    }
}

/**
 * Creates a generator that iterates over a sequence, then iterates over each
 * value in the sequence and yields the application of the map function to each
 * value.
 *
 * @param mixed    $iterable Iterable sequence of data.
 * @param callable $f        Map function to apply.
 *
 * @return \Generator
 */
function flatmap($iterable, callable $f)
{
    foreach (map($iterable, $f) as $outer) {
        foreach ($outer as $inner) {
            yield $inner;
        }
    }
}

/**
 * Partitions the input sequence into partitions of the specified size.
 *
 * @param mixed    $iterable Iterable sequence of data.
 * @param int $size Size to make each partition (except possibly the last chunk)
 *
 * @return \Generator
 */
function partition($iterable, $size)
{
    $buffer = [];
    foreach ($iterable as $value) {
        $buffer[] = $value;
        if (count($buffer) === $size) {
            yield $buffer;
            $buffer = [];
        }
    }

    if ($buffer) {
        yield $buffer;
    }
}

/**
 * Returns a function that invokes the provided variadic functions one
 * after the other until one of the functions returns a non-null value.
 * The return function will call each passed function with any arguments it
 * is provided.
 *
 *     $a = function ($x, $y) { return null; };
 *     $b = function ($x, $y) { return $x + $y; };
 *     $fn = \Aws\or_chain($a, $b);
 *     echo $fn(1, 2); // 3
 *
 * @return callable
 */
function or_chain()
{
    $fns = func_get_args();
    return function () use ($fns) {
        $args = func_get_args();
        foreach ($fns as $fn) {
            $result = $args ? call_user_func_array($fn, $args) : $fn();
            if ($result) {
                return $result;
            }
        }
        return null;
    };
}

//-----------------------------------------------------------------------------
// JSON compiler and loading functions
//-----------------------------------------------------------------------------

/**
 * Loads a compiled JSON file from a PHP file.
 *
 * If the JSON file has not been cached to disk as a PHP file, it will be loaded
 * from the JSON source file and returned.
 *
 * @param string $path Path to the JSON file on disk
 *
 * @return mixed Returns the JSON decoded data. Note that JSON objects are
 *     decoded as associative arrays.
 */
function load_compiled_json($path)
{
    static $compiledList = [];

    $compiledFilepath = "{$path}.php";

    if (!isset($compiledList[$compiledFilepath])) {
        if (is_readable($compiledFilepath)) {
            $compiledList[$compiledFilepath] = include($compiledFilepath);
        }
    }

    if (isset($compiledList[$compiledFilepath])) {
        return $compiledList[$compiledFilepath];
    }

    if (!file_exists($path)) {
        throw new \InvalidArgumentException(
            sprintf("File not found: %s", $path)
        );
    }

    return json_decode(file_get_contents($path), true);
}

/**
 * No-op
 */
function clear_compiled_json()
{
    // pass
}

//-----------------------------------------------------------------------------
// Directory iterator functions.
//-----------------------------------------------------------------------------

/**
 * Iterates over the files in a directory and works with custom wrappers.
 *
 * @param string   $path Path to open (e.g., "s3://foo/bar").
 * @param resource $context Stream wrapper context.
 *
 * @return \Generator Yields relative filename strings.
 */
function dir_iterator($path, $context = null)
{
    $dh = $context ? opendir($path, $context) : opendir($path);
    if (!$dh) {
        throw new \InvalidArgumentException('File not found: ' . $path);
    }
    while (($file = readdir($dh)) !== false) {
        yield $file;
    }
    closedir($dh);
}

/**
 * Returns a recursive directory iterator that yields absolute filenames.
 *
 * This iterator is not broken like PHP's built-in DirectoryIterator (which
 * will read the first file from a stream wrapper, then rewind, then read
 * it again).
 *
 * @param string   $path    Path to traverse (e.g., s3://bucket/key, /tmp)
 * @param resource $context Stream context options.
 *
 * @return \Generator Yields absolute filenames.
 */
function recursive_dir_iterator($path, $context = null)
{
    $invalid = ['.' => true, '..' => true];
    $pathLen = strlen($path) + 1;
    $iterator = dir_iterator($path, $context);
    $queue = [];
    do {
        while ($iterator->valid()) {
            $file = $iterator->current();
            $iterator->next();
            if (isset($invalid[basename($file)])) {
                continue;
            }
            $fullPath = "{$path}/{$file}";
            yield $fullPath;
            if (is_dir($fullPath)) {
                $queue[] = $iterator;
                $iterator = map(
                    dir_iterator($fullPath, $context),
                    function ($file) use ($fullPath, $pathLen) {
                        return substr("{$fullPath}/{$file}", $pathLen);
                    }
                );
                continue;
            }
        }
        $iterator = array_pop($queue);
    } while ($iterator);
}

//-----------------------------------------------------------------------------
// Misc. functions.
//-----------------------------------------------------------------------------

/**
 * Debug function used to describe the provided value type and class.
 *
 * @param mixed $input
 *
 * @return string Returns a string containing the type of the variable and
 *                if a class is provided, the class name.
 */
function describe_type($input)
{
    switch (gettype($input)) {
        case 'object':
            return 'object(' . get_class($input) . ')';
        case 'array':
            return 'array(' . count($input) . ')';
        default:
            ob_start();
            var_dump($input);
            // normalize float vs double
            return str_replace('double(', 'float(', rtrim(ob_get_clean()));
    }
}

/**
 * Creates a default HTTP handler based on the available clients.
 *
 * @return callable
 */
function default_http_handler()
{
    $version = guzzle_major_version();
    // If Guzzle 6 or 7 installed
    if ($version === 6 || $version === 7) {
        return new \Aws\Handler\GuzzleV6\GuzzleHandler();
    }

    // If Guzzle 5 installed
    if ($version === 5) {
        return new \Aws\Handler\GuzzleV5\GuzzleHandler();
    }

    throw new \RuntimeException('Unknown Guzzle version: ' . $version);
}

/**
 * Gets the default user agent string depending on the Guzzle version
 *
 * @return string
 */
function default_user_agent()
{
    $version = guzzle_major_version();
    // If Guzzle 6 or 7 installed
    if ($version === 6 || $version === 7) {
        return \GuzzleHttp\default_user_agent();
    }

    // If Guzzle 5 installed
    if ($version === 5) {
        return \GuzzleHttp\Client::getDefaultUserAgent();
    }

    throw new \RuntimeException('Unknown Guzzle version: ' . $version);
}

/**
 * Get the major version of guzzle that is installed.
 *
 * @internal This function is internal and should not be used outside aws/aws-sdk-php.
 * @return int
 * @throws \RuntimeException
 */
function guzzle_major_version()
{
    static $cache = null;
    if (null !== $cache) {
        return $cache;
    }

    if (defined('\GuzzleHttp\ClientInterface::VERSION')) {
        $version = (string) ClientInterface::VERSION;
        if ($version[0] === '6') {
            return $cache = 6;
        }
        if ($version[0] === '5') {
            return $cache = 5;
        }
    } elseif (defined('\GuzzleHttp\ClientInterface::MAJOR_VERSION')) {
        return $cache = ClientInterface::MAJOR_VERSION;
    }

    throw new \RuntimeException('Unable to determine what Guzzle version is installed.');
}

/**
 * Serialize a request for a command but do not send it.
 *
 * Returns a promise that is fulfilled with the serialized request.
 *
 * @param CommandInterface $command Command to serialize.
 *
 * @return RequestInterface
 * @throws \RuntimeException
 */
function serialize(CommandInterface $command)
{
    $request = null;
    $handlerList = $command->getHandlerList();

    // Return a mock result.
    $handlerList->setHandler(
        function (CommandInterface $_, RequestInterface $r) use (&$request) {
            $request = $r;
            return new FulfilledPromise(new Result([]));
        }
    );

    call_user_func($handlerList->resolve(), $command)->wait();
    if (!$request instanceof RequestInterface) {
        throw new \RuntimeException(
            'Calling handler did not serialize request'
        );
    }

    return $request;
}

/**
 * Retrieves data for a service from the SDK's service manifest file.
 *
 * Manifest data is stored statically, so it does not need to be loaded more
 * than once per process. The JSON data is also cached in opcache.
 *
 * @param string $service Case-insensitive namespace or endpoint prefix of the
 *                        service for which you are retrieving manifest data.
 *
 * @return array
 * @throws \InvalidArgumentException if the service is not supported.
 */
function manifest($service = null)
{
    // Load the manifest and create aliases for lowercased namespaces
    static $manifest = [];
    static $aliases = [];
    if (empty($manifest)) {
        $manifest = load_compiled_json(__DIR__ . '/data/manifest.json');
        foreach ($manifest as $endpoint => $info) {
            $alias = strtolower($info['namespace']);
            if ($alias !== $endpoint) {
                $aliases[$alias] = $endpoint;
            }
        }
    }

    // If no service specified, then return the whole manifest.
    if ($service === null) {
        return $manifest;
    }

    // Look up the service's info in the manifest data.
    $service = strtolower($service);
    if (isset($manifest[$service])) {
        return $manifest[$service] + ['endpoint' => $service];
    }

    if (isset($aliases[$service])) {
        return manifest($aliases[$service]);
    }

    throw new \InvalidArgumentException(
        "The service \"{$service}\" is not provided by the AWS SDK for PHP."
    );
}

/**
 * Checks if supplied parameter is a valid hostname
 *
 * @param string $hostname
 * @return bool
 */
function is_valid_hostname($hostname)
{
    return (
        preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*\.?$/i", $hostname)
        && preg_match("/^.{1,253}$/", $hostname)
        && preg_match("/^[^\.]{1,63}(\.[^\.]{0,63})*$/", $hostname)
    );
}

/**
 * Checks if supplied parameter is a valid host label
 *
 * @param $label
 * @return bool
 */
function is_valid_hostlabel($label)
{
    return preg_match("/^(?!-)[a-zA-Z0-9-]{1,63}(?<!-)$/", $label);
}

/**
 * Ignores '#' full line comments, which parse_ini_file no longer does
 * in PHP 7+.
 *
 * @param $filename
 * @param bool $process_sections
 * @param int $scanner_mode
 * @return array|bool
 */
function parse_ini_file(
    $filename,
    $process_sections = false,
    $scanner_mode = INI_SCANNER_NORMAL)
{
    return parse_ini_string(
        preg_replace('/^#.*\\n/m', "", file_get_contents($filename)),
        $process_sections,
        $scanner_mode
    );
}

/**
 * Outputs boolean value of input for a select range of possible values,
 * null otherwise
 *
 * @param $input
 * @return bool|null
 */
function boolean_value($input)
{
    if (is_bool($input)) {
        return $input;
    }

    if ($input === 0) {
        return false;
    }

    if ($input === 1) {
        return true;
    }

    if (is_string($input)) {
        switch (strtolower($input)) {
            case "true":
            case "on":
            case "1":
                return true;
                break;

            case "false":
            case "off":
            case "0":
                return false;
                break;
        }
    }
    return null;
}

/**
 * Checks if an input is a valid epoch time
 *
 * @param $input
 * @return bool
 */
function is_valid_epoch($input)
{
    if (is_string($input) || is_numeric($input)) {
        if (is_string($input) && !preg_match("/^-?[0-9]+\.?[0-9]*$/", $input)) {
            return false;
        }
        return true;
    }
    return false;
}
