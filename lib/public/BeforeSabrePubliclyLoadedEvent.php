<?php
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP;

/**
 * Dispatched before Sabre is loaded when accessing public webdav endpoints
 * This can be used to inject a Sabre plugin for example
 *
 * @since 26.0.0
 */
class BeforeSabrePubliclyLoadedEvent extends SabrePluginEvent {
}
