<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Integrity;

use OC\IntegrityCheck\Checker;
use OC\IntegrityCheck\Helpers\FileAccessHelper;
use OCP\IURLGenerator;
use phpseclib\Crypt\RSA;
use phpseclib\File\X509;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SignApp
 *
 * @package OC\Core\Command\Integrity
 */
class SignApp extends Command {
	public function __construct(
		private Checker $checker,
		private FileAccessHelper $fileAccessHelper,
		private IURLGenerator $urlGenerator,
	) {
		parent::__construct(null);
	}

	protected function configure() {
		$this
			->setName('integrity:sign-app')
			->setDescription('Signs an app using a private key.')
			->addOption('path', null, InputOption::VALUE_REQUIRED, 'Application to sign')
			->addOption('privateKey', null, InputOption::VALUE_REQUIRED, 'Path to private key to use for signing')
			->addOption('certificate', null, InputOption::VALUE_REQUIRED, 'Path to certificate to use for signing');
	}

	/**
	 * {@inheritdoc }
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$path = $input->getOption('path');
		$privateKeyPath = $input->getOption('privateKey');
		$keyBundlePath = $input->getOption('certificate');
		if (is_null($path) || is_null($privateKeyPath) || is_null($keyBundlePath)) {
			$documentationUrl = $this->urlGenerator->linkToDocs('developer-code-integrity');
			$output->writeln('This command requires the --path, --privateKey and --certificate.');
			$output->writeln('Example: ./occ integrity:sign-app --path="/Users/lukasreschke/Programming/myapp/" --privateKey="/Users/lukasreschke/private/myapp.key" --certificate="/Users/lukasreschke/public/mycert.crt"');
			$output->writeln('For more information please consult the documentation: ' . $documentationUrl);
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
			$this->checker->writeAppSignature($path, $x509, $rsa);
			$output->writeln('Successfully signed "' . $path . '"');
		} catch (\Exception $e) {
			$output->writeln('Error: ' . $e->getMessage());
			return 1;
		}
		return 0;
	}
}
