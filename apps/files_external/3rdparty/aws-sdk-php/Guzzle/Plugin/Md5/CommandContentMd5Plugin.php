<?php

namespace Guzzle\Plugin\Md5;

use Guzzle\Common\Event;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listener used to add a ContentMD5 header to the body of a command and adds ContentMD5 validation if the
 * ValidateMD5 option is not set to false on a command
 */
class CommandContentMd5Plugin  implements EventSubscriberInterface
{
    /** @var string Parameter used to check if the ContentMD5 value is being added */
    protected $contentMd5Param;

    /** @var string Parameter used to check if validation should occur on the response */
    protected $validateMd5Param;

    /**
     * @param string $contentMd5Param  Parameter used to check if the ContentMD5 value is being added
     * @param string $validateMd5Param Parameter used to check if validation should occur on the response
     */
    public function __construct($contentMd5Param = 'ContentMD5', $validateMd5Param = 'ValidateMD5')
    {
        $this->contentMd5Param = $contentMd5Param;
        $this->validateMd5Param = $validateMd5Param;
    }

    public static function getSubscribedEvents()
    {
        return array('command.before_send' => array('onCommandBeforeSend', -255));
    }

    public function onCommandBeforeSend(Event $event)
    {
        $command = $event['command'];
        $request = $command->getRequest();

        // Only add an MD5 is there is a MD5 option on the operation and it has a payload
        if ($request instanceof EntityEnclosingRequestInterface && $request->getBody()
            && $command->getOperation()->hasParam($this->contentMd5Param)) {
            // Check if an MD5 checksum value should be passed along to the request
            if ($command[$this->contentMd5Param] === true) {
                if (false !== ($md5 = $request->getBody()->getContentMd5(true, true))) {
                    $request->setHeader('Content-MD5', $md5);
                }
            }
        }

        // Check if MD5 validation should be used with the response
        if ($command[$this->validateMd5Param] === true) {
            $request->addSubscriber(new Md5ValidatorPlugin(true, false));
        }
    }
}
