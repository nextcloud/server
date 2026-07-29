<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Tests\Storage;

use OCA\Files_External\ConfigLexicon;
use OCA\Files_External\Lib\Storage\AmazonS3;
use OCP\IAppConfig;
use OCP\Server;

trait CrossMountS3ConfigTrait {
	private ?bool $serverSideCopyPreviousValue = null;

	protected function requireCrossMountConfig(): void {
		if (!($this->config['run_cross_mount'] ?? false) || empty($this->config['bucket2'])) {
			$this->markTestSkipped('run_cross_mount + bucket2 not set in config.amazons3.php');
		}
	}

	/**
	 * Instantiate a peer AmazonS3 pointing at bucket2 on the same endpoint. Optional overrides
	 * merge onto the base config, letting the multipart test force `putSizeLimit`/`copySizeLimit`.
	 */
	protected function newPeerStorage(array $overrides = []): AmazonS3 {
		$this->requireCrossMountConfig();
		$peerParams = array_merge($this->config, ['bucket' => $this->config['bucket2']], $overrides);
		unset($peerParams['bucket2']);
		return new AmazonS3($peerParams);
	}

	/**
	 * MUST be called before any AmazonS3 instance is constructed because the constructor
	 * snapshots the flag once into a private property.
	 */
	protected function enableServerSideCopyFlagBeforeInstances(): void {
		$appConfig = Server::get(IAppConfig::class);
		$this->serverSideCopyPreviousValue = $appConfig->getValueBool(
			'files_external',
			ConfigLexicon::AMAZONS3_SERVER_SIDE_COPY,
			false,
		);
		$appConfig->setValueBool('files_external', ConfigLexicon::AMAZONS3_SERVER_SIDE_COPY, true);
	}

	protected function restoreServerSideCopyFlag(): void {
		if ($this->serverSideCopyPreviousValue === null) {
			return;
		}
		Server::get(IAppConfig::class)->setValueBool(
			'files_external',
			ConfigLexicon::AMAZONS3_SERVER_SIDE_COPY,
			$this->serverSideCopyPreviousValue,
		);
		$this->serverSideCopyPreviousValue = null;
	}
}
