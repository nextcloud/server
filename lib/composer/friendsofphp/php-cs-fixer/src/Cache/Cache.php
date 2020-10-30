<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Cache;

/**
 * @author Andreas Möller <am@localheinz.com>
 *
 * @internal
 */
final class Cache implements CacheInterface
{
    /**
     * @var SignatureInterface
     */
    private $signature;

    /**
     * @var array
     */
    private $hashes = [];

    public function __construct(SignatureInterface $signature)
    {
        $this->signature = $signature;
    }

    public function getSignature()
    {
        return $this->signature;
    }

    public function has($file)
    {
        return \array_key_exists($file, $this->hashes);
    }

    public function get($file)
    {
        if (!$this->has($file)) {
            return null;
        }

        return $this->hashes[$file];
    }

    public function set($file, $hash)
    {
        $this->hashes[$file] = $hash;
    }

    public function clear($file)
    {
        unset($this->hashes[$file]);
    }

    public function toJson()
    {
        $json = json_encode([
            'php' => $this->getSignature()->getPhpVersion(),
            'version' => $this->getSignature()->getFixerVersion(),
            'indent' => $this->getSignature()->getIndent(),
            'lineEnding' => $this->getSignature()->getLineEnding(),
            'rules' => $this->getSignature()->getRules(),
            'hashes' => $this->hashes,
        ]);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \UnexpectedValueException(sprintf(
                'Can not encode cache signature to JSON, error: "%s". If you have non-UTF8 chars in your signature, like in license for `header_comment`, consider enabling `ext-mbstring` or install `symfony/polyfill-mbstring`.',
                json_last_error_msg()
            ));
        }

        return $json;
    }

    /**
     * @param string $json
     *
     * @throws \InvalidArgumentException
     *
     * @return Cache
     */
    public static function fromJson($json)
    {
        $data = json_decode($json, true);

        if (null === $data && JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(sprintf(
                'Value needs to be a valid JSON string, got "%s", error: "%s".',
                $json,
                json_last_error_msg()
            ));
        }

        $requiredKeys = [
            'php',
            'version',
            'indent',
            'lineEnding',
            'rules',
            'hashes',
        ];

        $missingKeys = array_diff_key(array_flip($requiredKeys), $data);

        if (\count($missingKeys)) {
            throw new \InvalidArgumentException(sprintf(
                'JSON data is missing keys "%s"',
                implode('", "', $missingKeys)
            ));
        }

        $signature = new Signature(
            $data['php'],
            $data['version'],
            $data['indent'],
            $data['lineEnding'],
            $data['rules']
        );

        $cache = new self($signature);

        $cache->hashes = $data['hashes'];

        return $cache;
    }
}
