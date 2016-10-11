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

namespace Aws\S3;

use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listener used to change the way in which buckets are referenced (path/virtual style) based on context
 */
class BucketStyleListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array('command.after_prepare' => array('onCommandAfterPrepare', -255));
    }

    /**
     * Changes how buckets are referenced in the HTTP request
     *
     * @param Event $event Event emitted
     */
    public function onCommandAfterPrepare(Event $event)
    {
        $command = $event['command'];
        $bucket = $command['Bucket'];
        $request = $command->getRequest();
        $pathStyle = false;

        if ($key = $command['Key']) {
            // Modify the command Key to account for the {/Key*} explosion into an array
            if (is_array($key)) {
                $command['Key'] = $key = implode('/', $key);
            }
        }

        // Set the key and bucket on the request
        $request->getParams()->set('bucket', $bucket)->set('key', $key);

        // Switch to virtual if PathStyle is disabled, or not a DNS compatible bucket name, or the scheme is
        // http, or the scheme is https and there are no dots in the host header (avoids SSL issues)
        if (!$command['PathStyle'] && $command->getClient()->isValidBucketName($bucket)
            && !($command->getRequest()->getScheme() == 'https' && strpos($bucket, '.'))
        ) {
            // Switch to virtual hosted bucket
            $request->setHost($bucket . '.' . $request->getHost());
            $request->setPath(preg_replace("#^/{$bucket}#", '', $request->getPath()));
        } else {
            $pathStyle = true;
        }

        if (!$bucket) {
            $request->getParams()->set('s3.resource', '/');
        } elseif ($pathStyle) {
            // Path style does not need a trailing slash
            $request->getParams()->set(
                's3.resource',
                '/' . rawurlencode($bucket) . ($key ? ('/' . S3Client::encodeKey($key)) : '')
            );
        } else {
            // Bucket style needs a trailing slash
            $request->getParams()->set(
                's3.resource',
                '/' . rawurlencode($bucket) . ($key ? ('/' . S3Client::encodeKey($key)) : '/')
            );
        }
    }
}
