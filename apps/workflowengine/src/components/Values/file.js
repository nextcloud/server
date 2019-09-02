/*
 * @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import './../../legacy/filesystemtagsplugin'
import './../../legacy/requesttimeplugin'
import './../../legacy/requesturlplugin'
import './../../legacy/requestuseragentplugin'
import './../../legacy/usergroupmembershipplugin'

import FileMimeType from './FileMimeType';

const FileChecks = Object.values(OCA.WorkflowEngine.Plugins).map((plugin) => {
	if (plugin.component) {
		return { ...plugin.getCheck(), component: plugin.component() }
	}
	return plugin.getCheck()
})


// new way of registering checks

const validateRegex = function(string) {
	var regexRegex = /^\/(.*)\/([gui]{0,3})$/
	var result = regexRegex.exec(string)
	return result !== null
}

const validateIPv4 = function(string) {
	var regexRegex = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\/(3[0-2]|[1-2][0-9]|[1-9])$/
	var result = regexRegex.exec(string)
	return result !== null
}

const validateIPv6 = function(string) {
	var regexRegex = /^(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))\/(1([01][0-9]|2[0-8])|[1-9][0-9]|[0-9])$/
	var result = regexRegex.exec(string)
	return result !== null
}

const stringValidator = (check) => {
	if (check.operator === 'matches' || check.operator === '!matches') {
		return validateRegex(check.value)
	}
	return true
}


FileChecks.push({
	class: 'OCA\\WorkflowEngine\\Check\\FileName',
	name: t('workflowengine', 'File name'),
	operators: [
		{ operator: 'is', name: t('workflowengine', 'is') },
		{ operator: '!is', name: t('workflowengine', 'is not') },
		{ operator: 'matches', name: t('workflowengine', 'matches') },
		{ operator: '!matches', name: t('workflowengine', 'does not match') }
	],
	placeholder: (check) => {
		if (check.operator === 'matches' || check.operator === '!matches') {
			return '/^dummy-.+$/i'
		}
		return 'filename.txt'
	},
	validate: stringValidator
})

FileChecks.push({
	class: 'OCA\\WorkflowEngine\\Check\\FileMimeType',
	name: t('workflowengine', 'File MIME type'),
	operators: [
		{ operator: 'is', name: t('workflowengine', 'is') },
		{ operator: '!is', name: t('workflowengine', 'is not') },
		{ operator: 'matches', name: t('workflowengine', 'matches') },
		{ operator: '!matches', name: t('workflowengine', 'does not match') }
	],
	component: FileMimeType
})

FileChecks.push({
	class: 'OCA\\WorkflowEngine\\Check\\FileSize',
	name: t('workflowengine', 'File size (upload)'),
	operators: [
		{ operator: 'less', name: t('workflowengine', 'less') },
		{ operator: '!greater', name: t('workflowengine', 'less or equals') },
		{ operator: '!less', name: t('workflowengine', 'greater or equals') },
		{ operator: 'greater', name: t('workflowengine', 'greater') }
	],
	placeholder: (check) => '5 MB',
	validate: (check) => check.value.match(/^[0-9]+[ ]?[kmgt]?b$/i) !== null
})

FileChecks.push({
	class: 'OCA\\WorkflowEngine\\Check\\RequestRemoteAddress',
	name: t('workflowengine', 'Request remote address'),
	operators: [
		{ operator: 'matchesIPv4', name: t('workflowengine', 'matches IPv4') },
		{ operator: '!matchesIPv4', name: t('workflowengine', 'does not match IPv4') },
		{ operator: 'matchesIPv6', name: t('workflowengine', 'matches IPv6') },
		{ operator: '!matchesIPv6', name: t('workflowengine', 'does not match IPv6') }
	],
	placeholder: (check) => {
		if (check.operator === 'matchesIPv6' || check.operator === '!matchesIPv6') {
			return '::1/128';
		}
		return '127.0.0.1/32'
	},
	validate: (check) => {
		if (check.operator === 'matchesIPv6' || check.operator === '!matchesIPv6') {
			return validateIPv6(check.value)
		}
		return validateIPv4(check.value)
	}
})

export default FileChecks
