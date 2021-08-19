<?php
namespace Aws\S3;

use Aws\CacheInterface;
use Aws\LruArrayCache;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\CachingStream;
use Psr\Http\Message\StreamInterface;

/**
 * Amazon S3 stream wrapper to use "s3://<bucket>/<key>" files with PHP
 * streams, supporting "r", "w", "a", "x".
 *
 * # Opening "r" (read only) streams:
 *
 * Read only streams are truly streaming by default and will not allow you to
 * seek. This is because data read from the stream is not kept in memory or on
 * the local filesystem. You can force a "r" stream to be seekable by setting
 * the "seekable" stream context option true. This will allow true streaming of
 * data from Amazon S3, but will maintain a buffer of previously read bytes in
 * a 'php://temp' stream to allow seeking to previously read bytes from the
 * stream.
 *
 * You may pass any GetObject parameters as 's3' stream context options. These
 * options will affect how the data is downloaded from Amazon S3.
 *
 * # Opening "w" and "x" (write only) streams:
 *
 * Because Amazon S3 requires a Content-Length header, write only streams will
 * maintain a 'php://temp' stream to buffer data written to the stream until
 * the stream is flushed (usually by closing the stream with fclose).
 *
 * You may pass any PutObject parameters as 's3' stream context options. These
 * options will affect how the data is uploaded to Amazon S3.
 *
 * When opening an "x" stream, the file must exist on Amazon S3 for the stream
 * to open successfully.
 *
 * # Opening "a" (write only append) streams:
 *
 * Similar to "w" streams, opening append streams requires that the data be
 * buffered in a "php://temp" stream. Append streams will attempt to download
 * the contents of an object in Amazon S3, seek to the end of the object, then
 * allow you to append to the contents of the object. The data will then be
 * uploaded using a PutObject operation when the stream is flushed (usually
 * with fclose).
 *
 * You may pass any GetObject and/or PutObject parameters as 's3' stream
 * context options. These options will affect how the data is downloaded and
 * uploaded from Amazon S3.
 *
 * Stream context options:
 *
 * - "seekable": Set to true to create a seekable "r" (read only) stream by
 *   using a php://temp stream buffer
 * - For "unlink" only: Any option that can be passed to the DeleteObject
 *   operation
 */
class StreamWrapper
{
    /** @var resource|null Stream context (this is set by PHP) */
    public $context;

    /** @var StreamInterface Underlying stream resource */
    private $body;

    /** @var int Size of the body that is opened */
    private $size;

    /** @var array Hash of opened stream parameters */
    private $params = [];

    /** @var string Mode in which the stream was opened */
    private $mode;

    /** @var \Iterator Iterator used with opendir() related calls */
    private $objectIterator;

    /** @var string The bucket that was opened when opendir() was called */
    private $openedBucket;

    /** @var string The prefix of the bucket that was opened with opendir() */
    private $openedBucketPrefix;

    /** @var string Opened bucket path */
    private $openedPath;

    /** @var CacheInterface Cache for object and dir lookups */
    private $cache;

    /** @var string The opened protocol (e.g., "s3") */
    private $protocol = 's3';

    /** @var bool Keeps track of whether stream has been flushed since opening */
    private $isFlushed = false;

    /**
     * Register the 's3://' stream wrapper
     *
     * @param S3ClientInterface $client   Client to use with the stream wrapper
     * @param string            $protocol Protocol to register as.
     * @param CacheInterface    $cache    Default cache for the protocol.
     */
    public static function register(
        S3ClientInterface $client,
        $protocol = 's3',
        CacheInterface $cache = null
    ) {
        if (in_array($protocol, stream_get_wrappers())) {
            stream_wrapper_unregister($protocol);
        }

        // Set the client passed in as the default stream context client
        stream_wrapper_register($protocol, get_called_class(), STREAM_IS_URL);
        $default = stream_context_get_options(stream_context_get_default());
        $default[$protocol]['client'] = $client;

        if ($cache) {
            $default[$protocol]['cache'] = $cache;
        } elseif (!isset($default[$protocol]['cache'])) {
            // Set a default cache adapter.
            $default[$protocol]['cache'] = new LruArrayCache();
        }

        stream_context_set_default($default);
    }

    public function stream_close()
    {
        if ($this->body->getSize() === 0 && !($this->isFlushed)) {
            $this->stream_flush();
        }
        $this->body = $this->cache = null;
    }

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->initProtocol($path);
        $this->isFlushed = false;
        $this->params = $this->getBucketKey($path);
        $this->mode = rtrim($mode, 'bt');

        if ($errors = $this->validate($path, $this->mode)) {
            return $this->triggerError($errors);
        }

        return $this->boolCall(function() {
            switch ($this->mode) {
                case 'r': return $this->openReadStream();
                case 'a': return $this->openAppendStream();
                default: return $this->openWriteStream();
            }
        });
    }

    public function stream_eof()
    {
        return $this->body->eof();
    }

    public function stream_flush()
    {
        $this->isFlushed = true;
        if ($this->mode == 'r') {
            return false;
        }

        if ($this->body->isSeekable()) {
            $this->body->seek(0);
        }
        $params = $this->getOptions(true);
        $params['Body'] = $this->body;

        // Attempt to guess the ContentType of the upload based on the
        // file extension of the key
        if (!isset($params['ContentType']) &&
            ($type = Psr7\MimeType::fromFilename($params['Key']))
        ) {
            $params['ContentType'] = $type;
        }

        $this->clearCacheKey("{$this->protocol}://{$params['Bucket']}/{$params['Key']}");
        return $this->boolCall(function () use ($params) {
            return (bool) $this->getClient()->putObject($params);
        });
    }

    public function stream_read($count)
    {
        return $this->body->read($count);
    }

    public function stream_seek($offset, $whence = SEEK_SET)
    {
        return !$this->body->isSeekable()
            ? false
            : $this->boolCall(function () use ($offset, $whence) {
                $this->body->seek($offset, $whence);
                return true;
            });
    }

    public function stream_tell()
    {
        return $this->boolCall(function() { return $this->body->tell(); });
    }

    public function stream_write($data)
    {
        return $this->body->write($data);
    }

    public function unlink($path)
    {
        $this->initProtocol($path);

        return $this->boolCall(function () use ($path) {
            $this->clearCacheKey($path);
            $this->getClient()->deleteObject($this->withPath($path));
            return true;
        });
    }

    public function stream_stat()
    {
        $stat = $this->getStatTemplate();
        $stat[7] = $stat['size'] = $this->getSize();
        $stat[2] = $stat['mode'] = $this->mode;

        return $stat;
    }

    /**
     * Provides information for is_dir, is_file, filesize, etc. Works on
     * buckets, keys, and prefixes.
     * @link http://www.php.net/manual/en/streamwrapper.url-stat.php
     */
    public function url_stat($path, $flags)
    {
        $this->initProtocol($path);

        // Some paths come through as S3:// for some reason.
        $split = explode('://', $path);
        $path = strtolower($split[0]) . '://' . $split[1];

        // Check if this path is in the url_stat cache
        if ($value = $this->getCacheStorage()->get($path)) {
            return $value;
        }

        $stat = $this->createStat($path, $flags);

        if (is_array($stat)) {
            $this->getCacheStorage()->set($path, $stat);
        }

        return $stat;
    }

    /**
     * Parse the protocol out of the given path.
     *
     * @param $path
     */
    private function initProtocol($path)
    {
        $parts = explode('://', $path, 2);
        $this->protocol = $parts[0] ?: 's3';
    }

    private function createStat($path, $flags)
    {
        $this->initProtocol($path);
        $parts = $this->withPath($path);

        if (!$parts['Key']) {
            return $this->statDirectory($parts, $path, $flags);
        }

        return $this->boolCall(function () use ($parts, $path) {
            try {
                $result = $this->getClient()->headObject($parts);
                if (substr($parts['Key'], -1, 1) == '/' &&
                    $result['ContentLength'] == 0
                ) {
                    // Return as if it is a bucket to account for console
                    // bucket objects (e.g., zero-byte object "foo/")
                    return $this->formatUrlStat($path);
                }

                // Attempt to stat and cache regular object
                return $this->formatUrlStat($result->toArray());
            } catch (S3Exception $e) {
                // Maybe this isn't an actual key, but a prefix. Do a prefix
                // listing of objects to determine.
                $result = $this->getClient()->listObjects([
                    'Bucket'  => $parts['Bucket'],
                    'Prefix'  => rtrim($parts['Key'], '/') . '/',
                    'MaxKeys' => 1
                ]);
                if (!$result['Contents'] && !$result['CommonPrefixes']) {
                    throw new \Exception("File or directory not found: $path");
                }
                return $this->formatUrlStat($path);
            }
        }, $flags);
    }

    private function statDirectory($parts, $path, $flags)
    {
        // Stat "directories": buckets, or "s3://"
        if (!$parts['Bucket'] ||
            $this->getClient()->doesBucketExist($parts['Bucket'])
        ) {
            return $this->formatUrlStat($path);
        }

        return $this->triggerError("File or directory not found: $path", $flags);
    }

    /**
     * Support for mkdir().
     *
     * @param string $path    Directory which should be created.
     * @param int    $mode    Permissions. 700-range permissions map to
     *                        ACL_PUBLIC. 600-range permissions map to
     *                        ACL_AUTH_READ. All other permissions map to
     *                        ACL_PRIVATE. Expects octal form.
     * @param int    $options A bitwise mask of values, such as
     *                        STREAM_MKDIR_RECURSIVE.
     *
     * @return bool
     * @link http://www.php.net/manual/en/streamwrapper.mkdir.php
     */
    public function mkdir($path, $mode, $options)
    {
        $this->initProtocol($path);
        $params = $this->withPath($path);
        $this->clearCacheKey($path);
        if (!$params['Bucket']) {
            return false;
        }

        if (!isset($params['ACL'])) {
            $params['ACL'] = $this->determineAcl($mode);
        }

        return empty($params['Key'])
            ? $this->createBucket($path, $params)
            : $this->createSubfolder($path, $params);
    }

    public function rmdir($path, $options)
    {
        $this->initProtocol($path);
        $this->clearCacheKey($path);
        $params = $this->withPath($path);
        $client = $this->getClient();

        if (!$params['Bucket']) {
            return $this->triggerError('You must specify a bucket');
        }

        return $this->boolCall(function () use ($params, $path, $client) {
            if (!$params['Key']) {
                $client->deleteBucket(['Bucket' => $params['Bucket']]);
                return true;
            }
            return $this->deleteSubfolder($path, $params);
        });
    }

    /**
     * Support for opendir().
     *
     * The opendir() method of the Amazon S3 stream wrapper supports a stream
     * context option of "listFilter". listFilter must be a callable that
     * accepts an associative array of object data and returns true if the
     * object should be yielded when iterating the keys in a bucket.
     *
     * @param string $path    The path to the directory
     *                        (e.g. "s3://dir[</prefix>]")
     * @param string $options Unused option variable
     *
     * @return bool true on success
     * @see http://www.php.net/manual/en/function.opendir.php
     */
    public function dir_opendir($path, $options)
    {
        $this->initProtocol($path);
        $this->openedPath = $path;
        $params = $this->withPath($path);
        $delimiter = $this->getOption('delimiter');
        /** @var callable $filterFn */
        $filterFn = $this->getOption('listFilter');
        $op = ['Bucket' => $params['Bucket']];
        $this->openedBucket = $params['Bucket'];

        if ($delimiter === null) {
            $delimiter = '/';
        }

        if ($delimiter) {
            $op['Delimiter'] = $delimiter;
        }

        if ($params['Key']) {
            $params['Key'] = rtrim($params['Key'], $delimiter) . $delimiter;
            $op['Prefix'] = $params['Key'];
        }

        $this->openedBucketPrefix = $params['Key'];

        // Filter our "/" keys added by the console as directories, and ensure
        // that if a filter function is provided that it passes the filter.
        $this->objectIterator = \Aws\flatmap(
            $this->getClient()->getPaginator('ListObjects', $op),
            function (Result $result) use ($filterFn) {
                $contentsAndPrefixes = $result->search('[Contents[], CommonPrefixes[]][]');
                // Filter out dir place holder keys and use the filter fn.
                return array_filter(
                    $contentsAndPrefixes,
                    function ($key) use ($filterFn) {
                        return (!$filterFn || call_user_func($filterFn, $key))
                            && (!isset($key['Key']) || substr($key['Key'], -1, 1) !== '/');
                    }
                );
            }
        );

        return true;
    }

    /**
     * Close the directory listing handles
     *
     * @return bool true on success
     */
    public function dir_closedir()
    {
        $this->objectIterator = null;
        gc_collect_cycles();

        return true;
    }

    /**
     * This method is called in response to rewinddir()
     *
     * @return boolean true on success
     */
    public function dir_rewinddir()
    {
        return $this->boolCall(function() {
            $this->objectIterator = null;
            $this->dir_opendir($this->openedPath, null);
            return true;
        });
    }

    /**
     * This method is called in response to readdir()
     *
     * @return string Should return a string representing the next filename, or
     *                false if there is no next file.
     * @link http://www.php.net/manual/en/function.readdir.php
     */
    public function dir_readdir()
    {
        // Skip empty result keys
        if (!$this->objectIterator->valid()) {
            return false;
        }

        // First we need to create a cache key. This key is the full path to
        // then object in s3: protocol://bucket/key.
        // Next we need to create a result value. The result value is the
        // current value of the iterator without the opened bucket prefix to
        // emulate how readdir() works on directories.
        // The cache key and result value will depend on if this is a prefix
        // or a key.
        $cur = $this->objectIterator->current();
        if (isset($cur['Prefix'])) {
            // Include "directories". Be sure to strip a trailing "/"
            // on prefixes.
            $result = rtrim($cur['Prefix'], '/');
            $key = $this->formatKey($result);
            $stat = $this->formatUrlStat($key);
        } else {
            $result = $cur['Key'];
            $key = $this->formatKey($cur['Key']);
            $stat = $this->formatUrlStat($cur);
        }

        // Cache the object data for quick url_stat lookups used with
        // RecursiveDirectoryIterator.
        $this->getCacheStorage()->set($key, $stat);
        $this->objectIterator->next();

        // Remove the prefix from the result to emulate other stream wrappers.
        return $this->openedBucketPrefix
            ? substr($result, strlen($this->openedBucketPrefix))
            : $result;
    }

    private function formatKey($key)
    {
        $protocol = explode('://', $this->openedPath)[0];
        return "{$protocol}://{$this->openedBucket}/{$key}";
    }

    /**
     * Called in response to rename() to rename a file or directory. Currently
     * only supports renaming objects.
     *
     * @param string $path_from the path to the file to rename
     * @param string $path_to   the new path to the file
     *
     * @return bool true if file was successfully renamed
     * @link http://www.php.net/manual/en/function.rename.php
     */
    public function rename($path_from, $path_to)
    {
        // PHP will not allow rename across wrapper types, so we can safely
        // assume $path_from and $path_to have the same protocol
        $this->initProtocol($path_from);
        $partsFrom = $this->withPath($path_from);
        $partsTo = $this->withPath($path_to);
        $this->clearCacheKey($path_from);
        $this->clearCacheKey($path_to);

        if (!$partsFrom['Key'] || !$partsTo['Key']) {
            return $this->triggerError('The Amazon S3 stream wrapper only '
                . 'supports copying objects');
        }

        return $this->boolCall(function () use ($partsFrom, $partsTo) {
            $options = $this->getOptions(true);
            // Copy the object and allow overriding default parameters if
            // desired, but by default copy metadata
            $this->getClient()->copy(
                $partsFrom['Bucket'],
                $partsFrom['Key'],
                $partsTo['Bucket'],
                $partsTo['Key'],
                isset($options['acl']) ? $options['acl'] : 'private',
                $options
            );
            // Delete the original object
            $this->getClient()->deleteObject([
                'Bucket' => $partsFrom['Bucket'],
                'Key'    => $partsFrom['Key']
            ] + $options);
            return true;
        });
    }

    public function stream_cast($cast_as)
    {
        return false;
    }

    /**
     * Validates the provided stream arguments for fopen and returns an array
     * of errors.
     */
    private function validate($path, $mode)
    {
        $errors = [];

        if (!$this->getOption('Key')) {
            $errors[] = 'Cannot open a bucket. You must specify a path in the '
                . 'form of s3://bucket/key';
        }

        if (!in_array($mode, ['r', 'w', 'a', 'x'])) {
            $errors[] = "Mode not supported: {$mode}. "
                . "Use one 'r', 'w', 'a', or 'x'.";
        }

        // When using mode "x" validate if the file exists before attempting
        // to read
        if ($mode == 'x' &&
            $this->getClient()->doesObjectExist(
                $this->getOption('Bucket'),
                $this->getOption('Key'),
                $this->getOptions(true)
            )
        ) {
            $errors[] = "{$path} already exists on Amazon S3";
        }

        return $errors;
    }

    /**
     * Get the stream context options available to the current stream
     *
     * @param bool $removeContextData Set to true to remove contextual kvp's
     *                                like 'client' from the result.
     *
     * @return array
     */
    private function getOptions($removeContextData = false)
    {
        // Context is not set when doing things like stat
        if ($this->context === null) {
            $options = [];
        } else {
            $options = stream_context_get_options($this->context);
            $options = isset($options[$this->protocol])
                ? $options[$this->protocol]
                : [];
        }

        $default = stream_context_get_options(stream_context_get_default());
        $default = isset($default[$this->protocol])
            ? $default[$this->protocol]
            : [];
        $result = $this->params + $options + $default;

        if ($removeContextData) {
            unset($result['client'], $result['seekable'], $result['cache']);
        }

        return $result;
    }

    /**
     * Get a specific stream context option
     *
     * @param string $name Name of the option to retrieve
     *
     * @return mixed|null
     */
    private function getOption($name)
    {
        $options = $this->getOptions();

        return isset($options[$name]) ? $options[$name] : null;
    }

    /**
     * Gets the client from the stream context
     *
     * @return S3ClientInterface
     * @throws \RuntimeException if no client has been configured
     */
    private function getClient()
    {
        if (!$client = $this->getOption('client')) {
            throw new \RuntimeException('No client in stream context');
        }

        return $client;
    }

    private function getBucketKey($path)
    {
        // Remove the protocol
        $parts = explode('://', $path);
        // Get the bucket, key
        $parts = explode('/', $parts[1], 2);

        return [
            'Bucket' => $parts[0],
            'Key'    => isset($parts[1]) ? $parts[1] : null
        ];
    }

    /**
     * Get the bucket and key from the passed path (e.g. s3://bucket/key)
     *
     * @param string $path Path passed to the stream wrapper
     *
     * @return array Hash of 'Bucket', 'Key', and custom params from the context
     */
    private function withPath($path)
    {
        $params = $this->getOptions(true);

        return $this->getBucketKey($path) + $params;
    }

    private function openReadStream()
    {
        $client = $this->getClient();
        $command = $client->getCommand('GetObject', $this->getOptions(true));
        $command['@http']['stream'] = true;
        $result = $client->execute($command);
        $this->size = $result['ContentLength'];
        $this->body = $result['Body'];

        // Wrap the body in a caching entity body if seeking is allowed
        if ($this->getOption('seekable') && !$this->body->isSeekable()) {
            $this->body = new CachingStream($this->body);
        }

        return true;
    }

    private function openWriteStream()
    {
        $this->body = new Stream(fopen('php://temp', 'r+'));
        return true;
    }

    private function openAppendStream()
    {
        try {
            // Get the body of the object and seek to the end of the stream
            $client = $this->getClient();
            $this->body = $client->getObject($this->getOptions(true))['Body'];
            $this->body->seek(0, SEEK_END);
            return true;
        } catch (S3Exception $e) {
            // The object does not exist, so use a simple write stream
            return $this->openWriteStream();
        }
    }

    /**
     * Trigger one or more errors
     *
     * @param string|array $errors Errors to trigger
     * @param mixed        $flags  If set to STREAM_URL_STAT_QUIET, then no
     *                             error or exception occurs
     *
     * @return bool Returns false
     * @throws \RuntimeException if throw_errors is true
     */
    private function triggerError($errors, $flags = null)
    {
        // This is triggered with things like file_exists()
        if ($flags & STREAM_URL_STAT_QUIET) {
            return $flags & STREAM_URL_STAT_LINK
                // This is triggered for things like is_link()
                ? $this->formatUrlStat(false)
                : false;
        }

        // This is triggered when doing things like lstat() or stat()
        trigger_error(implode("\n", (array) $errors), E_USER_WARNING);

        return false;
    }

    /**
     * Prepare a url_stat result array
     *
     * @param string|array $result Data to add
     *
     * @return array Returns the modified url_stat result
     */
    private function formatUrlStat($result = null)
    {
        $stat = $this->getStatTemplate();
        switch (gettype($result)) {
            case 'NULL':
            case 'string':
                // Directory with 0777 access - see "man 2 stat".
                $stat['mode'] = $stat[2] = 0040777;
                break;
            case 'array':
                // Regular file with 0777 access - see "man 2 stat".
                $stat['mode'] = $stat[2] = 0100777;
                // Pluck the content-length if available.
                if (isset($result['ContentLength'])) {
                    $stat['size'] = $stat[7] = $result['ContentLength'];
                } elseif (isset($result['Size'])) {
                    $stat['size'] = $stat[7] = $result['Size'];
                }
                if (isset($result['LastModified'])) {
                    // ListObjects or HeadObject result
                    $stat['mtime'] = $stat[9] = $stat['ctime'] = $stat[10]
                        = strtotime($result['LastModified']);
                }
        }

        return $stat;
    }

    /**
     * Creates a bucket for the given parameters.
     *
     * @param string $path   Stream wrapper path
     * @param array  $params A result of StreamWrapper::withPath()
     *
     * @return bool Returns true on success or false on failure
     */
    private function createBucket($path, array $params)
    {
        if ($this->getClient()->doesBucketExist($params['Bucket'])) {
            return $this->triggerError("Bucket already exists: {$path}");
        }

        return $this->boolCall(function () use ($params, $path) {
            $this->getClient()->createBucket($params);
            $this->clearCacheKey($path);
            return true;
        });
    }

    /**
     * Creates a pseudo-folder by creating an empty "/" suffixed key
     *
     * @param string $path   Stream wrapper path
     * @param array  $params A result of StreamWrapper::withPath()
     *
     * @return bool
     */
    private function createSubfolder($path, array $params)
    {
        // Ensure the path ends in "/" and the body is empty.
        $params['Key'] = rtrim($params['Key'], '/') . '/';
        $params['Body'] = '';

        // Fail if this pseudo directory key already exists
        if ($this->getClient()->doesObjectExist(
            $params['Bucket'],
            $params['Key'])
        ) {
            return $this->triggerError("Subfolder already exists: {$path}");
        }

        return $this->boolCall(function () use ($params, $path) {
            $this->getClient()->putObject($params);
            $this->clearCacheKey($path);
            return true;
        });
    }

    /**
     * Deletes a nested subfolder if it is empty.
     *
     * @param string $path   Path that is being deleted (e.g., 's3://a/b/c')
     * @param array  $params A result of StreamWrapper::withPath()
     *
     * @return bool
     */
    private function deleteSubfolder($path, $params)
    {
        // Use a key that adds a trailing slash if needed.
        $prefix = rtrim($params['Key'], '/') . '/';
        $result = $this->getClient()->listObjects([
            'Bucket'  => $params['Bucket'],
            'Prefix'  => $prefix,
            'MaxKeys' => 1
        ]);

        // Check if the bucket contains keys other than the placeholder
        if ($contents = $result['Contents']) {
            return (count($contents) > 1 || $contents[0]['Key'] != $prefix)
                ? $this->triggerError('Subfolder is not empty')
                : $this->unlink(rtrim($path, '/') . '/');
        }

        return $result['CommonPrefixes']
            ? $this->triggerError('Subfolder contains nested folders')
            : true;
    }

    /**
     * Determine the most appropriate ACL based on a file mode.
     *
     * @param int $mode File mode
     *
     * @return string
     */
    private function determineAcl($mode)
    {
        switch (substr(decoct($mode), 0, 1)) {
            case '7': return 'public-read';
            case '6': return 'authenticated-read';
            default: return 'private';
        }
    }

    /**
     * Gets a URL stat template with default values
     *
     * @return array
     */
    private function getStatTemplate()
    {
        return [
            0  => 0,  'dev'     => 0,
            1  => 0,  'ino'     => 0,
            2  => 0,  'mode'    => 0,
            3  => 0,  'nlink'   => 0,
            4  => 0,  'uid'     => 0,
            5  => 0,  'gid'     => 0,
            6  => -1, 'rdev'    => -1,
            7  => 0,  'size'    => 0,
            8  => 0,  'atime'   => 0,
            9  => 0,  'mtime'   => 0,
            10 => 0,  'ctime'   => 0,
            11 => -1, 'blksize' => -1,
            12 => -1, 'blocks'  => -1,
        ];
    }

    /**
     * Invokes a callable and triggers an error if an exception occurs while
     * calling the function.
     *
     * @param callable $fn
     * @param int      $flags
     *
     * @return bool
     */
    private function boolCall(callable $fn, $flags = null)
    {
        try {
            return $fn();
        } catch (\Exception $e) {
            return $this->triggerError($e->getMessage(), $flags);
        }
    }

    /**
     * @return LruArrayCache
     */
    private function getCacheStorage()
    {
        if (!$this->cache) {
            $this->cache = $this->getOption('cache') ?: new LruArrayCache();
        }

        return $this->cache;
    }

    /**
     * Clears a specific stat cache value from the stat cache and LRU cache.
     *
     * @param string $key S3 path (s3://bucket/key).
     */
    private function clearCacheKey($key)
    {
        clearstatcache(true, $key);
        $this->getCacheStorage()->remove($key);
    }

    /**
     * Returns the size of the opened object body.
     *
     * @return int|null
     */
    private function getSize()
    {
        $size = $this->body->getSize();

        return !empty($size) ? $size : $this->size;
    }
}
