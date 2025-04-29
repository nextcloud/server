<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Http\Template;

use InvalidArgumentException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IInitialStateService;

/**
 * Class PublicTemplateResponse
 *
 * @since 14.0.0
 * @template H of array<string, mixed>
 * @template S of Http::STATUS_*
 * @template-extends TemplateResponse<Http::STATUS_*, array<string, mixed>>
 */
class PublicTemplateResponse extends TemplateResponse {
	private $headerTitle = '';
	private $headerDetails = '';
	/** @var IMenuAction[] */
	private $headerActions = [];
	private $footerVisible = true;

	/**
	 * PublicTemplateResponse constructor.
	 *
	 * @param string $appName
	 * @param string $templateName
	 * @param array $params
	 * @param S $status
	 * @param H $headers
	 * @since 14.0.0
	 */
	public function __construct(
		string $appName,
		string $templateName,
		array $params = [],
		$status = Http::STATUS_OK,
		array $headers = [],
	) {
		parent::__construct($appName, $templateName, $params, 'public', $status, $headers);
		\OCP\Util::addScript('core', 'public-page-menu');
		\OCP\Util::addScript('core', 'public-page-user-menu');

		$state = \OCP\Server::get(IInitialStateService::class);
		$state->provideLazyInitialState('core', 'public-page-menu', function () {
			$response = [];
			foreach ($this->headerActions as $action) {
				// First try in it is a custom action that provides rendered HTML
				$rendered = $action->render();
				if ($rendered === '') {
					// If simple action, add the response data
					if ($action instanceof SimpleMenuAction) {
						$response[] = $action->getData();
					}
				} else {
					// custom action so add the rendered output
					$response[] = [
						'id' => $action->getId(),
						'label' => $action->getLabel(),
						'html' => $rendered,
					];
				}
			}
			return $response;
		});
	}

	/**
	 * @param string $title
	 * @since 14.0.0
	 */
	public function setHeaderTitle(string $title) {
		$this->headerTitle = $title;
	}

	/**
	 * @return string
	 * @since 14.0.0
	 */
	public function getHeaderTitle(): string {
		return $this->headerTitle;
	}

	/**
	 * @param string $details
	 * @since 14.0.0
	 */
	public function setHeaderDetails(string $details) {
		$this->headerDetails = $details;
	}

	/**
	 * @return string
	 * @since 14.0.0
	 */
	public function getHeaderDetails(): string {
		return $this->headerDetails;
	}

	/**
	 * @param array $actions
	 * @since 14.0.0
	 * @throws InvalidArgumentException
	 */
	public function setHeaderActions(array $actions) {
		foreach ($actions as $action) {
			if ($actions instanceof IMenuAction) {
				throw new InvalidArgumentException('Actions must be of type IMenuAction');
			}
			$this->headerActions[] = $action;
		}
		usort($this->headerActions, function (IMenuAction $a, IMenuAction $b) {
			return $a->getPriority() <=> $b->getPriority();
		});
	}

	/**
	 * @return IMenuAction
	 * @since 14.0.0
	 * @throws \Exception
	 */
	public function getPrimaryAction(): IMenuAction {
		if ($this->getActionCount() > 0) {
			return $this->headerActions[0];
		}
		throw new \Exception('No header actions have been set');
	}

	/**
	 * @return int
	 * @since 14.0.0
	 */
	public function getActionCount(): int {
		return count($this->headerActions);
	}

	/**
	 * @return IMenuAction[]
	 * @since 14.0.0
	 */
	public function getOtherActions(): array {
		return array_slice($this->headerActions, 1);
	}

	/**
	 * @since 14.0.0
	 */
	public function setFooterVisible(bool $visible = false) {
		$this->footerVisible = $visible;
	}

	/**
	 * @since 14.0.0
	 */
	public function getFooterVisible(): bool {
		return $this->footerVisible;
	}

	/**
	 * @return string
	 * @since 14.0.0
	 */
	public function render(): string {
		$params = array_merge($this->getParams(), [
			'template' => $this,
		]);
		$this->setParams($params);
		return parent::render();
	}
}
