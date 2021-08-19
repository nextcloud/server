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
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Blob\Models;

/**
 * Holds information about blob block.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class Block
{
    private $_blockId;
    private $_type;

    /**
     * Constructor.
     *
     * @param string $blockId The ID of this block.
     * @param string $type    The type of the block.
     */
    public function __construct($blockId = '', $type = '')
    {
        $this->_blockId = $blockId;
        $this->_type = $type;
    }

    /**
     * Sets the blockId.
     *
     * @param string $blockId The id of the block.
     *
     * @return void
     */
    public function setBlockId($blockId)
    {
        $this->_blockId = $blockId;
    }

    /**
     * Gets the blockId.
     *
     * @return string
     */
    public function getBlockId()
    {
        return $this->_blockId;
    }

    /**
     * Sets the type.
     *
     * @param string $type The type of the block.
     *
     * @return void
     */
    public function setType($type)
    {
        $this->_type = $type;
    }

    /**
     * Gets the type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }
}
