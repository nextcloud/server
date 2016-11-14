<?php
/**
 * @author Cornelius Kölbel <cornelius.koelbel@netknights.it>
 * @copyright Copyright (c) 2016, ownCloud GmbH.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 * User: cornelius
 * Date: 14.11.16
 */

/*
 * This is the public API of ownCloud. It defines an Exception a 2FA app can
 * throw in case of an error. The 2FA Controller will catch this exception and
 * display this error.
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP\Authentication\TwoFactorAuth;

/**
 * Two Factor Authentication failed
 * @since 9.2.0
 */
class TwoFactorException extends \Exception {}
