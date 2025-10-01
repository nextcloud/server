<?php
namespace Aws\S3;

use Aws;
use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\MetricsBuilder;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\PromisorInterface;
use Iterator;

/**
 * Transfers files from the local filesystem to S3 or from S3 to the local
 * filesystem.
 *
 * This class does not support copying from the local filesystem to somewhere
 * else on the local filesystem or from one S3 bucket to another.
 */
class Transfer implements PromisorInterface
{
    private $client;
    private $promise;
    private $source;
    private $sourceMetadata;
    private $destination;
    private $concurrency;
    private $mupThreshold;
    private $before;
    private $after;
    private $s3Args = [];
    private $addContentMD5;

    /**
     * When providing the $source argument, you may provide a string referencing
     * the path to a directory on disk to upload, an s3 scheme URI that contains
     * the bucket and key (e.g., "s3://bucket/key"), or an \Iterator object
     * that yields strings containing filenames that are the path to a file on
     * disk or an s3 scheme URI. The bucket portion of the s3 URI may be an S3
     * access point ARN. The "/key" portion of an s3 URI is optional.
     *
     * When providing an iterator for the $source argument, you must also
     * provide a 'base_dir' key value pair in the $options argument.
     *
     * The $dest argument can be the path to a directory on disk or an s3
     * scheme URI (e.g., "s3://bucket/key").
     *
     * The options array can contain the following key value pairs:
     *
     * - base_dir: (string) Base directory of the source, if $source is an
     *   iterator. If the $source option is not an array, then this option is
     *   ignored.
     * - before: (callable) A callback to invoke before each transfer. The
     *   callback accepts a single argument: Aws\CommandInterface $command.
     *   The provided command will be either a GetObject, PutObject,
     *   InitiateMultipartUpload, or UploadPart command.
     * - after: (callable) A callback to invoke after each transfer promise is fulfilled.
     *   The function is invoked with three arguments: the fulfillment value, the index
     *   position from the iterable list of the promise, and the aggregate
     *   promise that manages all the promises. The aggregate promise may
     *   be resolved from within the callback to short-circuit the promise.
     * - mup_threshold: (int) Size in bytes in which a multipart upload should
     *   be used instead of PutObject. Defaults to 20971520 (20 MB).
     * - concurrency: (int, default=5) Number of files to upload concurrently.
     *   The ideal concurrency value will vary based on the number of files
     *   being uploaded and the average size of each file. Generally speaking,
     *   smaller files benefit from a higher concurrency while larger files
     *   will not.
     * - debug: (bool) Set to true to print out debug information for
     *   transfers. Set to an fopen() resource to write to a specific stream
     *   rather than writing to STDOUT.
     *
     * @param S3ClientInterface $client  Client used for transfers.
     * @param string|Iterator   $source  Where the files are transferred from.
     * @param string            $dest    Where the files are transferred to.
     * @param array             $options Hash of options.
     */
    public function __construct(
        S3ClientInterface $client,
        $source,
        $dest,
        array $options = []
    ) {
        $this->client = $client;

        // Prepare the destination.
        $this->destination = $this->prepareTarget($dest);
        if ($this->destination['scheme'] === 's3') {
            $this->s3Args = $this->getS3Args($this->destination['path']);
        }

        // Prepare the source.
        if (is_string($source)) {
            $this->sourceMetadata = $this->prepareTarget($source);
            $this->source = $source;
        } elseif ($source instanceof Iterator) {
            if (empty($options['base_dir'])) {
                throw new \InvalidArgumentException('You must provide the source'
                    . ' argument as a string or provide the "base_dir" option.');
            }

            $this->sourceMetadata = $this->prepareTarget($options['base_dir']);
            $this->source = $source;
        } else {
            throw new \InvalidArgumentException('source must be the path to a '
                . 'directory or an iterator that yields file names.');
        }

        // Validate schemes.
        if ($this->sourceMetadata['scheme'] === $this->destination['scheme']) {
            throw new \InvalidArgumentException("You cannot copy from"
                . " {$this->sourceMetadata['scheme']} to"
                . " {$this->destination['scheme']}."
            );
        }

        // Handle multipart-related options.
        $this->concurrency = isset($options['concurrency'])
            ? $options['concurrency']
            : MultipartUploader::DEFAULT_CONCURRENCY;
        $this->mupThreshold = isset($options['mup_threshold'])
            ? $options['mup_threshold']
            : 16777216;
        if ($this->mupThreshold < MultipartUploader::PART_MIN_SIZE) {
            throw new \InvalidArgumentException('mup_threshold must be >= 5MB');
        }

        // Handle "before" callback option.
        if (isset($options['before'])) {
            $this->before = $options['before'];
            if (!is_callable($this->before)) {
                throw new \InvalidArgumentException('before must be a callable.');
            }
        }

        // Handle "after" callback option.
        if (isset($options['after'])) {
            $this->after = $options['after'];
            if (!is_callable($this->after)) {
                throw new \InvalidArgumentException('after must be a callable.');
            }
        }

        // Handle "debug" option.
        if (isset($options['debug'])) {
            if ($options['debug'] === true) {
                $options['debug'] = fopen('php://output', 'w');
            }
            if (is_resource($options['debug'])) {
                $this->addDebugToBefore($options['debug']);
            }
        }

        // Handle "add_content_md5" option.
        $this->addContentMD5 = isset($options['add_content_md5'])
            && $options['add_content_md5'] === true;
        MetricsBuilder::appendMetricsCaptureMiddleware(
            $this->client->getHandlerList(),
            MetricsBuilder::S3_TRANSFER
        );
    }

    /**
     * Transfers the files.
     *
     * @return PromiseInterface
     */
    public function promise(): PromiseInterface
    {
        // If the promise has been created, just return it.
        if (!$this->promise) {
            // Create an upload/download promise for the transfer.
            $this->promise = $this->sourceMetadata['scheme'] === 'file'
                ? $this->createUploadPromise()
                : $this->createDownloadPromise();
        }

        return $this->promise;
    }

    /**
     * Transfers the files synchronously.
     */
    public function transfer()
    {
        $this->promise()->wait();
    }

    private function prepareTarget($targetPath)
    {
        $target = [
            'path'   => $this->normalizePath($targetPath),
            'scheme' => $this->determineScheme($targetPath),
        ];

        if ($target['scheme'] !== 's3' && $target['scheme'] !== 'file') {
            throw new \InvalidArgumentException('Scheme must be "s3" or "file".');
        }

        return $target;
    }

    /**
     * Creates an array that contains Bucket and Key by parsing the filename.
     *
     * @param string $path Path to parse.
     *
     * @return array
     */
    private function getS3Args($path)
    {
        $parts = explode('/', str_replace('s3://', '', $path), 2);
        $args = ['Bucket' => $parts[0]];
        if (isset($parts[1])) {
            $args['Key'] = $parts[1];
        }

        return $args;
    }

    /**
     * Parses the scheme from a filename.
     *
     * @param string $path Path to parse.
     *
     * @return string
     */
    private function determineScheme($path)
    {
        return !strpos($path, '://') ? 'file' : explode('://', $path)[0];
    }

    /**
     * Normalize a path so that it has UNIX-style directory separators and no trailing /
     *
     * @param string $path
     *
     * @return string
     */
    private function normalizePath($path)
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }

    private function resolvesOutsideTargetDirectory($sink, $objectKey)
    {
        $resolved = [];
        $sections = explode('/', $sink);
        $targetSectionsLength = count(explode('/', $objectKey));
        $targetSections = array_slice($sections, -($targetSectionsLength + 1));
        $targetDirectory = $targetSections[0];

        foreach ($targetSections as $section) {
            if ($section === '.' || $section === '') {
                continue;
            }
            if ($section === '..') {
                array_pop($resolved);
                if (empty($resolved) || $resolved[0] !== $targetDirectory) {
                    return true;
                }
            } else {
                $resolved []= $section;
            }
        }
        return false;
    }

    private function createDownloadPromise()
    {
        $parts = $this->getS3Args($this->sourceMetadata['path']);
        $prefix = "s3://{$parts['Bucket']}/"
            . (isset($parts['Key']) ? $parts['Key'] . '/' : '');

        $commands = [];
        foreach ($this->getDownloadsIterator() as $object) {
            // Prepare the sink.
            $objectKey = preg_replace('/^' . preg_quote($prefix, '/') . '/', '', $object);
            $sink = $this->destination['path'] . '/' . $objectKey;

            $command = $this->client->getCommand(
                'GetObject',
                $this->getS3Args($object) + ['@http'  => ['sink'  => $sink]]
            );

            if ($this->resolvesOutsideTargetDirectory($sink, $objectKey)) {
                throw new AwsException(
                    'Cannot download key ' . $objectKey
                    . ', its relative path resolves outside the'
                    . ' parent directory', $command);
            }

            // Create the directory if needed.
            $dir = dirname($sink);
            if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
                throw new \RuntimeException("Could not create dir: {$dir}");
            }

            // Create the command.
            $commands []= $command;
        }

        // Create a GetObject command pool and return the promise.
        return (new Aws\CommandPool($this->client, $commands, [
            'concurrency' => $this->concurrency,
            'before'      => $this->before,
            'fulfill'     => $this->after,
            'rejected'    => function ($reason, $idx, Promise\PromiseInterface $p) {
                $p->reject($reason);
            }
        ]))->promise();
    }

    private function createUploadPromise()
    {
        // Map each file into a promise that performs the actual transfer.
        $files = \Aws\map($this->getUploadsIterator(), function ($file) {
            return (filesize($file) >= $this->mupThreshold)
                ? $this->uploadMultipart($file)
                : $this->upload($file);
        });

        // Create an EachPromise, that will concurrently handle the upload
        // operations' yielded promises from the iterator.
        return Promise\Each::ofLimitAll($files, $this->concurrency, $this->after);
    }

    /** @return Iterator */
    private function getUploadsIterator()
    {
        if (is_string($this->source)) {
            return Aws\filter(
                Aws\recursive_dir_iterator($this->sourceMetadata['path']),
                function ($file) { return !is_dir($file); }
            );
        }

        return $this->source;
    }

    /** @return Iterator */
    private function getDownloadsIterator()
    {
        if (is_string($this->source)) {
            $listArgs = $this->getS3Args($this->sourceMetadata['path']);
            if (isset($listArgs['Key'])) {
                $listArgs['Prefix'] = $listArgs['Key'] . '/';
                unset($listArgs['Key']);
            }

            $files = $this->client
                ->getPaginator('ListObjects', $listArgs)
                ->search('Contents[].Key');
            $files = Aws\map($files, function ($key) use ($listArgs) {
                return "s3://{$listArgs['Bucket']}/$key";
            });
            return Aws\filter($files, function ($key) {
                return substr($key, -1, 1) !== '/';
            });
        }

        return $this->source;
    }

    private function upload($filename)
    {
        $args = $this->s3Args;
        $args['SourceFile'] = $filename;
        $args['Key'] = $this->createS3Key($filename);
        $args['AddContentMD5'] = $this->addContentMD5;
        $command = $this->client->getCommand('PutObject', $args);
        $this->before and call_user_func($this->before, $command);

        return $this->client->executeAsync($command);
    }

    private function uploadMultipart($filename)
    {
        $args = $this->s3Args;
        $args['Key'] = $this->createS3Key($filename);
        $filename = $filename instanceof \SplFileInfo ? $filename->getPathname() : $filename;

        return (new MultipartUploader($this->client, $filename, [
            'bucket'          => $args['Bucket'],
            'key'             => $args['Key'],
            'before_initiate' => $this->before,
            'before_upload'   => $this->before,
            'before_complete' => $this->before,
            'concurrency'     => $this->concurrency,
            'add_content_md5' => $this->addContentMD5
        ]))->promise();
    }

    private function createS3Key($filename)
    {
        $filename = $this->normalizePath($filename);
        $relative_file_path = ltrim(
            preg_replace('#^' . preg_quote($this->sourceMetadata['path']) . '#', '', $filename),
            '/\\'
        );

        if (isset($this->s3Args['Key'])) {
            return rtrim($this->s3Args['Key'], '/').'/'.$relative_file_path;
        }

        return $relative_file_path;
    }

    private function addDebugToBefore($debug)
    {
        $before = $this->before;
        $sourcePath = $this->sourceMetadata['path'];
        $s3Args = $this->s3Args;

        $this->before = static function (
            CommandInterface $command
        ) use ($before, $debug, $sourcePath, $s3Args) {
            // Call the composed before function.
            $before and $before($command);

            // Determine the source and dest values based on operation.
            switch ($operation = $command->getName()) {
                case 'GetObject':
                    $source = "s3://{$command['Bucket']}/{$command['Key']}";
                    $dest = $command['@http']['sink'];
                    break;
                case 'PutObject':
                    $source = $command['SourceFile'];
                    $dest = "s3://{$command['Bucket']}/{$command['Key']}";
                    break;
                case 'UploadPart':
                    $part = $command['PartNumber'];
                case 'CreateMultipartUpload':
                case 'CompleteMultipartUpload':
                    $sourceKey = $command['Key'];
                    if (isset($s3Args['Key']) && strpos($sourceKey, $s3Args['Key']) === 0) {
                        $sourceKey = substr($sourceKey, strlen($s3Args['Key']) + 1);
                    }
                    $source = "{$sourcePath}/{$sourceKey}";
                    $dest = "s3://{$command['Bucket']}/{$command['Key']}";
                    break;
                default:
                    throw new \UnexpectedValueException(
                        "Transfer encountered an unexpected operation: {$operation}."
                    );
            }

            // Print the debugging message.
            $context = sprintf('%s -> %s (%s)', $source, $dest, $operation);
            if (isset($part)) {
                $context .= " : Part={$part}";
            }
            fwrite($debug, "Transferring {$context}\n");
        };
    }
}
