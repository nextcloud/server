<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files;

class NotFoundException extends \Exception {
}

class NotPermittedException extends \Exception {
}

class AlreadyExistsException extends \Exception {
}

class NotEnoughSpaceException extends \Exception {
}
