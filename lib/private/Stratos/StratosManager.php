<?php
declare(strict_types=1);


/**
 * Stratos - above your cloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2019, Maxence Lange <maxence@artificial-owl.com>
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OC\Stratos;


use OCP\Stratos\Exceptions\StratosInstallException;
use OCP\Stratos\Helper\IStratosHelper;
use OCP\Stratos\IStratosManager;
use OCP\Stratos\Service\IStratosService;


/**
 * Class StratosManager
 *
 * @package OC\Stratos
 */
class StratosManager implements IStratosManager {


	/** @var IStratosService */
	private $stratosService;

	/** @var IStratosHelper */
	private $stratosHelper;


	/**
	 * @param IStratosService $stratosService
	 * @param IStratosHelper $stratosHelper
	 *
	 * @since 18.0.0
	 */
	public function registerStratos(IStratosService $stratosService, IStratosHelper $stratosHelper
	) {
		$this->stratosService = $stratosService;
		$this->stratosHelper = $stratosHelper;
	}


	/**
	 * @return bool
	 */
	public function isAvailable(): bool {
		try {
			$this->checkRegistration();

			return true;
		} catch (StratosInstallException $e) {
		}

		return false;
	}


	/**
	 * @return IStratosService
	 * @throws StratosInstallException
	 */
	public function getStratosService(): IStratosService {
		$this->checkRegistration();

		return $this->stratosService;
	}


	/**
	 * @return IStratosHelper
	 * @throws StratosInstallException
	 */
	public function getStratosHelper(): IStratosHelper {
		$this->checkRegistration();

		return $this->stratosHelper;
	}


	/**
	 * @throws StratosInstallException
	 */
	private function checkRegistration() {
		if ($this->stratosService === null || $this->stratosHelper === null) {
			throw new StratosInstallException('Stratos is not available. Please check the Stratos App is installed and enabled');
		}
	}

}

