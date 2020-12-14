<?php

declare(strict_types=1);

/**
 * @copyright 2020, Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OC\WellKnown\Model;

use JsonSerializable;
use OCP\IRequest;
use OCP\WellKnown\Model\IWellKnown;

/**
 * @since 21.0.0
 *
 * @package OC\WellKnown\Model
 */
final class WellKnown implements IWellKnown, JsonSerializable {


	/** @var string */
	private $service;

	/** @var IRequest */
	private $request;

	/** @var string */
	private $subject = '';

	/** @var array */
	private $aliases = [];

	/** @var array */
	private $properties = [];

	/** @var array */
	private $rels = [];

	/** @var array */
	private $links = [];


	/**
	 * WellKnown constructor.
	 *
	 * @param string $service
	 * @param IRequest $request
	 *
	 * @since 21.0.0
	 */
	public function __construct(string $service, IRequest $request) {
		$this->request = $request;
		$this->service = $service;
		$this->subject = $request->getParam('resource', '');
		$rel = $request->getParam('rel', '');
		if ($rel !== '') {
			$this->rels[] = $rel;
		}
	}


	/**
	 * @return string
	 * @since 21.0.0
	 */
	public function getService(): string {
		return $this->service;
	}

	/**
	 * @param string $service
	 *
	 * @return bool
	 * @since 21.0.0
	 */
	public function isService(string $service): bool {
		return ($this->service === $service);
	}


	/**
	 * @return IRequest
	 * @since 21.0.0
	 */
	public function getRequest(): IRequest {
		return $this->request;
	}


	/**
	 * @return string
	 * @since 21.0.0
	 */
	public function getSubject(): string {
		return $this->subject;
	}

	/**
	 * @param string $subject
	 *
	 * @return bool
	 * @since 21.0.0
	 */
	public function isSubject(string $subject): bool {
		return ($this->subject === $subject);
	}


	/**
	 * @return array
	 * @since 21.0.0
	 */
	public function getRels(): array {
		return $this->rels;
	}

	/**
	 * @param string $rel
	 *
	 * @return bool
	 * @since 21.0.0
	 */
	public function isRequestedRel(string $rel): bool {
		return (empty($this->rels) || in_array($rel, $this->rels));
	}


	/**
	 * @param string $alias
	 *
	 * @return $this
	 * @since 21.0.0
	 */
	public function addAlias(string $alias): IWellKnown {
		if (!in_array($alias, $this->aliases)) {
			$this->aliases[] = $alias;
		}

		return $this;
	}

	/**
	 * @return array
	 * @since 21.0.0
	 */
	public function getAliases(): array {
		return $this->aliases;
	}


	/**
	 * @param string $property
	 * @param $value
	 *
	 * @return IWellKnown
	 * @since 21.0.0
	 */
	public function addProperty(string $property, $value): IWellKnown {
		$this->properties[$property] = $value;

		return $this;
	}

	/**
	 * @return array
	 * @since 21.0.0
	 */
	public function getProperties(): array {
		return $this->properties;
	}


	/**
	 * Please refer to official RFC regarding the generation of $link:
	 * - https://tools.ietf.org/html/rfc7033#section-4.4.4
	 *
	 * @param array $link
	 * @psalm-param array{rel: string, type: string, href: string, titles: array, properties: array} $link
	 * @return IWellKnown
	 *
	 * @return IWellKnown
	 * @since 21.0.0
	 */
	public function addLink(array $link): IWellKnown {
		$this->links[] = $link;

		return $this;
	}

	/**
	 * @param JsonSerializable $object
	 *
	 * @return IWellKnown
	 * @since 21.0.0
	 */
	public function addLinkSerialized(JsonSerializable $object): IWellKnown {
		$this->links[] = $object;

		return $this;
	}

	/**
	 * @return array
	 * @since 21.0.0
	 */
	public function getLinks(): array {
		return $this->links;
	}


	/**
	 * @return array
	 * @since 21.0.0
	 */
	public function jsonSerialize(): array {
		$data = [
			'subject'    => $this->getSubject(),
			'properties' => $this->getProperties(),
			'aliases'    => $this->getAliases(),
			'links'      => $this->getLinks()
		];

		return array_filter($data);
	}
}
