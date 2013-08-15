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
 * Converts filenames from one system to another
 */
class KeyConverter implements FilenameConverterInterface
{
    /** @var string Directory separator for Amazon S3 keys */
    protected $delimiter;

    /** @var string Prefix to prepend to each Amazon S3 object key */
    protected $prefix;

    /** @var string Base directory to remove from each file path before converting to an object key */
    protected $baseDir;

    /**
     * @param string $baseDir   Base directory to remove from each converted name
     * @param string $prefix    Amazon S3 prefix
     * @param string $delimiter Directory separator used with generated names
     */
    public function __construct($baseDir = '', $prefix = '', $delimiter = '/')
    {
        $this->baseDir = $baseDir;
        $this->prefix = $prefix;
        $this->delimiter = $delimiter;
    }

    public function convert($filename)
    {
        // Remove base directory from the key
        $key = str_replace($this->baseDir, '', $filename);
        // Replace Windows directory separators to become Unix style, and convert that to the custom dir separator
        $key = str_replace('/', $this->delimiter, str_replace('\\', '/', $key));
        // Add the key prefix and remove double slashes
        $key = str_replace($this->delimiter . $this->delimiter, $this->delimiter, $this->prefix . $key);

        return ltrim($key, $this->delimiter);
    }
}
