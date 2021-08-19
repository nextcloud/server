<?php
namespace Aws\Glacier;

use Aws\HashInterface;

/**
 * Encapsulates the creation of a tree hash from streamed data
 */
class TreeHash implements HashInterface
{
    const MB = 1048576;
    const EMPTY_HASH = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';

    /** @var string Algorithm used for hashing. */
    private $algorithm;

    /** @var string Buffered data that has not yet been hashed. */
    private $buffer;

    /** @var array Binary checksums from which the tree hash is derived. */
    private $checksums = [];

    /** @var string Resulting hash in binary form. */
    private $hash;

    public function __construct($algorithm = 'sha256')
    {
        $this->algorithm = $algorithm;
        $this->reset();
    }

    /**
     * {@inheritdoc}
     * @throws \LogicException if the root tree hash is already calculated
     */
    public function update($data)
    {
        // Error if hash is already calculated.
        if ($this->hash) {
            throw new \LogicException('You may not add more data to a '
                . 'complete tree hash.');
        }

        // Buffer incoming data.
        $this->buffer .= $data;

        // When there is more than a MB of data, create a checksum.
        while (strlen($this->buffer) >= self::MB) {
            $data = substr($this->buffer, 0, self::MB);
            $this->buffer = substr($this->buffer, self::MB) ?: '';
            $this->checksums[] = hash($this->algorithm, $data, true);
        }

        return $this;
    }

    /**
     * Add a checksum to the tree hash directly
     *
     * @param string $checksum   The checksum to add
     * @param bool $inBinaryForm TRUE if checksum is in binary form
     *
     * @return self
     * @throws \LogicException if the root tree hash is already calculated
     */
    public function addChecksum($checksum, $inBinaryForm = false)
    {
        // Error if hash is already calculated
        if ($this->hash) {
            throw new \LogicException('You may not add more checksums to a '
                . 'complete tree hash.');
        }

        // Convert the checksum to binary form if necessary
        $this->checksums[] = $inBinaryForm ? $checksum : hex2bin($checksum);

        return $this;
    }

    public function complete()
    {
        if (!$this->hash) {
            // Clear out the remaining buffer.
            if (strlen($this->buffer) > 0) {
                $this->checksums[] = hash($this->algorithm, $this->buffer, true);
                $this->buffer = '';
            }

            // If no hashes, add the EMPTY_HASH.
            if (!$this->checksums) {
                $this->checksums[] = hex2bin(self::EMPTY_HASH);
            }

            // Perform hashes up the tree to arrive at the root checksum.
            $hashes = $this->checksums;
            while (count($hashes) > 1) {
                $sets = array_chunk($hashes, 2);
                $hashes = array();
                foreach ($sets as $set) {
                    $hashes[] = (count($set) === 1)
                        ? $set[0]
                        : hash($this->algorithm, $set[0] . $set[1], true);
                }
            }

            $this->hash = $hashes[0];
        }

        return $this->hash;
    }

    public function reset()
    {
        $this->hash = null;
        $this->checksums = [];
        $this->buffer = '';
    }
}
