/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
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

/**
 * @namespace
 * @deprecated Use OCP.AppConfig instead
 */
OC.AppConfig={
	/**
	 * @deprecated Use OCP.AppConfig.getValue() instead
	 */
	getValue:function(app,key,defaultValue,callback){
		OCP.AppConfig.getValue(app, key, defaultValue, {
			success: callback
		});
	},

	/**
	 * @deprecated Use OCP.AppConfig.setValue() instead
	 */
	setValue:function(app,key,value){
		OCP.AppConfig.setValue(app, key, value);
	},

	/**
	 * @deprecated Use OCP.AppConfig.getApps() instead
	 */
	getApps:function(callback){
		OCP.AppConfig.getApps({
			success: callback
		});
	},

	/**
	 * @deprecated Use OCP.AppConfig.getKeys() instead
	 */
	getKeys:function(app,callback){
		OCP.AppConfig.getKeys(app, {
			success: callback
		});
	},

	/**
	 * @deprecated
	 */
	hasKey:function(app,key,callback){
		console.error('OC.AppConfig.hasKey is not supported anymore. Use OCP.AppConfig.getValue instead.');
	},

	/**
	 * @deprecated Use OCP.AppConfig.deleteKey() instead
	 */
	deleteKey:function(app,key){
		OCP.AppConfig.deleteKey(app, key);
	},

	/**
	 * @deprecated
	 */
	deleteApp:function(app){
		console.error('OC.AppConfig.deleteApp is not supported anymore.');
	}
};
