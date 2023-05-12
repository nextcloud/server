<?php
/**
 * @copyright Julien Veyssier <eneiluj@posteo.net> 2022
 *
 * @author Julien Veyssier <julien-nc@posteo.net>
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
