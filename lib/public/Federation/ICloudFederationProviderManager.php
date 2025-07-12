<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Federation;

use OCP\Http\Client\IResponse;
use OCP\OCM\Exceptions\OCMProviderException;

/**
 * Class ICloudFederationProviderManager
 *
 * Manage cloud federation providers
 *
 * @since 14.0.0
 *
 */
interface ICloudFederationProviderManager {
	/**
	 * Registers an callback function which must return an cloud federation provider
	 *
	 * @param string $resourceType which resource type does the provider handles
	 * @param string $displayName user facing name of the federated share provider
	 * @param callable $callback
	 * @throws Exceptions\ProviderAlreadyExistsException
	 *
	 * @since 14.0.0
	 */
	public function addCloudFederationProvider($resourceType, $displayName, callable $callback);

	/**
	 * remove cloud federation provider
	 *
	 * @param string $providerClass
	 *
	 * @since 26.0.0
	 */
	public function removeCloudFederationProvider($providerClass);

	/**
	 * get a list of all cloudFederationProviders
	 *
	 * @return array [providerClass => ['resourceType' => $resourceType, 'displayName' => $displayName, 'provider' => ICloudFederationProvider]]
	 *
	 * @since 26.0.0
	 */
	public function getAllCloudFederationProviders();

	/**
	 * get a specific cloud federation provider
	 *
	 * @param string $resourceType
	 * @param string|null $shareType
	 * @return ICloudFederationProvider
	 * @throws Exceptions\ProviderDoesNotExistsException
	 *
	 * @since 14.0.0
	 */
	public function getCloudFederationProvider($resourceType, $shareType = null);

	/**
	 * send federated share
	 *
	 * @param ICloudFederationShare $share
	 * @return mixed
	 *
	 * @since 14.0.0
	 * @deprecated 29.0.0 - Use {@see sendCloudShare()} instead and handle errors manually
	 */
	public function sendShare(ICloudFederationShare $share);

	/**
	 * @param ICloudFederationShare $share
	 * @return IResponse
	 * @throws OCMProviderException
	 * @since 29.0.0
	 */
	public function sendCloudShare(ICloudFederationShare $share): IResponse;

	/**
	 * send notification about existing share
	 *
	 * @param string $url
	 * @param ICloudFederationNotification $notification
	 * @return array|false
	 *
	 * @since 14.0.0
	 * @deprecated 29.0.0 - Use {@see sendCloudNotification()} instead and handle errors manually
	 */
	public function sendNotification($url, ICloudFederationNotification $notification);

	/**
	 * @param string $url
	 * @param ICloudFederationNotification $notification
	 * @return IResponse
	 * @throws OCMProviderException
	 * @since 29.0.0
	 */
	public function sendCloudNotification(string $url, ICloudFederationNotification $notification): IResponse;

	/**
	 * check if the new cloud federation API is ready to be used
	 *
	 * @return bool
	 *
	 * @since 14.0.0
	 */
	public function isReady();
}
