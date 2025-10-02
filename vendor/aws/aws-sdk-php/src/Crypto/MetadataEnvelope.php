<?php
namespace Aws\Crypto;

use Aws\HasDataTrait;
use \ArrayAccess;
use \IteratorAggregate;
use \InvalidArgumentException;
use \JsonSerializable;

/**
 * Stores encryption metadata for reading and writing.
 *
 * @internal
 */
class MetadataEnvelope implements ArrayAccess, IteratorAggregate, JsonSerializable
{
    use HasDataTrait;

    const CONTENT_KEY_V2_HEADER = 'x-amz-key-v2';
    const IV_HEADER = 'x-amz-iv';
    const MATERIALS_DESCRIPTION_HEADER = 'x-amz-matdesc';
    const KEY_WRAP_ALGORITHM_HEADER = 'x-amz-wrap-alg';
    const CONTENT_CRYPTO_SCHEME_HEADER = 'x-amz-cek-alg';
    const CRYPTO_TAG_LENGTH_HEADER = 'x-amz-tag-len';
    const UNENCRYPTED_CONTENT_LENGTH_HEADER = 'x-amz-unencrypted-content-length';

    private static $constants = [];

    public static function getConstantValues()
    {
        if (empty(self::$constants)) {
            $reflection = new \ReflectionClass(static::class);
            foreach (array_values($reflection->getConstants()) as $constant) {
                self::$constants[$constant] = true;
            }
        }

        return array_keys(self::$constants);
    }

    /**
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($name, $value)
    {
        $constants = self::getConstantValues();
        if (is_null($name) || !in_array($name, $constants)) {
            throw new InvalidArgumentException('MetadataEnvelope fields must'
                . ' must match a predefined offset; use the header constants.');
        }

        $this->data[$name] = $value;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->data;
    }
}
