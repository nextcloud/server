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

	/** Encryption mode of the connection, pgsql only */
	dbsslmode: string
	/** Path to the CA certificate the database server is verified against */
	dbsslca: string
	/** Path to the client certificate used to authenticate against the database */
	dbsslcert: string
	/** Path to the private key of the client certificate */
	dbsslkey: string
	/** Path to the certificate revocation list, pgsql only */
	dbsslcrl: string
	/**
	 * Skip verifying that the server certificate matches the host, mysql only.
	 * A string when reflected back from a submitted form, as checkboxes are submitted by value.
	 */
	dbsslnoverify: boolean | string

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
