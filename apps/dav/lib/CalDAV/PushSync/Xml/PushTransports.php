<?php

declare(strict_types=1);

/**
 * @copyright 2023 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\CalDAV\PushSync\Xml;

use OCA\DAV\CalDAV\PushSync\Plugin;
use OCA\DAV\Push\IPushTransport;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;


class PushTransports implements XmlSerializable {

	/** @var IPushTransport[] */
	private array $pushTransports;

	public function __construct(array $pushTransports) {
		$this->pushTransports = $pushTransports;
	}

	public function getValue(): array {
		return $this->pushTransports;
	}

	/**
	 * The xmlSerialize method is called during xml writing.
	 *
	 * Use the $writer argument to write its own xml serialization.
	 *
	 * An important note: do _not_ create a parent element. Any element
	 * implementing XmlSerializble should only ever write what's considered
	 * its 'inner xml'.
	 *
	 * The parent of the current element is responsible for writing a
	 * containing element.
	 *
	 * This allows serializers to be re-used for different element names.
	 *
	 * If you are opening new elements, you must also close them again.
	 *
	 * @param Writer $writer
	 * @return void
	 */
	public function xmlSerialize(Writer $writer): void {
		// $cs = '{' . Plugin::NS_CALENDARSERVER . '}';

		if (count($this->pushTransports) <= 0) return;

		//$writer->startElement($cs . 'push-transports');

		foreach ($this->pushTransports as $pushTransport) {
			$pushTransport->xmlSerialize($writer);

//			$writer->startElement($cs . 'transport');
//			$writer->writeAttribute('type', 'whatever');
//
//			$writer->startElement($cs . 'subscription-url');
//			$writer->writeElement('{DAV:}href', $pushTransport->getSubscriptionUrl());
//			$writer->endElement();
//
//			$writer->writeElement('apsbundleid', 'whatever');
//			$writer->writeElement('env', 'whatever');
//			$writer->writeElement('refresh-interval', $pushTransport->getRefreshInterval());
//
//			$writer->endElement(); // transport
		}
		// $writer->endElement();
	}
}
