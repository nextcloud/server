<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\TemplateResponse;

class TemplateResponseTest extends \Test\TestCase {
	/**
	 * @var \OCP\AppFramework\Http\TemplateResponse
	 */
	private $tpl;

	protected function setUp(): void {
		parent::setUp();

		$this->tpl = new TemplateResponse('app', 'home');
	}


	public function testSetParamsConstructor(): void {
		$params = ['hi' => 'yo'];
		$this->tpl = new TemplateResponse('app', 'home', $params);

		$this->assertEquals(['hi' => 'yo'], $this->tpl->getParams());
	}


	public function testSetRenderAsConstructor(): void {
		$renderAs = 'myrender';
		$this->tpl = new TemplateResponse('app', 'home', [], $renderAs);

		$this->assertEquals($renderAs, $this->tpl->getRenderAs());
	}


	public function testSetParams(): void {
		$params = ['hi' => 'yo'];
		$this->tpl->setParams($params);

		$this->assertEquals(['hi' => 'yo'], $this->tpl->getParams());
	}


	public function testGetTemplateName(): void {
		$this->assertEquals('home', $this->tpl->getTemplateName());
	}

	public function testGetRenderAs(): void {
		$render = 'myrender';
		$this->tpl->renderAs($render);
		$this->assertEquals($render, $this->tpl->getRenderAs());
	}

	public function testChainability(): void {
		$params = ['hi' => 'yo'];
		$this->tpl->setParams($params)
			->setStatus(Http::STATUS_NOT_FOUND);

		$this->assertEquals(Http::STATUS_NOT_FOUND, $this->tpl->getStatus());
		$this->assertEquals(['hi' => 'yo'], $this->tpl->getParams());
	}
}
