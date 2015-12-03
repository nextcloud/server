<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;

require __DIR__ . '/../../vendor/autoload.php';


/**
 * Features context.
 */
class FeatureContext implements Context, SnippetAcceptingContext {
	use BasicStructure;
	use Provisioning;
	use Sharing;
	use WebDav;
}
