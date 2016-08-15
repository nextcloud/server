<?php

namespace Tests\Core\Templates;

class TemplatesTest extends \Test\TestCase {

	public function test403() {
		$template = \OC::$SERVERROOT . '/core/templates/403.php';
		$expectedHtml = "<ul><li class='error'>\n\t\tAccess forbidden<br><p class='hint'></p></li></ul>";
		$this->assertTemplate($expectedHtml, $template);
	}

	public function test404() {
		$template = \OC::$SERVERROOT . '/core/templates/404.php';
		$href = \OC::$server->getURLGenerator()->linkTo('', 'index.php');
		$expectedHtml = "<ul><li class='error'>\n\t\t\tFile not found<br><p class='hint'>The specified document has not been found on the server.</p>\n<p class='hint'><a href='$href'>You can click here to return to Nextcloud.</a></p>\n\t\t</li></ul>";
		$this->assertTemplate($expectedHtml, $template);
	}

}
