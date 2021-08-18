<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Core\Command;

use OC_Util;
use OCP\Defaults;
use OCP\IConfig;
use OCP\Util;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Status extends Base {

	/** @var IConfig */
	private $config;
	/** @var Defaults */
	private $themingDefaults;

	public function __construct(IConfig $config, Defaults $themingDefaults) {
		parent::__construct('status');

		$this->config = $config;
		$this->themingDefaults = $themingDefaults;
	}

	protected function configure() {
		parent::configure();

		$this
			->setDescription('show some status information')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$values = [
			'installed' => $this->config->getSystemValueBool('installed', false),
			'version' => implode('.', Util::getVersion()),
			'versionstring' => OC_Util::getVersionString(),
			'edition' => '',
			'maintenance' => $this->config->getSystemValueBool('maintenance', false),
			'needsDbUpgrade' => Util::needUpgrade(),
			'productname' => $this->themingDefaults->getProductName(),
			'extendedSupport' => Util::hasExtendedSupport()
		];

		$this->writeArrayInOutputFormat($input, $output, $values);
		return 0;
	}
}
