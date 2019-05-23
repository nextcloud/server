<!--
  - @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<Multiselect class="sharing-input" :searchable="true" :loading="loading" :internal-search="false" :options="options" :limit="3" :placeholder="inputPlaceholder" :hide-selected="true" :multiple="true" :taggable="true" @search-change="asyncFind" @tag="addShare" />
</template>

<script>
import axios from 'nextcloud-axios'
import { generateOcsUrl } from 'nextcloud-router'
import Multiselect from 'nextcloud-vue/dist/Components/Multiselect'
import Config from '../services/ConfigService'
import Share from '../models/Share'

export default {
	name:  'SharingInput',

	components: {
		Multiselect
	},

	props: {
		shares: {
			type: Array,
			default: () => [],
			required: true
		},
		fileInfo: {
			type: Object,
			default: () => {},
			required: true
		},
		reshare: {
			type: Share,
			default: false
		}
	},

	data() {
		return {
			options: [],
			loading: false,
			config: new Config()
		}
	},

	computed: {
		inputPlaceholder() {
			const allowRemoteSharing = this.config.isRemoteShareAllowed;
			const allowMailSharing = this.config.isMailShareAllowed;

			if (!allowRemoteSharing && allowMailSharing) {
				return t('files_sharing', 'Name or email address...');
			}
			if (allowRemoteSharing && !allowMailSharing) {
				return t('files_sharing', 'Name or federated cloud ID...');
			}
			if (allowRemoteSharing && allowMailSharing) {
				return t('files_sharing', 'Name, federated cloud ID or email address...');
			}

			return 	t('files_sharing', 'Name...');
		}
	},

	mounted() {
		this.getRecommendations()
	},

	methods: {
		async asyncFind(query) {
			console.info(query);	
		},
		addShare(data) {
			console.info(data);
		},
		async getRecommendations() {
			const request = await axios.get(generateOcsUrl('apps/files_sharing/api/v1') + 'sharees_recommended', {
				params: {
					format: 'json',
					itemType: this.fileInfo.type
				}
			})
			const result = request.data
			if (result.ocs.meta.statuscode === 100) {
				var filter = (users, groups, remotes, remote_groups, emails, circles, rooms) => {
					if (typeof(emails) === 'undefined') {
						emails = [];
					}
					if (typeof(circles) === 'undefined') {
						circles = [];
					}
					if (typeof(rooms) === 'undefined') {
						rooms = [];
					}

					var usersLength;
					var groupsLength;
					var remotesLength;
					var remoteGroupsLength;
					var emailsLength;
					var circlesLength;
					var roomsLength;

					var i, j;

					//Filter out the current user
					usersLength = users.length;
					for (i = 0; i < usersLength; i++) {
						if (users[i].value.shareWith === OC.currentUser) {
							users.splice(i, 1);
							break;
						}
					}

					// Filter out the owner of the share
					if (this.reshare) {
						usersLength = users.length;
						for (i = 0 ; i < usersLength; i++) {
							if (users[i].value.shareWith === this.reshare.owner) {
								users.splice(i, 1);
								break;
							}
						}
					}

					var sharesLength = this.shares.length;

					// Now filter out all sharees that are already shared with
					for (i = 0; i < sharesLength; i++) {
						var share = this.shares[i];

						if (share.share_type === OC.Share.SHARE_TYPE_USER) {
							usersLength = users.length;
							for (j = 0; j < usersLength; j++) {
								if (users[j].value.shareWith === share.share_with) {
									users.splice(j, 1);
									break;
								}
							}
						} else if (share.share_type === OC.Share.SHARE_TYPE_GROUP) {
							groupsLength = groups.length;
							for (j = 0; j < groupsLength; j++) {
								if (groups[j].value.shareWith === share.share_with) {
									groups.splice(j, 1);
									break;
								}
							}
						} else if (share.share_type === OC.Share.SHARE_TYPE_REMOTE) {
							remotesLength = remotes.length;
							for (j = 0; j < remotesLength; j++) {
								if (remotes[j].value.shareWith === share.share_with) {
									remotes.splice(j, 1);
									break;
								}
							}
						} else if (share.share_type === OC.Share.SHARE_TYPE_REMOTE_GROUP) {
							remoteGroupsLength = remote_groups.length;
							for (j = 0; j < remoteGroupsLength; j++) {
								if (remote_groups[j].value.shareWith === share.share_with) {
									remote_groups.splice(j, 1);
									break;
								}
							}
						} else if (share.share_type === OC.Share.SHARE_TYPE_EMAIL) {
							emailsLength = emails.length;
							for (j = 0; j < emailsLength; j++) {
								if (emails[j].value.shareWith === share.share_with) {
									emails.splice(j, 1);
									break;
								}
							}
						} else if (share.share_type === OC.Share.SHARE_TYPE_CIRCLE) {
							circlesLength = circles.length;
							for (j = 0; j < circlesLength; j++) {
								if (circles[j].value.shareWith === share.share_with) {
									circles.splice(j, 1);
									break;
								}
							}
						} else if (share.share_type === OC.Share.SHARE_TYPE_ROOM) {
							roomsLength = rooms.length;
							for (j = 0; j < roomsLength; j++) {
								if (rooms[j].value.shareWith === share.share_with) {
									rooms.splice(j, 1);
									break;
								}
							}
						}
					}
				};

				filter(
					result.ocs.data.exact.users,
					result.ocs.data.exact.groups,
					result.ocs.data.exact.remotes,
					result.ocs.data.exact.remote_groups,
					result.ocs.data.exact.emails,
					result.ocs.data.exact.circles,
					result.ocs.data.exact.rooms
				);

				var exactUsers   = result.ocs.data.exact.users;
				var exactGroups  = result.ocs.data.exact.groups;
				var exactRemotes = result.ocs.data.exact.remotes || [];
				var exactRemoteGroups = result.ocs.data.exact.remote_groups || [];
				var exactEmails = [];
				if (typeof(result.ocs.data.emails) !== 'undefined') {
					exactEmails = result.ocs.data.exact.emails;
				}
				var exactCircles = [];
				if (typeof(result.ocs.data.circles) !== 'undefined') {
					exactCircles = result.ocs.data.exact.circles;
				}
				var exactRooms = [];
				if (typeof(result.ocs.data.rooms) !== 'undefined') {
					exactRooms = result.ocs.data.exact.rooms;
				}

				var exactMatches = exactUsers.concat(exactGroups).concat(exactRemotes).concat(exactRemoteGroups).concat(exactEmails).concat(exactCircles).concat(exactRooms);

				filter(
					result.ocs.data.users,
					result.ocs.data.groups,
					result.ocs.data.remotes,
					result.ocs.data.remote_groups,
					result.ocs.data.emails,
					result.ocs.data.circles,
					result.ocs.data.rooms
				);

				var users   = result.ocs.data.users;
				var groups  = result.ocs.data.groups;
				var remotes = result.ocs.data.remotes || [];
				var remoteGroups = result.ocs.data.remote_groups || [];
				var lookup = result.ocs.data.lookup || [];
				var emails = [];
				if (typeof(result.ocs.data.emails) !== 'undefined') {
					emails = result.ocs.data.emails;
				}
				var circles = [];
				if (typeof(result.ocs.data.circles) !== 'undefined') {
					circles = result.ocs.data.circles;
				}
				var rooms = [];
				if (typeof(result.ocs.data.rooms) !== 'undefined') {
					rooms = result.ocs.data.rooms;
				}

				var suggestions = exactMatches.concat(users).concat(groups).concat(remotes).concat(remoteGroups).concat(emails).concat(circles).concat(rooms).concat(lookup);

				function dynamicSort(property) {
					return function (a,b) {
						var aProperty = '';
						var bProperty = '';
						if (typeof a[property] !== 'undefined') {
							aProperty = a[property];
						}
						if (typeof b[property] !== 'undefined') {
							bProperty = b[property];
						}
						return (aProperty < bProperty) ? -1 : (aProperty > bProperty) ? 1 : 0;
					}
				}

				/**
				 * Sort share entries by uuid to properly group them
				 */
				var grouped = suggestions.sort(dynamicSort('uuid'));

				var previousUuid = null;
				var groupedLength = grouped.length;
				var results = [];
				/**
				 * build the result array that only contains all contact entries from
				 * merged contacts, if the search term matches its contact name
				 */
				for (var i = 0; i < groupedLength; i++) {
					if (typeof grouped[i].uuid !== 'undefined' && grouped[i].uuid === previousUuid) {
						grouped[i].merged = true;
					}
					if (typeof grouped[i].merged === 'undefined') {
						results.push(grouped[i]);
					}
					previousUuid = grouped[i].uuid;
				}

				console.info(results, exactMatches);
			}
		}
	}
}
</script>
  
<style lang="scss">
.sharing-input {
	width: 100%;
	margin: 5px 0;
}
</style>