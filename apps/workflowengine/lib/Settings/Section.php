<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Settings;

use OCA\WorkflowEngine\AppInfo\Application;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class Section implements IIconSection {
	/** @var IL10N */
	private $l;
	/** @var IURLGenerator */
	private $url;

	/**
	 * @param IURLGenerator $url
	 * @param IL10N $l
	 */
	public function __construct(IURLGenerator $url, IL10N $l) {
		$this->url = $url;
		$this->l = $l;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getID() {
		return 'workflow';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return $this->l->t('Flow');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPriority() {
		return 55;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getIcon() {
		return $this->url->imagePath(Application::APP_ID, 'app-dark.svg');
	}
}
