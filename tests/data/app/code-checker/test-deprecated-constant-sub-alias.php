<?php

use OCP\NamespaceName as SubAlias;

/**
 * Class BadClass - creating an instance of a blacklisted class is not allowed
 */
SubAlias\ClassName::CONSTANT_NAME;
