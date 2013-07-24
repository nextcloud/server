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

namespace Aws\Common\Model\MultipartUpload;

/**
 * An object that encapsulates the data identifying an upload
 */
interface UploadIdInterface extends \Serializable
{
    /**
     * Create an UploadId from an array
     *
     * @param array $data Data representing the upload identification
     *
     * @return self
     */
    public static function fromParams($data);

    /**
     * Returns the array form of the upload identification for use as command params
     *
     * @return array
     */
    public function toParams();
}
