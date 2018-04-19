<?php
/**
 * @copyright Copyright (c) <2018>, <Patrik Novotný> (<patrik.novotny@gmx.com>)
 *
 * @author Patrik Novotný <patrik.novotny@gmx.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Files\Mount;

use OCP\Files\Config\IMountProvider;
use OCP\Files\Storage\IStorageFactory;
use OCP\IConfig;
use OCP\IUser;

/**
 * Mount provider for custom appdata storages
 */
class AppdataMountProvider implements IMountProvider {
    /**
     * @var IConfig
     */
    private $config;

    /**
     * AppdataMountProvider constructor.
     *
     * @param IConfig $config
     */
    public function __construct(IConfig $config) {
        $this->config = $config;
    }

    /**
     * Get the appdata mount for a user
     *
     * @param IUser $user
     * @param IStorageFactory $loader
     * @return \OCP\Files\Mount\IMountPoint[]
     */
    public function getMountsForUser(IUser $user, IStorageFactory $loader) {
        $appdataPath = $this->config->getSystemValue('appdata_path', '');
        if ($appdataPath !== '') {
            return [new MountPoint('\OC\Files\Storage\Local', '/appdata.local', ['datadir' => $appdataPath])];
        } else {
            return [];
        }
    }
}
