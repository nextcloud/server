<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023, Thomas Citharel
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


namespace OCP\Location;

class LocationAddress implements ILocationAddress, \JsonSerializable {

	private ?string $streetName = null;
	private ?string $locality = null;
	private ?string $postalCode = null;
	private ?string $region = null;
	private ?string $country = null;
	private ?string $description = null;
	private ?string $type = null;
	private ?string $geometry = null;
	private ?string $originId = null;
	private ?string $timezone = null;

	public function getStreetName(): ?string {
		return $this->streetName;
	}

	public function setStreetName(?string $streetName): LocationAddress {
		$this->streetName = $streetName;
		return $this;
	}

	public function getLocality(): ?string {
		return $this->locality;
	}

	public function setLocality(?string $locality): LocationAddress {
		$this->locality = $locality;
		return $this;
	}

	public function getPostalCode(): ?string {
		return $this->postalCode;
	}

	public function setPostalCode(?string $postalCode): LocationAddress {
		$this->postalCode = $postalCode;
		return $this;
	}

	public function getRegion(): ?string {
		return $this->region;
	}

	public function setRegion(?string $region): LocationAddress {
		$this->region = $region;
		return $this;
	}

	public function getCountry(): ?string {
		return $this->country;
	}

	public function setCountry(?string $country): LocationAddress {
		$this->country = $country;
		return $this;
	}

	public function getDescription(): ?string {
		return $this->description;
	}

	public function setDescription(?string $description): LocationAddress {
		$this->description = $description;
		return $this;
	}

	public function getType(): ?string {
		return $this->type;
	}

	public function setType(?string $type): LocationAddress {
		$this->type = $type;
		return $this;
	}

	public function getGeometry(): ?string {
		return $this->geometry;
	}

	public function setGeometry(?string $geometry): LocationAddress {
		$this->geometry = $geometry;
		return $this;
	}

	public function getOriginId(): ?string {
		return $this->originId;
	}

	public function setOriginId(?string $originId): LocationAddress {
		$this->originId = $originId;
		return $this;
	}

	public function getTimezone(): ?string {
		return $this->timezone;
	}

	public function setTimezone(?string $timezone): LocationAddress {
		$this->timezone = $timezone;
		return $this;
	}

	function jsonSerialize(): array {
		return [
			'streetName' => $this->streetName,
			'locality' => $this->locality,
			'postalCode' => $this->postalCode,
			'region' => $this->region,
			'country' => $this->country,
			'description' => $this->description,
			'type' => $this->type,
			'geometry' => $this->geometry,
			'originId' => $this->originId,
			'timezone' => $this->timezone,
		];
	}
}
