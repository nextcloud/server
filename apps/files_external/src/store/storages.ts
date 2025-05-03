import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import type { IStorage } from '../types'

export const useStorages = defineStore('files_external--storages', {
	state() {
		return {
			globalStorages: [] as IStorage[],
			userStorages: [] as IStorage[],
		}
	},

	getters: {
		allStorages(state) {
			return [...state.globalStorages, state.userStorages]
		},
	},

	actions: {
		async loadGlobalStorages() {
			const url = 'apps/files_external/globalstorages'
			const { data } = await axios.get<IStorage[]>(generateUrl(url))

			this.globalStorages = data
		},
	},
	/* result = Object.values(result);
				var onCompletion = jQuery.Deferred();
				var $rows = $();
				result.forEach(function(storageParams) {
					storageParams.mountPoint = (storageParams.mountPoint === '/')? '/' : storageParams.mountPoint.substr(1); // trim leading slash
					var storageConfig = new self._storageConfigClass();
					_.extend(storageConfig, storageParams);
					var $tr = self.newStorage(storageConfig, onCompletion, true);

					// don't recheck config automatically when there are a large number of storages
					if (result.length < 20) {
						self.recheckStorageConfig($tr);
					} else {
						self.updateStatus($tr, StorageConfig.Status.INDETERMINATE, t('files_external', 'Automatic status checking is disabled due to the large number of configured storages, click to check status'));
					}
					$rows = $rows.add($tr);
				});
				initApplicableUsersMultiselect($rows.find('.applicableUsers'), this._userListLimit);
				self.$el.find('tr#addMountPoint').before($rows);
				onCompletion.resolve();
				onLoaded2.resolve();
			}
	}, */
})
