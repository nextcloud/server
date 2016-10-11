<?php

use OCP\AppFramework as OAF;

/**
 * Class BadClass - creating an instance of a blacklisted class is not allowed
 */
class BadClass implements OAF\IApi {
}
