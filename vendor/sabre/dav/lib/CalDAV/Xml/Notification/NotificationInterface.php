<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Notification;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * This interface reflects a single notification type.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface NotificationInterface extends XmlSerializable
{
    /**
     * This method serializes the entire notification, as it is used in the
     * response body.
     */
    public function xmlSerializeFull(Writer $writer);

    /**
     * Returns a unique id for this notification.
     *
     * This is just the base url. This should generally be some kind of unique
     * id.
     *
     * @return string
     */
    public function getId();

    /**
     * Returns the ETag for this notification.
     *
     * The ETag must be surrounded by literal double-quotes.
     *
     * @return string
     */
    public function getETag();
}
