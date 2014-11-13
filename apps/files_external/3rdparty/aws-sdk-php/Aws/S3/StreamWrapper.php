<?php
/**
 * Copyright 2010-2013 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

namespace Aws\S3;

use Aws\Common\Exception\RuntimeException;
use Aws\S3\Exception\S3Exception;
use Aws\S3\Exception\NoSuchKeyException;
use Aws\S3\Iterator\ListObjectsIterator;
use Guzzle\Http\EntityBody;
use Guzzle\Http\CachingEntityBody;
use Guzzle\Http\Mimetypes;
use Guzzle\Iterator\FilterIterator;
use Guzzle\Stream\PhpStreamRequestFactory;
use Guzzle\Service\Command\CommandInterface;

/**
 * Amazon S3 stream wrapper to use "s3://<bucket>/<key>" files with PHP streams, supporting "r", "w", "a", "x".
 *
 * # Supported stream related PHP functions:
 * - fopen, fclose, fread, fwrite, fseek, ftell, feof, fflush
 * - opendir, closedir, readdir, rewinddir
 * - copy, rename, unlink
 * - mkdir, rmdir, rmdir (recursive)
 * - file_get_contents, file_put_contents
 * - file_exists, filesize, is_file, is_dir
 *
 * # Opening "r" (read only) streams:
 *
 * Read only streams are truly streaming by default and will not allow you to seek. This is because data
 * read from the stream is not kept in memory or on the local filesystem. You can force a "r" stream to be seekable
 * by setting the "seekable" stream context option true. This will allow true streaming of data from Amazon S3, but
 * will maintain a buffer of previously read bytes in a 'php://temp' stream to allow seeking to previously read bytes
 * from the stream.
 *
 * You may pass any GetObject parameters as 's3' stream context options. These options will affect how the data is
 * downloaded from Amazon S3.
 *
 * # Opening "w" and "x" (write only) streams:
 *
 * Because Amazon S3 requires a Content-Length header, write only streams will maintain a 'php://temp' stream to buffer
 * data written to the stream until the stream is flushed (usually by closing the stream with fclose).
 *
 * You may pass any PutObject parameters as 's3' stream context options. These options will affect how the data is
 * uploaded to Amazon S3.
 *
 * When opening an "x" stream, the file must exist on Amazon S3 for the stream to open successfully.
 *
 * # Opening "a" (write only append) streams:
 *
 * Similar to "w" streams, opening append streams requires that the data be buffered in a "php://temp" stream. Append
 * streams will attempt to download the contents of an object in Amazon S3, seek to the end of the object, then allow
 * you to append to the contents of the object. The data will then be uploaded using a PutObject operation when the
 * stream is flushed (usually with fclose).
 *
 * You may pass any GetObject and/or PutObject parameters as 's3' stream context options. These options will affect how
 * the data is downloaded and uploaded from Amazon S3.
 *
 * Stream context options:
 *
 * - "seekable": Set to true to create a seekable "r" (read only) stream by using a php://temp stream buffer
 * - For "unlink" only: Any option that can be passed to the DeleteObject operation
 */
class StreamWrapper
{
    /**
     * @var resource|null Stream context (this is set by PHP when a context is used)
     */
    public $context;

    /**
     * @var S3Client Client used to send requests
     */
    protected static $client;

    /**
     * @var string Mode the stream was opened with
     */
    protected $mode;

    /**
     * @var EntityBody Underlying stream resource
     */
    protected $body;

    /**
     * @var array Current parameters to use with the flush operation
     */
    protected $params;

    /**
     * @var ListObjectsIterator Iterator used with opendir() and subsequent readdir() calls
     */
    protected $objectIterator;

    /**
     * @var string The bucket that was opened when opendir() was called
     */
    protected $openedBucket;

    /**
     * @var string The prefix of the bucket that was opened with opendir()
     */
    protected $openedBucketPrefix;

    /**
     * @var array The next key to retrieve when using a directory iterator. Helps for fast directory traversal.
     */
    protected static $nextStat = array();

    /**
     * Register the 's3://' stream wrapper
     *
     * @param S3Client $client Client to use with the stream wrapper
     */
    public static function register(S3Client $client)
    {
        if (in_array('s3', stream_get_wrappers())) {
            stream_wrapper_unregister('s3');
        }

        stream_wrapper_register('s3', get_called_class(), STREAM_IS_URL);
        static::$client = $client;
    }

    /**
     * Close the stream
     */
    public function stream_close()
    {
        $this->body = null;
    }

    /**
     * @param string $path
     * @param string $mode
     * @param int    $options
     * @param string $opened_path
     *
     * @return bool
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        // We don't care about the binary flag
        $this->mode = $mode = rtrim($mode, 'bt');
        $this->params = $params = $this->getParams($path);
        $errors = array();

        if (!$params['Key']) {
            $errors[] = 'Cannot open a bucket. You must specify a path in the form of s3://bucket/key';
        }

        if (strpos($mode, '+')) {
            $errors[] = 'The Amazon S3 stream wrapper does not allow simultaneous reading and writing.';
        }

        if (!in_array($mode, array('r', 'w', 'a', 'x'))) {
            $errors[] = "Mode not supported: {$mode}. Use one 'r', 'w', 'a', or 'x'.";
        }

        // When using mode "x" validate if the file exists before attempting to read
        if ($mode == 'x' && static::$client->doesObjectExist($params['Bucket'], $params['Key'], $this->getOptions())) {
            $errors[] = "{$path} already exists on Amazon S3";
        }

        if (!$errors) {
            if ($mode == 'r') {
                $this->openReadStream($params, $errors);
            } elseif ($mode == 'a') {
                $this->openAppendStream($params, $errors);
            } else {
                $this->openWriteStream($params, $errors);
            }
        }

        return $errors ? $this->triggerError($errors) : true;
    }

    /**
     * @return bool
     */
    public function stream_eof()
    {
        return $this->body->feof();
    }

    /**
     * @return bool
     */
    public function stream_flush()
    {
        if ($this->mode == 'r') {
            return false;
        }

        $this->body->rewind();
        $params = $this->params;
        $params['Body'] = $this->body;

        // Attempt to guess the ContentType of the upload based on the
        // file extension of the key
        if (!isset($params['ContentType']) &&
            ($type = Mimetypes::getInstance()->fromFilename($params['Key']))
        ) {
            $params['ContentType'] = $type;
        }

        try {
            static::$client->putObject($params);
            return true;
        } catch (\Exception $e) {
            return $this->triggerError($e->getMessage());
        }
    }

    /**
     * Read data from the underlying stream
     *
     * @param int $count Amount of bytes to read
     *
     * @return string
     */
    public function stream_read($count)
    {
        return $this->body->read($count);
    }

    /**
     * Seek to a specific byte in the stream
     *
     * @param int $offset Seek offset
     * @param int $whence Whence (SEEK_SET, SEEK_CUR, SEEK_END)
     *
     * @return bool
     */
    public function stream_seek($offset, $whence = SEEK_SET)
    {
        return $this->body->seek($offset, $whence);
    }

    /**
     * Get the current position of the stream
     *
     * @return int Returns the current position in the stream
     */
    public function stream_tell()
    {
        return $this->body->ftell();
    }

    /**
     * Write data the to the stream
     *
     * @param string $data
     *
     * @return int Returns the number of bytes written to the stream
     */
    public function stream_write($data)
    {
        return $this->body->write($data);
    }

    /**
     * Delete a specific object
     *
     * @param string $path
     * @return bool
     */
    public function unlink($path)
    {
        try {
            $this->clearStatInfo($path);
            static::$client->deleteObject($this->getParams($path));
            return true;
        } catch (\Exception $e) {
            return $this->triggerError($e->getMessage());
        }
    }

    /**
     * @return array
     */
    public function stream_stat()
    {
        $stat = fstat($this->body->getStream());
        // Add the size of the underlying stream if it is known
        if ($this->mode == 'r' && $this->body->getSize()) {
            $stat[7] = $stat['size'] = $this->body->getSize();
        }

        return $stat;
    }

    /**
     * Provides information for is_dir, is_file, filesize, etc. Works on buckets, keys, and prefixes
     *
     * @param string $path
     * @param int    $flags
     *
     * @return array Returns an array of stat data
     * @link http://www.php.net/manual/en/streamwrapper.url-stat.php
     */
    public function url_stat($path, $flags)
    {
        // Check if this path is in the url_stat cache
        if (isset(static::$nextStat[$path])) {
            return static::$nextStat[$path];
        }

        $parts = $this->getParams($path);

        if (!$parts['Key']) {
            // Stat "directories": buckets, or "s3://"
            if (!$parts['Bucket'] || static::$client->doesBucketExist($parts['Bucket'])) {
                return $this->formatUrlStat($path);
            } else {
                return $this->triggerError("File or directory not found: {$path}", $flags);
            }
        }

        try {
            try {
                $result = static::$client->headObject($parts)->toArray();
                if (substr($parts['Key'], -1, 1) == '/' && $result['ContentLength'] == 0) {
                    // Return as if it is a bucket to account for console bucket objects (e.g., zero-byte object "foo/")
                    return $this->formatUrlStat($path);
                } else {
                    // Attempt to stat and cache regular object
                    return $this->formatUrlStat($result);
                }
            } catch (NoSuchKeyException $e) {
                // Maybe this isn't an actual key, but a prefix. Do a prefix listing of objects to determine.
                $result = static::$client->listObjects(array(
                    'Bucket'  => $parts['Bucket'],
                    'Prefix'  => rtrim($parts['Key'], '/') . '/',
                    'MaxKeys' => 1
                ));
                if (!$result['Contents'] && !$result['CommonPrefixes']) {
                    return $this->triggerError("File or directory not found: {$path}", $flags);
                }
                // This is a directory prefix
                return $this->formatUrlStat($path);
            }
        } catch (\Exception $e) {
            return $this->triggerError($e->getMessage(), $flags);
        }
    }

    /**
     * Support for mkdir().
     *
     * @param string $path    Directory which should be created.
     * @param int    $mode    Permissions. 700-range permissions map to ACL_PUBLIC. 600-range permissions map to
     *                        ACL_AUTH_READ. All other permissions map to ACL_PRIVATE. Expects octal form.
     * @param int    $options A bitwise mask of values, such as STREAM_MKDIR_RECURSIVE.
     *
     * @return bool
     * @link http://www.php.net/manual/en/streamwrapper.mkdir.php
     */
    public function mkdir($path, $mode, $options)
    {
        $params = $this->getParams($path);
        if (!$params['Bucket']) {
            return false;
        }

        if (!isset($params['ACL'])) {
            $params['ACL'] = $this->determineAcl($mode);
        }

        return !isset($params['Key']) || $params['Key'] === '/'
            ? $this->createBucket($path, $params)
            : $this->createPseudoDirectory($path, $params);
    }

    /**
     * Remove a bucket from Amazon S3
     *
     * @param string $path the directory path
     *
     * @return bool true if directory was successfully removed
     * @link http://www.php.net/manual/en/streamwrapper.rmdir.php
     */
    public function rmdir($path)
    {
        $params = $this->getParams($path);
        if (!$params['Bucket']) {
            return $this->triggerError('You cannot delete s3://. Please specify a bucket.');
        }

        try {

            if (!$params['Key']) {
                static::$client->deleteBucket(array('Bucket' => $params['Bucket']));
                $this->clearStatInfo($path);
                return true;
            }

            // Use a key that adds a trailing slash if needed.
            $prefix = rtrim($params['Key'], '/') . '/';

            $result = static::$client->listObjects(array(
                'Bucket'  => $params['Bucket'],
                'Prefix'  => $prefix,
                'MaxKeys' => 1
            ));

            // Check if the bucket contains keys other than the placeholder
            if ($result['Contents']) {
                foreach ($result['Contents'] as $key) {
                    if ($key['Key'] == $prefix) {
                        continue;
                    }
                    return $this->triggerError('Psuedo folder is not empty');
                }
                return $this->unlink(rtrim($path, '/') . '/');
            }

            return $result['CommonPrefixes']
                ? $this->triggerError('Pseudo folder contains nested folders')
                : true;

        } catch (\Exception $e) {
            return $this->triggerError($e->getMessage());
        }
    }

    /**
     * Support for opendir().
     *
     * The opendir() method of the Amazon S3 stream wrapper supports a stream
     * context option of "listFilter". listFilter must be a callable that
     * accepts an associative array of object data and returns true if the
     * object should be yielded when iterating the keys in a bucket.
     *
     * @param string $path    The path to the directory (e.g. "s3://dir[</prefix>]")
     * @param string $options Whether or not to enforce safe_mode (0x04). Unused.
     *
     * @return bool true on success
     * @see http://www.php.net/manual/en/function.opendir.php
     */
    public function dir_opendir($path, $options)
    {
        // Reset the cache
        $this->clearStatInfo();
        $params = $this->getParams($path);
        $delimiter = $this->getOption('delimiter');
        $filterFn = $this->getOption('listFilter');

        if ($delimiter === null) {
            $delimiter = '/';
        }

        if ($params['Key']) {
            $params['Key'] = rtrim($params['Key'], $delimiter) . $delimiter;
        }

        $this->openedBucket = $params['Bucket'];
        $this->openedBucketPrefix = $params['Key'];
        $operationParams = array('Bucket' => $params['Bucket'], 'Prefix' => $params['Key']);

        if ($delimiter) {
            $operationParams['Delimiter'] = $delimiter;
        }

        $objectIterator = static::$client->getIterator('ListObjects', $operationParams, array(
            'return_prefixes' => true,
            'sort_results'    => true
        ));

        // Filter our "/" keys added by the console as directories, and ensure
        // that if a filter function is provided that it passes the filter.
        $this->objectIterator = new FilterIterator(
            $objectIterator,
            function ($key) use ($filterFn) {
                // Each yielded results can contain a "Key" or "Prefix"
                return (!$filterFn || call_user_func($filterFn, $key)) &&
                    (!isset($key['Key']) || substr($key['Key'], -1, 1) !== '/');
            }
        );

        $this->objectIterator->next();

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

        return true;
    }

    /**
     * This method is called in response to rewinddir()
     *
     * @return boolean true on success
     */
    public function dir_rewinddir()
    {
        $this->clearStatInfo();
        $this->objectIterator->rewind();

        return true;
    }

    /**
     * This method is called in response to readdir()
     *
     * @return string Should return a string representing the next filename, or false if there is no next file.
     *
     * @link http://www.php.net/manual/en/function.readdir.php
     */
    public function dir_readdir()
    {
        // Skip empty result keys
        if (!$this->objectIterator->valid()) {
            return false;
        }

        $current = $this->objectIterator->current();
        if (isset($current['Prefix'])) {
            // Include "directories". Be sure to strip a trailing "/"
            // on prefixes.
            $prefix = rtrim($current['Prefix'], '/');
            $result = str_replace($this->openedBucketPrefix, '', $prefix);
            $key = "s3://{$this->openedBucket}/{$prefix}";
            $stat = $this->formatUrlStat($prefix);
        } else {
            // Remove the prefix from the result to emulate other
            // stream wrappers.
            $result = str_replace($this->openedBucketPrefix, '', $current['Key']);
            $key = "s3://{$this->openedBucket}/{$current['Key']}";
            $stat = $this->formatUrlStat($current);
        }

        // Cache the object data for quick url_stat lookups used with
        // RecursiveDirectoryIterator.
        static::$nextStat = array($key => $stat);
        $this->objectIterator->next();

        return $result;
    }

    /**
     * Called in response to rename() to rename a file or directory. Currently only supports renaming objects.
     *
     * @param string $path_from the path to the file to rename
     * @param string $path_to   the new path to the file
     *
     * @return bool true if file was successfully renamed
     * @link http://www.php.net/manual/en/function.rename.php
     */
    public function rename($path_from, $path_to)
    {
        $partsFrom = $this->getParams($path_from);
        $partsTo = $this->getParams($path_to);
        $this->clearStatInfo($path_from);
        $this->clearStatInfo($path_to);

        if (!$partsFrom['Key'] || !$partsTo['Key']) {
            return $this->triggerError('The Amazon S3 stream wrapper only supports copying objects');
        }

        try {
            // Copy the object and allow overriding default parameters if desired, but by default copy metadata
            static::$client->copyObject($this->getOptions() + array(
                'Bucket' => $partsTo['Bucket'],
                'Key' => $partsTo['Key'],
                'CopySource' => '/' . $partsFrom['Bucket'] . '/' . rawurlencode($partsFrom['Key']),
                'MetadataDirective' => 'COPY'
            ));
            // Delete the original object
            static::$client->deleteObject(array(
                'Bucket' => $partsFrom['Bucket'],
                'Key'    => $partsFrom['Key']
            ) + $this->getOptions());
        } catch (\Exception $e) {
            return $this->triggerError($e->getMessage());
        }

        return true;
    }

    /**
     * Cast the stream to return the underlying file resource
     *
     * @param int $cast_as STREAM_CAST_FOR_SELECT or STREAM_CAST_AS_STREAM
     *
     * @return resource
     */
    public function stream_cast($cast_as)
    {
        return $this->body->getStream();
    }

    /**
     * Get the stream context options available to the current stream
     *
     * @return array
     */
    protected function getOptions()
    {
        $context = $this->context ?: stream_context_get_default();
        $options = stream_context_get_options($context);

        return isset($options['s3']) ? $options['s3'] : array();
    }

    /**
     * Get a specific stream context option
     *
     * @param string $name Name of the option to retrieve
     *
     * @return mixed|null
     */
    protected function getOption($name)
    {
        $options = $this->getOptions();

        return isset($options[$name]) ? $options[$name] : null;
    }

    /**
     * Get the bucket and key from the passed path (e.g. s3://bucket/key)
     *
     * @param string $path Path passed to the stream wrapper
     *
     * @return array Hash of 'Bucket', 'Key', and custom params
     */
    protected function getParams($path)
    {
        $parts = explode('/', substr($path, 5), 2);

        $params = $this->getOptions();
        unset($params['seekable']);

        return array(
            'Bucket' => $parts[0],
            'Key'    => isset($parts[1]) ? $parts[1] : null
        ) + $params;
    }

    /**
     * Serialize and sign a command, returning a request object
     *
     * @param CommandInterface $command Command to sign
     *
     * @return RequestInterface
     */
    protected function getSignedRequest($command)
    {
        $request = $command->prepare();
        $request->dispatch('request.before_send', array('request' => $request));

        return $request;
    }

    /**
     * Initialize the stream wrapper for a read only stream
     *
     * @param array $params Operation parameters
     * @param array $errors Any encountered errors to append to
     *
     * @return bool
     */
    protected function openReadStream(array $params, array &$errors)
    {
        // Create the command and serialize the request
        $request = $this->getSignedRequest(static::$client->getCommand('GetObject', $params));
        // Create a stream that uses the EntityBody object
        $factory = $this->getOption('stream_factory') ?: new PhpStreamRequestFactory();
        $this->body = $factory->fromRequest($request, array(), array('stream_class' => 'Guzzle\Http\EntityBody'));

        // Wrap the body in a caching entity body if seeking is allowed
        if ($this->getOption('seekable')) {
            $this->body = new CachingEntityBody($this->body);
        }

        return true;
    }

    /**
     * Initialize the stream wrapper for a write only stream
     *
     * @param array $params Operation parameters
     * @param array $errors Any encountered errors to append to
     *
     * @return bool
     */
    protected function openWriteStream(array $params, array &$errors)
    {
        $this->body = new EntityBody(fopen('php://temp', 'r+'));
    }

    /**
     * Initialize the stream wrapper for an append stream
     *
     * @param array $params Operation parameters
     * @param array $errors Any encountered errors to append to
     *
     * @return bool
     */
    protected function openAppendStream(array $params, array &$errors)
    {
        try {
            // Get the body of the object
            $this->body = static::$client->getObject($params)->get('Body');
            $this->body->seek(0, SEEK_END);
        } catch (S3Exception $e) {
            // The object does not exist, so use a simple write stream
            $this->openWriteStream($params, $errors);
        }

        return true;
    }

    /**
     * Trigger one or more errors
     *
     * @param string|array $errors Errors to trigger
     * @param mixed        $flags  If set to STREAM_URL_STAT_QUIET, then no error or exception occurs
     *
     * @return bool Returns false
     * @throws RuntimeException if throw_errors is true
     */
    protected function triggerError($errors, $flags = null)
    {
        if ($flags & STREAM_URL_STAT_QUIET) {
          // This is triggered with things like file_exists()

          if ($flags & STREAM_URL_STAT_LINK) {
            // This is triggered for things like is_link()
            return $this->formatUrlStat(false);
          }
          return false;
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
    protected function formatUrlStat($result = null)
    {
        static $statTemplate = array(
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
        );

        $stat = $statTemplate;
        $type = gettype($result);

        // Determine what type of data is being cached
        if ($type == 'NULL' || $type == 'string') {
            // Directory with 0777 access - see "man 2 stat".
            $stat['mode'] = $stat[2] = 0040777;
        } elseif ($type == 'array' && isset($result['LastModified'])) {
            // ListObjects or HeadObject result
            $stat['mtime'] = $stat[9] = $stat['ctime'] = $stat[10] = strtotime($result['LastModified']);
            $stat['size'] = $stat[7] = (isset($result['ContentLength']) ? $result['ContentLength'] : $result['Size']);
            // Regular file with 0777 access - see "man 2 stat".
            $stat['mode'] = $stat[2] = 0100777;
        }

        return $stat;
    }

    /**
     * Clear the next stat result from the cache
     *
     * @param string $path If a path is specific, clearstatcache() will be called
     */
    protected function clearStatInfo($path = null)
    {
        static::$nextStat = array();
        if ($path) {
            clearstatcache(true, $path);
        }
    }

    /**
     * Creates a bucket for the given parameters.
     *
     * @param string $path   Stream wrapper path
     * @param array  $params A result of StreamWrapper::getParams()
     *
     * @return bool Returns true on success or false on failure
     */
    private function createBucket($path, array $params)
    {
        if (static::$client->doesBucketExist($params['Bucket'])) {
            return $this->triggerError("Directory already exists: {$path}");
        }

        try {
            static::$client->createBucket($params);
            $this->clearStatInfo($path);
            return true;
        } catch (\Exception $e) {
            return $this->triggerError($e->getMessage());
        }
    }

    /**
     * Creates a pseudo-folder by creating an empty "/" suffixed key
     *
     * @param string $path   Stream wrapper path
     * @param array  $params A result of StreamWrapper::getParams()
     *
     * @return bool
     */
    private function createPseudoDirectory($path, array $params)
    {
        // Ensure the path ends in "/" and the body is empty.
        $params['Key'] = rtrim($params['Key'], '/') . '/';
        $params['Body'] = '';

        // Fail if this pseudo directory key already exists
        if (static::$client->doesObjectExist($params['Bucket'], $params['Key'])) {
            return $this->triggerError("Directory already exists: {$path}");
        }

        try {
            static::$client->putObject($params);
            $this->clearStatInfo($path);
            return true;
        } catch (\Exception $e) {
            return $this->triggerError($e->getMessage());
        }
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
        $mode = decoct($mode);

        if ($mode >= 700 && $mode <= 799) {
            return 'public-read';
        }

        if ($mode >= 600 && $mode <= 699) {
            return 'authenticated-read';
        }

        return 'private';
    }
}
