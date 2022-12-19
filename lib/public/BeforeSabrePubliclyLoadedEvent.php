<?php
/**
 * @copyright Julien Veyssier <eneiluj@posteo.net> 2022
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 *
 * @license AGPL-3.0-or-later
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
namespace OCP;

/**
 * Dispatched before Sabre is loaded when accessing public webdav endpoints
 * This can be used to inject a Sabre plugin for example
 *
 * @since 26.0.0
 */
class BeforeSabrePubliclyLoadedEvent extends SabrePluginEvent {
}
