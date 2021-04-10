<?php

use OCP\AppFramework;

/**
 * Class BadClass - creating an instance of a blacklisted class is not allowed
 */
class BadClass implements AppFramework\IApi {
}
