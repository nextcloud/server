<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Http;

/**
 * Class FeaturePolicy is a simple helper which allows applications to
 * modify the Feature-Policy sent by Nextcloud. Per default only autoplay is allowed
 * from the same domain and full screen as well from the same domain.
 *
 * Even if a value gets modified above defaults will still get appended. Please
 * notice that Nextcloud ships already with sensible defaults and those policies
 * should require no modification at all for most use-cases.
 *
 * @since 17.0.0
 */
class FeaturePolicy extends EmptyFeaturePolicy {
	protected $autoplayDomains = [
		'\'self\'',
	];

	/** @var string[] of allowed domains that can access the camera */
	protected $cameraDomains = [];

	protected $fullscreenDomains = [
		'\'self\'',
	];

	/** @var string[] of allowed domains that can use the geolocation of the device */
	protected $geolocationDomains = [];

	/** @var string[] of allowed domains that can use the microphone */
	protected $microphoneDomains = [];

	/** @var string[] of allowed domains that can use the payment API */
	protected $paymentDomains = [];
}
