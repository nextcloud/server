/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* eslint-disable */
import escapeHTML from 'escape-html'

(function(OC) {
	/**
	 * @namespace
	 */
	OC.SystemTags = {
		/**
		 *
		 * @param {OC.SystemTags.SystemTagModel|Object|String} tag
		 * @returns {HTMLElement}
		 */
		getDescriptiveTag: function(tag) {
			if (_.isUndefined(tag.name) && !_.isUndefined(tag.toJSON)) {
				tag = tag.toJSON()
			}

			var $span = document.createElement('span')

			if (_.isUndefined(tag.name)) {
				$span.classList.add('non-existing-tag')
				$span.textContent = t('core', 'Non-existing tag #{tag}', {
						tag: tag
				})
				return $span
			}

			$span.textContent = escapeHTML(tag.name)

			var scope
			if (!tag.userAssignable) {
				scope = t('core', 'Restricted')
			}
			if (!tag.userVisible) {
				// invisible also implicitly means not assignable
				scope = t('core', 'Invisible')
			}
			if (scope) {
				var $scope = document.createElement('em')
				$scope.textContent = ' (' + scope + ')'
				$span.appendChild($scope)
			}
			return $span
		}
	}
})(OC)
