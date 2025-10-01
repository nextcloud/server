<?php

/**
 * LICENSE: The MIT License (the "License")
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * https://github.com/azure/azure-storage-php/LICENSE
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * PHP version 5
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2018 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common\Models;

/**
 * Trait implementing setting and getting useTransactionalMD5 for
 * option classes which need support transactional MD5 validation
 * during data transferring.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2018 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
trait TransactionalMD5Trait
{
    /** @var $useTransactionalMD5 boolean */
    private $useTransactionalMD5;

    /**
     * Gets whether using transactional MD5 validation.
     *
     * @return boolean
     */
    public function getUseTransactionalMD5()
    {
        return $this->useTransactionalMD5;
    }

    /**
     * Sets whether using transactional MD5 validation.
     *
     * @param boolean $useTransactionalMD5 whether enable transactional
     *                                     MD5 validation.
     */
    public function setUseTransactionalMD5($useTransactionalMD5)
    {
        $this->useTransactionalMD5 = $useTransactionalMD5;
    }
}
