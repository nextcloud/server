<?php
namespace Aws;

/**
 * Incremental hashing using PHP's hash functions.
 */
class PhpHash implements HashInterface
{
    /** @var resource|\HashContext */
    private $context;

    /** @var string */
    private $algo;

    /** @var array */
    private $options;

    /** @var string */
    private $hash;

    /**
     * @param string $algo Hashing algorithm. One of PHP's hash_algos()
     *     return values (e.g. md5, sha1, etc...).
     * @param array  $options Associative array of hashing options:
     *     - key: Secret key used with the hashing algorithm.
     *     - base64: Set to true to base64 encode the value when complete.
     */
    public function __construct($algo, array $options = [])
    {
        $this->algo = $algo;
        $this->options = $options;
    }

    public function update($data)
    {
        if ($this->hash !== null) {
            $this->reset();
        }

        hash_update($this->getContext(), $data);
    }

    public function complete()
    {
        if ($this->hash) {
            return $this->hash;
        }

        $this->hash = hash_final($this->getContext(), true);

        if (isset($this->options['base64']) && $this->options['base64']) {
            $this->hash = base64_encode($this->hash);
        }

        return $this->hash;
    }

    public function reset()
    {
        $this->context = $this->hash = null;
    }

    /**
     * Get a hash context or create one if needed
     *
     * @return resource|\HashContext 
     */
    private function getContext()
    {
        if (!$this->context) {
            $key = isset($this->options['key']) ? $this->options['key'] : '';
            $this->context = hash_init(
                $this->algo,
                $key ? HASH_HMAC : 0,
                $key
            );
        }

        return $this->context;
    }
}
