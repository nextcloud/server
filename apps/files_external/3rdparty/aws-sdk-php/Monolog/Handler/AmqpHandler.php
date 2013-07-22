<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolog\Handler;

use Monolog\Logger;
use Monolog\Formatter\JsonFormatter;

class AmqpHandler extends AbstractProcessingHandler
{
    /**
     * @var \AMQPExchange $exchange
     */
    protected $exchange;

    /**
     * @param \AMQPExchange $exchange     AMQP exchange, ready for use
     * @param string        $exchangeName
     * @param int           $level
     * @param bool          $bubble       Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(\AMQPExchange $exchange, $exchangeName = 'log', $level = Logger::DEBUG, $bubble = true)
    {
        $this->exchange = $exchange;
        $this->exchange->setName($exchangeName);

        parent::__construct($level, $bubble);
    }

    /**
     * {@inheritDoc}
     */
    protected function write(array $record)
    {
        $data = $record["formatted"];

        $routingKey = sprintf(
            '%s.%s',
            substr($record['level_name'], 0, 4),
            $record['channel']
        );

        $this->exchange->publish(
            $data,
            strtolower($routingKey),
            0,
            array(
                'delivery_mode' => 2,
                'Content-type' => 'application/json'
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultFormatter()
    {
        return new JsonFormatter();
    }
}
