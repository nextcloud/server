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

namespace Aws\Common;

use Guzzle\Http\Url;

/**
 * Utility class for parsing regions and services from URLs
 */
class HostNameUtils
{
    const DEFAULT_REGION = 'us-east-1';
    const DEFAULT_GOV_REGION = 'us-gov-west-1';

    /**
     * Parse the AWS region name from a URL
     *
     *
     * @param Url $url HTTP URL
     *
     * @return string
     * @link http://docs.amazonwebservices.com/general/latest/gr/rande.html
     */
    public static function parseRegionName(Url $url)
    {
        // If we don't recognize the domain, just return the default
        if (substr($url->getHost(), -14) != '.amazonaws.com') {
            return self::DEFAULT_REGION;
        }

        $serviceAndRegion = substr($url->getHost(), 0, -14);
        // Special handling for S3 regions
        $separator = strpos($serviceAndRegion, 's3') === 0 ? '-' : '.';
        $separatorPos = strpos($serviceAndRegion, $separator);

        // If don't detect a separator, then return the default region
        if ($separatorPos === false) {
            return self::DEFAULT_REGION;
        }

        $region = substr($serviceAndRegion, $separatorPos + 1);

        // All GOV regions currently use the default GOV region
        if ($region == 'us-gov') {
            return self::DEFAULT_GOV_REGION;
        }

        return $region;
    }

    /**
     * Parse the AWS service name from a URL
     *
     * @param Url $url HTTP URL
     *
     * @return string Returns a service name (or empty string)
     * @link http://docs.amazonwebservices.com/general/latest/gr/rande.html
     */
    public static function parseServiceName(Url $url)
    {
        // The service name is the first part of the host
        $parts = explode('.', $url->getHost(), 2);

        // Special handling for S3
        if (stripos($parts[0], 's3') === 0) {
            return 's3';
        }

        return $parts[0];
    }
}
