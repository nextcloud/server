<?php
namespace Aws\Glacier;

use Aws\CommandInterface;
use Aws\HashingStream;
use Aws\Multipart\AbstractUploader;
use Aws\Multipart\UploadState;
use Aws\PhpHash;
use Aws\ResultInterface;
use GuzzleHttp\Psr7;
use Psr\Http\Message\StreamInterface as Stream;

/**
 * Encapsulates the execution of a multipart upload to Glacier.
 */
class MultipartUploader extends AbstractUploader
{
    const PART_MIN_SIZE = 1048576;

    private static $validPartSizes = [
        1048576,    //   1 MB
        2097152,    //   2 MB
        4194304,    //   4 MB
        8388608,    //   8 MB
        16777216,   //  16 MB
        33554432,   //  32 MB
        67108864,   //  64 MB
        134217728,  // 128 MB
        268435456,  // 256 MB
        536870912,  // 512 MB
        1073741824, //   1 GB
        2147483648, //   2 GB
        4294967296, //   4 GB
    ];

    /**
     * Creates an UploadState object for a multipart upload by querying the
     * service for the specified upload's information.
     *
     * @param GlacierClient $client    GlacierClient object to use.
     * @param string        $vaultName Vault name for the multipart upload.
     * @param string        $uploadId  Upload ID for the multipart upload.
     * @param string        $accountId Account ID for the multipart upload.
     *
     * @return UploadState
     */
    public static function getStateFromService(
        GlacierClient $client,
        $vaultName,
        $uploadId,
        $accountId = '-'
    ) {
        $state = new UploadState([
            'accountId' => $accountId,
            'vaultName' => $vaultName,
            'uploadId'  => $uploadId,
        ]);

        foreach ($client->getPaginator('ListParts', $state->getId()) as $result) {
            // Get the part size from the first part in the first result.
            if (!$state->getPartSize()) {
                $state->setPartSize($result['PartSizeInBytes']);
            }
            // Mark all the parts returned by ListParts as uploaded.
            foreach ($result['Parts'] as $part) {
                list($rangeIndex, $rangeSize) = self::parseRange(
                    $part['RangeInBytes'],
                    $state->getPartSize()
                );
                $state->markPartAsUploaded($rangeIndex, [
                    'size'     => $rangeSize,
                    'checksum' => $part['SHA256TreeHash'],
                ]);
            }
        }

        $state->setStatus(UploadState::INITIATED);

        return $state;
    }

    /**
     * Creates a multipart upload for a Glacier archive.
     *
     * The valid configuration options are as follows:
     *
     * - account_id: (string, default=string('-')) Account ID for the archive
     *   being uploaded, if different from the account making the request.
     * - archive_description: (string) Description of the archive.
     * - before_complete: (callable) Callback to invoke before the
     *   `CompleteMultipartUpload` operation. The callback should have a
     *   function signature like `function (Aws\Command $command) {...}`.
     * - before_initiate: (callable) Callback to invoke before the
     *   `InitiateMultipartUpload` operation. The callback should have a
     *   function signature like `function (Aws\Command $command) {...}`.
     * - before_upload: (callable) Callback to invoke before any
     *   `UploadMultipartPart` operations. The callback should have a function
     *   signature like `function (Aws\Command $command) {...}`.
     * - concurrency: (int, default=int(3)) Maximum number of concurrent
     *   `UploadMultipartPart` operations allowed during the multipart upload.
     * - part_size: (int, default=int(1048576)) Part size, in bytes, to use when
     *   doing a multipart upload. This must between 1 MB and 4 GB, and must be
     *   a power of 2 (in megabytes).
     * - prepare_data_source: (callable) Callback to invoke before starting the
     *   multipart upload workflow. The callback should have a function
     *   signature like `function () {...}`.
     * - state: (Aws\Multipart\UploadState) An object that represents the state
     *   of the multipart upload and that is used to resume a previous upload.
     *   When this options is provided, the `account_id`, `key`, and `part_size`
     *   options are ignored.
     * - vault_name: (string, required) Vault name to use for the archive being
     *   uploaded.
     *
     * @param GlacierClient $client Client used for the upload.
     * @param mixed         $source Source of the data to upload.
     * @param array         $config Configuration used to perform the upload.
     */
    public function __construct(GlacierClient $client, $source, array $config = [])
    {
        parent::__construct($client, $source, $config + [
            'account_id' => '-',
            'vault_name' => null,
        ]);
    }

    protected function loadUploadWorkflowInfo()
    {
        return [
            'command' => [
                'initiate' => 'InitiateMultipartUpload',
                'upload'   => 'UploadMultipartPart',
                'complete' => 'CompleteMultipartUpload',
            ],
            'id' => [
                'account_id' => 'accountId',
                'vault_name' => 'vaultName',
                'upload_id'  => 'uploadId',
            ],
            'part_num' => 'range',
        ];
    }

    protected function determinePartSize()
    {
        // Make sure the part size is set.
        $partSize = $this->config['part_size'] ?: self::PART_MIN_SIZE;

        // Ensure that the part size is valid.
        if (!in_array($partSize, self::$validPartSizes)) {
            throw new \InvalidArgumentException('The part_size must be a power '
                . 'of 2, in megabytes, such that 1 MB <= PART_SIZE <= 4 GB.');
        }

        return $partSize;
    }

    protected function createPart($seekable, $number)
    {
        $data = [];
        $firstByte = $this->source->tell();

        // Read from the source to create the body stream. This also
        // calculates the linear and tree hashes as the data is read.
        if ($seekable) {
            // Case 1: Stream is seekable, can make stream from new handle.
            $body = Psr7\Utils::tryFopen($this->source->getMetadata('uri'), 'r');
            $body = $this->limitPartStream(Psr7\Utils::streamFor($body));
            // Create another stream decorated with hashing streams and read
            // through it, so we can get the hash values for the part.
            $decoratedBody = $this->decorateWithHashes($body, $data);
            while (!$decoratedBody->eof()) $decoratedBody->read(1048576);
            // Seek the original source forward to the end of the range.
            $this->source->seek($this->source->tell() + $body->getSize());
        } else {
            // Case 2: Stream is not seekable, must store part in temp stream.
            $source = $this->limitPartStream($this->source);
            $source = $this->decorateWithHashes($source, $data);
            $body = Psr7\Utils::streamFor();
            Psr7\Utils::copyToStream($source, $body);
        }

        // Do not create a part if the body size is zero.
        if ($body->getSize() === 0) {
            return false;
        }

        $body->seek(0);
        $data['body'] = $body;
        $lastByte = $this->source->tell() - 1;
        $data['range'] = "bytes {$firstByte}-{$lastByte}/*";

        return $data;
    }

    protected function handleResult(CommandInterface $command, ResultInterface $result)
    {
        list($rangeIndex, $rangeSize) = $this->parseRange(
            $command['range'],
            $this->state->getPartSize()
        );

        $this->state->markPartAsUploaded($rangeIndex, [
            'size'     => $rangeSize,
            'checksum' => $command['checksum']
        ]);
    }

    protected function getInitiateParams()
    {
        $params = ['partSize' => $this->state->getPartSize()];
        if (isset($this->config['archive_description'])) {
            $params['archiveDescription'] = $this->config['archive_description'];
        }

        return $params;
    }

    protected function getCompleteParams()
    {
        $treeHash = new TreeHash();
        $archiveSize = 0;
        foreach ($this->state->getUploadedParts() as $part) {
            $archiveSize += $part['size'];
            $treeHash->addChecksum($part['checksum']);
        }

        return [
            'archiveSize' => $archiveSize,
            'checksum'    => bin2hex($treeHash->complete()),
        ];
    }

    /**
     * Decorates a stream with a tree AND linear sha256 hashing stream.
     *
     * @param Stream $stream Stream to decorate.
     * @param array  $data   Data bag that results are injected into.
     *
     * @return Stream
     */
    private function decorateWithHashes(Stream $stream, array &$data)
    {
        // Make sure that a tree hash is calculated.
        $stream = new HashingStream($stream, new TreeHash(),
            function ($result) use (&$data) {
                $data['checksum'] = bin2hex($result);
            }
        );

        // Make sure that a linear SHA256 hash is calculated.
        $stream = new HashingStream($stream, new PhpHash('sha256'),
            function ($result) use (&$data) {
                $data['ContentSHA256'] = bin2hex($result);
            }
        );

        return $stream;
    }

    /**
     * Parses a Glacier range string into a size and part number.
     *
     * @param string $range    Glacier range string (e.g., "bytes 5-5000/*")
     * @param int    $partSize The chosen part size
     *
     * @return array
     */
    private static function parseRange($range, $partSize)
    {
        // Strip away the prefix and suffix.
        if (strpos($range, 'bytes') !== false) {
            $range = substr($range, 6, -2);
        }

        // Split that range into it's parts.
        list($firstByte, $lastByte) = explode('-', $range);

        // Calculate and return range index and range size
        return [
            intval($firstByte / $partSize) + 1,
            $lastByte - $firstByte + 1,
        ];
    }
}
