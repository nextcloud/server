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

namespace Aws\S3\Sync;

/**
 * Iterator used to filter an internal iterator to only yield files that do not exist in the target iterator or files
 * that have changed
 */
class ChangedFilesIterator extends \FilterIterator
{
    /** @var \Iterator */
    protected $sourceIterator;

    /** @var \Iterator */
    protected $targetIterator;

    /** @var FilenameConverterInterface */
    protected $sourceConverter;

    /** @var FilenameConverterInterface */
    protected $targetConverter;

    /** @var array Previously loaded data */
    protected $cache = array();

    /**
     * @param \Iterator                  $sourceIterator  Iterator to wrap and filter
     * @param \Iterator                  $targetIterator  Iterator used to compare against the source iterator
     * @param FilenameConverterInterface $sourceConverter Key converter to convert source to target keys
     * @param FilenameConverterInterface $targetConverter Key converter to convert target to source keys
     */
    public function __construct(
        \Iterator $sourceIterator,
        \Iterator $targetIterator,
        FilenameConverterInterface $sourceConverter,
        FilenameConverterInterface $targetConverter
    ) {
        $this->targetIterator = $targetIterator;
        $this->sourceConverter = $sourceConverter;
        $this->targetConverter = $targetConverter;
        parent::__construct($sourceIterator);
    }

    public function accept()
    {
        $current = $this->current();
        $key = $this->sourceConverter->convert($this->normalize($current));
        if (!($data = $this->getTargetData($key))) {
            return true;
        }

        // Ensure the Content-Length matches and it hasn't been modified since the mtime
        return $current->getSize() != $data[0] || $current->getMTime() > $data[1];
    }

    /**
     * Returns an array of the files from the target iterator that were not found in the source iterator
     *
     * @return array
     */
    public function getUnmatched()
    {
        return array_keys($this->cache);
    }

    /**
     * Get key information from the target iterator for a particular filename
     *
     * @param string $key Target iterator filename
     *
     * @return array|bool Returns an array of data, or false if the key is not in the iterator
     */
    protected function getTargetData($key)
    {
        $key = $this->cleanKey($key);

        if (isset($this->cache[$key])) {
            $result = $this->cache[$key];
            unset($this->cache[$key]);
            return $result;
        }

        $it = $this->targetIterator;

        while ($it->valid()) {
            $value = $it->current();
            $data = array($value->getSize(), $value->getMTime());
            $filename = $this->targetConverter->convert($this->normalize($value));
            $filename = $this->cleanKey($filename);

            if ($filename == $key) {
                return $data;
            }

            $this->cache[$filename] = $data;
            $it->next();
        }

        return false;
    }

    private function normalize($current)
    {
        return $current->getRealPath() ?: (string) $current;
    }

    private function cleanKey($key)
    {
        return ltrim($key, '/');
    }
}
