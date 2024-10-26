'use strict'

/**
 * Party inspired by https://github.com/FormidableLabs/webpack-stats-plugin
 *
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: MIT
 */

const { constants } = require('node:fs')
const fs = require('node:fs/promises')
const path = require('node:path')
const webpack = require('webpack')

class WebpackSPDXPlugin {

	#options

	/**
	 * @param {object} opts Parameters
	 * @param {Record<string, string>} opts.override Override licenses for packages
	 */
	constructor(opts = {}) {
		this.#options = { override: {}, ...opts }
	}

	apply(compiler) {
		compiler.hooks.thisCompilation.tap('spdx-plugin', (compilation) => {
			// `processAssets` is one of the last hooks before frozen assets.
			// We choose `PROCESS_ASSETS_STAGE_REPORT` which is the last possible
			// stage after which to emit.
			compilation.hooks.processAssets.tapPromise(
				{
					name: 'spdx-plugin',
					stage: compilation.constructor.PROCESS_ASSETS_STAGE_REPORT,
				},
				() => this.emitLicenses(compilation),
			)
		})
	}

	/**
	 * Find the nearest package.json
	 * @param {string} dir Directory to start checking
	 */
	async #findPackage(dir) {
		if (!dir || dir === '/' || dir === '.') {
			return null
		}

		const packageJson = `${dir}/package.json`
		try {
			await fs.access(packageJson, constants.F_OK)
		} catch (e) {
			return await this.#findPackage(path.dirname(dir))
		}

		const { private: isPrivatePacket, name } = JSON.parse(await fs.readFile(packageJson))
		// "private" is set in internal package.json which should not be resolved but the parent package.json
		// Same if no name is set in package.json
		if (isPrivatePacket === true || !name) {
			return (await this.#findPackage(path.dirname(dir))) ?? packageJson
		}
		return packageJson
	}

	/**
	 * Emit licenses found in compilation to '.license' files
	 * @param {webpack.Compilation} compilation Webpack compilation object
	 * @param {*} callback Callback for old webpack versions
	 */
	async emitLicenses(compilation, callback) {
		const logger = compilation.getLogger('spdx-plugin')
		// cache the node packages
		const packageInformation = new Map()

		const warnings = new Set()
		/** @type {Map<string, Set<webpack.Chunk>>} */
		const sourceMap = new Map()

		for (const chunk of compilation.chunks) {
			for (const file of chunk.files) {
				if (sourceMap.has(file)) {
					sourceMap.get(file).add(chunk)
				} else {
					sourceMap.set(file, new Set([chunk]))
				}
			}
		}

		for (const [asset, chunks] of sourceMap.entries()) {
			/** @type {Set<webpack.Module>} */
			const modules = new Set()
			/**
			 * @param {webpack.Module} module
			 */
			const addModule = (module) => {
				if (module && !modules.has(module)) {
					modules.add(module)
					for (const dep of module.dependencies) {
						addModule(compilation.moduleGraph.getModule(dep))
					}
				}
			}
			chunks.forEach((chunk) => chunk.getModules().forEach(addModule))

			const sources = [...modules].map((module) => module.identifier())
				.map((source) => {
					const skipped = [
						'delegated',
						'external',
						'container entry',
						'ignored',
						'remote',
						'data:',
					]
					// Webpack sources that we can not infer license information or that is not included (external modules)
					if (skipped.some((prefix) => source.startsWith(prefix))) {
						return ''
					}
					// Internal webpack sources
					if (source.startsWith('webpack/runtime')) {
						return require.resolve('webpack')
					}
					// Handle webpack loaders
					if (source.includes('!')) {
						return source.split('!').at(-1)
					}
					if (source.includes('|')) {
						return source
							.split('|')
							.filter((s) => s.startsWith(path.sep))
							.at(0)
					}
					return source
				})
				.filter((s) => !!s)
				.map((s) => s.split('?', 2)[0])

			// Skip assets without modules, these are emitted by webpack plugins
			if (sources.length === 0) {
				logger.warn(`Skipping ${asset} because it does not contain any source information`)
				continue
			}

			/** packages used by the current asset
			 * @type {Set<string>}
			 */
			const packages = new Set()

			// packages is the list of packages used by the asset
			for (const sourcePath of sources) {
				const pkg = await this.#findPackage(path.dirname(sourcePath))
				if (!pkg) {
					logger.warn(`No package for source found (${sourcePath})`)
					continue
				}

				if (!packageInformation.has(pkg)) {
					// Get the information from the package
					const { author: packageAuthor, name, version, license: packageLicense, licenses } = JSON.parse(await fs.readFile(pkg))
					// Handle legacy packages
					let license = !packageLicense && licenses
						? licenses.map((entry) => entry.type ?? entry).join(' OR ')
						: packageLicense
					if (license?.includes(' ') && !license?.startsWith('(')) {
						license = `(${license})`
					}
					// Handle both object style and string style author
					const author = typeof packageAuthor === 'object'
						? `${packageAuthor.name}` + (packageAuthor.mail ? ` <${packageAuthor.mail}>` : '')
						: packageAuthor ?? `${name} developers`

					packageInformation.set(pkg, {
						version,
						// Fallback to directory name if name is not set
						name: name ?? path.basename(path.dirname(pkg)),
						author,
						license,
					})
				}
				packages.add(pkg)
			}

			let output = 'This file is generated from multiple sources. Included packages:\n'
			const authors = new Set()
			const licenses = new Set()
			for (const packageName of [...packages].sort()) {
				const pkg = packageInformation.get(packageName)
				const license = this.#options.override[pkg.name] ?? pkg.license
				// Emit warning if not already done
				if (!license && !warnings.has(pkg.name)) {
					logger.warn(`Missing license information for package ${pkg.name}, you should add it to the 'override' option.`)
					warnings.add(pkg.name)
				}
				licenses.add(license || 'unknown')
				authors.add(pkg.author)
				output += `- ${pkg.name}\n\t- version: ${pkg.version}\n\t- license: ${license}\n`
			}
			output = `\n\n${output}`
			for (const author of [...authors].sort()) {
				output = `SPDX-FileCopyrightText: ${author}\n${output}`
			}
			for (const license of [...licenses].sort()) {
				output = `SPDX-License-Identifier: ${license}\n${output}`
			}

			compilation.emitAsset(
				asset.split('?', 2)[0] + '.license',
				new webpack.sources.RawSource(output),
			)
		}

		if (callback) {
			return callback()
		}
	}

}

module.exports = WebpackSPDXPlugin
