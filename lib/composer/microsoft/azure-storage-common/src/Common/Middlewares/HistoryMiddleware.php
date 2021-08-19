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
 * @package   MicrosoftAzure\Storage\Common\Middlewares
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common\Middlewares;

use MicrosoftAzure\Storage\Common\Internal\Validate;
use MicrosoftAzure\Storage\Common\Internal\Utilities;
use MicrosoftAzure\Storage\Common\Internal\Serialization\MessageSerializer;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\RejectedPromise;

/**
 * This class provides the functionality to log the requests/options/responses.
 * Logging large number of entries without providing a file path may exhaust
 * the memory.
 *
 * The middleware should be pushed into client options if the logging is
 * intended to persist between different API calls.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Middlewares
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class HistoryMiddleware extends MiddlewareBase
{
    private $history;
    private $path;
    private $count;

    const TITLE_LENGTH = 120;

    /**
     * Gets the saved paried history.
     *
     * @return array
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * Constructor
     *
     * @param string $path the path to save the history. If path is provided,
     *                     no data is going to be saved to memory and the
     *                     entries are going to be serialized and saved to given
     *                     path.
     *
     */
    public function __construct($path = '')
    {
        $this->history = array();
        $this->path = $path;
        $this->count = 0;
    }

    /**
     * Add an entry to history
     *
     * @param array $entry the entry to be added.
     */
    public function addHistory(array $entry)
    {
        if ($this->path !== '') {
            $this->appendNewEntryToPath($entry);
        } else {
            Validate::isTrue(
                array_key_exists('request', $entry) &&
                array_key_exists('options', $entry) &&
                (array_key_exists('response', $entry) ||
                array_key_exists('reason', $entry)),
                'Given history entry not in correct format'
            );
            $this->history[] = $entry;
        }
        ++$this->count;
    }

    /**
     * Clear the history
     *
     * @return void
     */
    public function clearHistory()
    {
        $this->history = array();
        $this->count = 0;
    }

    /**
     * This function will be invoked after the request is sent, if
     * the promise is fulfilled.
     *
     * @param  RequestInterface $request the request sent.
     * @param  array            $options the options that the request sent with.
     *
     * @return callable
     */
    protected function onFulfilled(RequestInterface $request, array $options)
    {
        $reflection = $this;
        return function (ResponseInterface $response) use (
            $reflection,
            $request,
            $options
        ) {
            $reflection->addHistory([
                'request'  => $request,
                'response' => $response,
                'options'  => $options
            ]);
            return $response;
        };
    }

    /**
     * This function will be executed after the request is sent, if
     * the promise is rejected.
     *
     * @param  RequestInterface $request the request sent.
     * @param  array            $options the options that the request sent with.
     *
     * @return callable
     */
    protected function onRejected(RequestInterface $request, array $options)
    {
        $reflection = $this;
        return function ($reason) use (
            $reflection,
            $request,
            $options
        ) {
            $reflection->addHistory([
                'request' => $request,
                'reason'  => $reason,
                'options' => $options
            ]);
            return new RejectedPromise($reason);
        };
    }

    /**
     * Append the new entry to saved file path.
     *
     * @param array $entry the entry to be added.
     *
     * @return void
     */
    private function appendNewEntryToPath(array $entry)
    {
        $entryNoString = "Entry " . $this->count;
        $delimiter = str_pad(
            $entryNoString,
            self::TITLE_LENGTH,
            '-',
            STR_PAD_BOTH
        ) . PHP_EOL;
        $entryString = $delimiter;
        $entryString .= sprintf(
            "Time: %s\n",
            (new \DateTime("now", new \DateTimeZone('UTC')))->format('Y-m-d H:i:s')
        );
        $entryString .= MessageSerializer::objectSerialize($entry['request']);
        if (array_key_exists('reason', $entry)) {
            $entryString .= MessageSerializer::objectSerialize($entry['reason']);
        } elseif (array_key_exists('response', $entry)) {
            $entryString .= MessageSerializer::objectSerialize($entry['response']);
        }

        $entryString .= $delimiter;

        Utilities::appendToFile($this->path, $entryString);
    }
}
