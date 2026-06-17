/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import Setup from './views/Setup.vue'

type Error = {
	error: string
	hint: string
}

export type DbType = 'sqlite' | 'mysql' | 'pgsql' | 'oci'

export type SetupConfig = {
	adminlogin: string
	adminpass: string
	directory: string
	dbuser: string
	dbpass: string
	dbname: string
	dbtablespace: string
	dbhost: string
	dbtype: DbType | ''

	databases: Partial<Record<DbType, string>>

	hasAutoconfig: boolean
	htaccessWorking: boolean
	serverRoot: string

	errors: string[] | Error[]
}

export type SetupLinks = {
	adminInstall: string
	adminSourceInstall: string
	adminDBConfiguration: string
}

const SetupVue = Vue.extend(Setup)
new SetupVue().$mount('#content')
