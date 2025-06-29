<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
    <NcDialog
      class="dialog"
      size="small"
      :name="t('settings', 'Add existing account')"
      out-transition
      v-on="$listeners"
    >
      <form
        id="add-existing-user-form"
        class="dialog__form"
        @submit.prevent="submit"
      >
        <NcSelect
          v-model="selectedUser"
          class="dialog__select"
          :options="possibleUsers"
          :placeholder="t('settings', 'Search accounts')"
          :user-select="true"
          :dropdown-should-open="dropdownShouldOpen"
          label="displayname"
          @search="searchUsers"
        />
        <NcSelect
          v-model="selectedGroup"
          class="dialog__select"
          :options="availableGroups"
          :placeholder="t('settings', 'Select group')"
          label="name"
          :disabled="preselectedGroup"
          :clearable="false"
        />
      </form>
      <template #actions>
        <NcButton
          class="dialog__submit"
          form="add-existing-user-form"
          type="primary"
          native-type="submit"
        >
          {{ t('settings', 'Add to group') }}
        </NcButton>
      </template>
    </NcDialog>
  </template>
  
  <script>
  import { t } from '@nextcloud/l10n'
  import NcDialog from '@nextcloud/vue/components/NcDialog'
  import NcSelect from '@nextcloud/vue/components/NcSelect'
  import NcButton from '@nextcloud/vue/components/NcButton'
  import { showError } from '@nextcloud/dialogs'
  import { confirmPassword } from '@nextcloud/password-confirmation'
  import logger from '../../logger.ts'
  
  export default {
    name: 'AddExistingUserDialog',
    components: { NcDialog, NcSelect, NcButton },
    props: {
      group: {
        type: Object,
        default: null,
      },
    },
    data() {
      return {
        possibleUsers: [],
        selectedUser: null,
        availableGroups: [],
        selectedGroup: null,
        preselectedGroup: false,
        promise: null,
      }
    },
    mounted() {
      // only subadmins see a filtered list
      this.availableGroups = this.$store.getters.getSubAdminGroups
      if (this.group) {
        this.selectedGroup = this.group
        this.preselectedGroup = true
      }
    },
    methods: {
      t,
      async searchUsers(query) {
        if (this.promise) {
          this.promise.cancel?.()
        }
        try {
          this.promise = this.$store.dispatch('searchUsers', {
            offset: 0,
            limit: 10,
            search: query,
          })
          const resp = await this.promise
          this.possibleUsers = resp?.data
            ? Object.values(resp.data.ocs.data.users)
            : []
        } catch (error) {
          logger.error(t('settings', 'Failed to search accounts'), { error })
        } finally {
          this.promise = null
        }
      },
        /**
         * Only open the dropdown once there's some text in the search-box.
         * @param {object} vm  the vue-select instance
         * @returns {boolean}
         */
        dropdownShouldOpen(vm) {
            return vm.search && vm.search.length > 0;
        },
      async submit() {
        if (!this.selectedUser || !this.selectedGroup) {
          return
        }
  
        // require the admin to re-enter their password
        try {
          await confirmPassword()
        } catch {
          showError(t('settings', 'Password confirmation is required'))
          return
        }
  
        try {
          // now that we've confirmed, call the action (it will include the
          // proper header automatically)
          await this.$store.dispatch('addUserGroup', {
            userid: this.selectedUser.id,
            gid: this.selectedGroup.id,
          })
          // if this user wasn’t in the list yet, fetch their details
          if (
            !this.$store.getters
              .getUsers.find((u) => u.id === this.selectedUser.id)
          ) {
            const resp = await this.$store.dispatch(
              'getUser',
              this.selectedUser.id
            )
            if (resp) {
              this.$store.commit('addUserData', resp)
            }
          }
          this.$emit('closing')
        } catch (error) {
          logger.error(t('settings', 'Failed to add user to group'), { error })
          showError(t('settings', 'Failed to add user to group'))
        }
      },
    },
  }
  </script>
  
  <style scoped lang="scss">
  .dialog {
    &__form {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 0 8px;
      gap: 4px 0;
    }
    &__select {
      width: 100%;
    }
    &__submit {
      margin-top: 4px;
      margin-bottom: 8px;
    }
  }
  </style>
  