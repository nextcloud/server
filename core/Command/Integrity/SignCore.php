<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Integrity;

use OC\IntegrityCheck\Checker;
use OC\IntegrityCheck\Helpers\FileAccessHelper;
use phpseclib\Crypt\RSA;
use phpseclib\File\X509;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SignCore
 *
 * @package OC\Core\Command\Integrity
 */
class SignCore extends Command {
	public function __construct(
		private Checker $checker,
		private FileAccessHelper $fileAccessHelper,
	) {
		parent::__construct(null);
	}

	protected function configure() {
		$this
			->setName('integrity:sign-core')
			->setDescription('Sign core using a private key.')
			->addOption('privateKey', null, InputOption::VALUE_REQUIRED, 'Path to private key to use for signing')
			->addOption('certificate', null, InputOption::VALUE_REQUIRED, 'Path to certificate to use for signing')
			->addOption('path', null, InputOption::VALUE_REQUIRED, 'Path of core to sign');
	}

	/**
	 * {@inheritdoc }
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$privateKeyPath = $input->getOption('privateKey');
		$keyBundlePath = $input->getOption('certificate');
		$path = $input->getOption('path');
		if (is_null($privateKeyPath) || is_null($keyBundlePath) || is_null($path)) {
			$output->writeln('--privateKey, --certificate and --path are required.');
			return 1;
		}

		$privateKey = $this->fileAccessHelper->file_get_contents($privateKeyPath);
		$keyBundle = $this->fileAccessHelper->file_get_contents($keyBundlePath);

		if ($privateKey === false) {
			$output->writeln(sprintf('Private key "%s" does not exists.', $privateKeyPath));
			return 1;
		}

		if ($keyBundle === false) {
			$output->writeln(sprintf('Certificate "%s" does not exists.', $keyBundlePath));
			return 1;
		}

		$rsa = new RSA();
		$rsa->loadKey($privateKey);
		$x509 = new X509();
		$x509->loadX509($keyBundle);
		$x509->setPrivateKey($rsa);

		try {
			$this->checker->writeCoreSignature($x509, $rsa, $path);
			$output->writeln('Successfully signed "core"');
		} catch (\Exception $e) {
			$output->writeln('Error: ' . $e->getMessage());
			return 1;
		}
		return 0;
	}
}
