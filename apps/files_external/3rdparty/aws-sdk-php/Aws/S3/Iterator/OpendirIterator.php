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

namespace Aws\S3\Iterator;

/**
 * Provides an iterator around an opendir resource. This is useful when you need to provide context to an opendir so
 * you can't use RecursiveDirectoryIterator
 */
class OpendirIterator implements \Iterator
{
    /** @var resource */
    protected $dirHandle;

    /** @var \SplFileInfo */
    protected $currentFile;

    /** @var int */
    protected $key = -1;

    /** @var string */
    protected $filePrefix;

    /**
     * @param resource $dirHandle  Opened directory handled returned from opendir
     * @param string   $filePrefix Prefix to add to each filename
     */
    public function __construct($dirHandle, $filePrefix = '')
    {
        $this->filePrefix = $filePrefix;
        $this->dirHandle = $dirHandle;
        $this->next();
    }

    public function __destruct()
    {
        if ($this->dirHandle) {
            closedir($this->dirHandle);
        }
    }

    public function rewind()
    {
        $this->key = 0;
        rewinddir($this->dirHandle);
    }

    public function current()
    {
        return $this->currentFile;
    }

    public function next()
    {
        if ($file = readdir($this->dirHandle)) {
            $this->currentFile = new \SplFileInfo($this->filePrefix . $file);
        } else {
            $this->currentFile = false;
        }

        $this->key++;
    }

    public function key()
    {
        return $this->key;
    }

    public function valid()
    {
        return $this->currentFile !== false;
    }
}
