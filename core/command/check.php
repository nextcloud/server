<?php

namespace OC\Core\Command;

use OCP\IConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Check extends Command {
	/**
	 * @var IConfig
	 */
	private $config;

	public function __construct(IConfig $config) {
		parent::__construct();
		$this->config = $config;
	}

	protected function configure() {
		$this
			->setName('check')
			->setDescription('check dependencies of the server environment')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$errors = \OC_Util::checkServer($this->config);
		if (!empty($errors)) {
			$errors = array_map( function($items) {
				return (string)$items['error'];
			}, $errors);
			echo json_encode($errors);
			return 1;
		}
		return 0;
	}
}
