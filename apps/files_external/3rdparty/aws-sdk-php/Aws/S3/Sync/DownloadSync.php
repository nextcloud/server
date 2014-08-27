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

use Aws\Common\Exception\RuntimeException;
use Aws\S3\ResumableDownload;

/**
 * Downloads and Amazon S3 bucket to a local directory
 */
class DownloadSync extends AbstractSync
{
    protected function createTransferAction(\SplFileInfo $file)
    {
        $sourceFilename = $file->getPathname();
        list($bucket, $key) = explode('/', substr($sourceFilename, 5), 2);
        $filename = $this->options['source_converter']->convert($sourceFilename);
        $this->createDirectory($filename);

        // Some S3 buckets contains nested files under the same name as a directory
        if (is_dir($filename)) {
            return false;
        }

        // Allow a previously interrupted download to resume
        if (file_exists($filename) && $this->options['resumable']) {
            return new ResumableDownload($this->options['client'], $bucket, $key, $filename);
        }

        return $this->options['client']->getCommand('GetObject', array(
            'Bucket' => $bucket,
            'Key'    => $key,
            'SaveAs' => $filename
        ));
    }

    /**
     * @codeCoverageIgnore
     */
    protected function createDirectory($filename)
    {
        $directory = dirname($filename);
        // Some S3 clients create empty files to denote directories. Remove these so that we can create the directory.
        if (is_file($directory) && filesize($directory) == 0) {
            unlink($directory);
        }
        // Create the directory if it does not exist
        if (!is_dir($directory) && !mkdir($directory, 0777, true)) {
            $errors = error_get_last();
            throw new RuntimeException('Could not create directory: ' . $directory . ' - ' . $errors['message']);
        }
    }

    protected function filterCommands(array $commands)
    {
        // Build a list of all of the directories in each command so that we don't attempt to create an empty dir in
        // the same parallel transfer as attempting to create a file in that dir
        $dirs = array();
        foreach ($commands as $command) {
            $parts = array_values(array_filter(explode('/', $command['SaveAs'])));
            for ($i = 0, $total = count($parts); $i < $total; $i++) {
                $dir = '';
                for ($j = 0; $j < $i; $j++) {
                    $dir .= '/' . $parts[$j];
                }
                if ($dir && !in_array($dir, $dirs)) {
                    $dirs[] = $dir;
                }
            }
        }

        return array_filter($commands, function ($command) use ($dirs) {
            return !in_array($command['SaveAs'], $dirs);
        });
    }

    protected function transferCommands(array $commands)
    {
        parent::transferCommands($this->filterCommands($commands));
    }
}
