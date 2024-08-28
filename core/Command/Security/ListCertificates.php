<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Security;

use OC\Core\Command\Base;
use OCP\ICertificate;
use OCP\ICertificateManager;
use OCP\IL10N;
use OCP\L10N\IFactory as IL10NFactory;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCertificates extends Base {
	protected IL10N $l;

	public function __construct(
		protected ICertificateManager $certificateManager,
		IL10NFactory $l10nFactory,
	) {
		parent::__construct();
		$this->l = $l10nFactory->get('core');
	}

	protected function configure() {
		$this
			->setName('security:certificates')
			->setDescription('list trusted certificates');
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$outputType = $input->getOption('output');
		if ($outputType === self::OUTPUT_FORMAT_JSON || $outputType === self::OUTPUT_FORMAT_JSON_PRETTY) {
			$certificates = array_map(function (ICertificate $certificate) {
				return [
					'name' => $certificate->getName(),
					'common_name' => $certificate->getCommonName(),
					'organization' => $certificate->getOrganization(),
					'expire' => $certificate->getExpireDate()->format(\DateTimeInterface::ATOM),
					'issuer' => $certificate->getIssuerName(),
					'issuer_organization' => $certificate->getIssuerOrganization(),
					'issue_date' => $certificate->getIssueDate()->format(\DateTimeInterface::ATOM)
				];
			}, $this->certificateManager->listCertificates());
			if ($outputType === self::OUTPUT_FORMAT_JSON) {
				$output->writeln(json_encode(array_values($certificates)));
			} else {
				$output->writeln(json_encode(array_values($certificates), JSON_PRETTY_PRINT));
			}
		} else {
			$table = new Table($output);
			$table->setHeaders([
				'File Name',
				'Common Name',
				'Organization',
				'Valid Until',
				'Issued By'
			]);

			$rows = array_map(function (ICertificate $certificate) {
				return [
					$certificate->getName(),
					$certificate->getCommonName(),
					$certificate->getOrganization(),
					$this->l->l('date', $certificate->getExpireDate()),
					$certificate->getIssuerName()
				];
			}, $this->certificateManager->listCertificates());
			$table->setRows($rows);
			$table->render();
		}
		return 0;
	}
}
