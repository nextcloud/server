<?php

namespace Guzzle\Plugin\Backoff;

use Guzzle\Common\Event;
use Guzzle\Log\LogAdapterInterface;
use Guzzle\Log\MessageFormatter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Logs backoff retries triggered from the BackoffPlugin
 *
 * Format your log messages using a template that can contain template substitutions found in {@see MessageFormatter}.
 * In addition to the default template substitutions, there is also:
 *
 * - retries: The number of times the request has been retried
 * - delay:   The amount of time the request is being delayed
 */
class BackoffLogger implements EventSubscriberInterface
{
    /** @var string Default log message template */
    const DEFAULT_FORMAT = '[{ts}] {method} {url} - {code} {phrase} - Retries: {retries}, Delay: {delay}, Time: {connect_time}, {total_time}, cURL: {curl_code} {curl_error}';

    /** @var LogAdapterInterface Logger used to log retries */
    protected $logger;

    /** @var MessageFormatter Formatter used to format log messages */
    protected $formatter;

    /**
     * @param LogAdapterInterface $logger    Logger used to log the retries
     * @param MessageFormatter    $formatter Formatter used to format log messages
     */
    public function __construct(LogAdapterInterface $logger, MessageFormatter $formatter = null)
    {
        $this->logger = $logger;
        $this->formatter = $formatter ?: new MessageFormatter(self::DEFAULT_FORMAT);
    }

    public static function getSubscribedEvents()
    {
        return array(BackoffPlugin::RETRY_EVENT => 'onRequestRetry');
    }

    /**
     * Set the template to use for logging
     *
     * @param string $template Log message template
     *
     * @return self
     */
    public function setTemplate($template)
    {
        $this->formatter->setTemplate($template);

        return $this;
    }

    /**
     * Called when a request is being retried
     *
     * @param Event $event Event emitted
     */
    public function onRequestRetry(Event $event)
    {
        $this->logger->log($this->formatter->format(
            $event['request'],
            $event['response'],
            $event['handle'],
            array(
                'retries' => $event['retries'],
                'delay'   => $event['delay']
            )
        ));
    }
}
