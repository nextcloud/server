<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Template\PublicTemplateResponse;
use Test\TestCase;

class PublicTemplateResponseTest extends TestCase {
	public function testSetParamsConstructor() {
		$template = new PublicTemplateResponse('app', 'home', ['key' => 'value']);
		$this->assertContains('core/js/public/publicpage', \OC_Util::$scripts);
		$this->assertEquals(['key' => 'value'], $template->getParams());
	}

	public function testAdditionalElements() {
		$template = new PublicTemplateResponse('app', 'home', ['key' => 'value']);
		$template->setHeaderTitle('Header');
		$template->setHeaderDetails('Details');
		$this->assertEquals(['key' => 'value'], $template->getParams());
		$this->assertEquals('Header', $template->getHeaderTitle());
		$this->assertEquals('Details', $template->getHeaderDetails());
	}

	public function testActionSingle() {
		$actions = [
			new Http\Template\SimpleMenuAction('link', 'Download', 'download', 'downloadLink', 0)
		];
		$template = new PublicTemplateResponse('app', 'home', ['key' => 'value']);
		$template->setHeaderActions($actions);
		$this->assertEquals(['key' => 'value'], $template->getParams());
		$this->assertEquals($actions[0], $template->getPrimaryAction());
		$this->assertEquals(1, $template->getActionCount());
		$this->assertEquals([], $template->getOtherActions());
	}


	public function testActionMultiple() {
		$actions = [
			new Http\Template\SimpleMenuAction('link1', 'Download1', 'download1', 'downloadLink1', 100),
			new Http\Template\SimpleMenuAction('link2', 'Download2', 'download2', 'downloadLink2', 20),
			new Http\Template\SimpleMenuAction('link3', 'Download3', 'download3', 'downloadLink3', 0)
		];
		$template = new PublicTemplateResponse('app', 'home', ['key' => 'value']);
		$template->setHeaderActions($actions);
		$this->assertEquals(['key' => 'value'], $template->getParams());
		$this->assertEquals($actions[2], $template->getPrimaryAction());
		$this->assertEquals(3, $template->getActionCount());
		$this->assertEquals([$actions[1], $actions[0]], $template->getOtherActions());
	}


	public function testGetRenderAs() {
		$template = new PublicTemplateResponse('app', 'home', ['key' => 'value']);
		$this->assertContains('core/js/public/publicpage', \OC_Util::$scripts);
		$this->assertEquals(['key' => 'value'], $template->getParams());
		$this->assertEquals('public', $template->getRenderAs());
	}
}
