<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\App;

use OCP\ICache;
use function simplexml_load_string;

class InfoParser {
	/**
	 * @param ICache|null $cache
	 */
	public function __construct(
		private ?ICache $cache = null,
	) {
	}

	/**
	 * @param string $file the xml file to be loaded
	 * @return null|array where null is an indicator for an error
	 */
	public function parse(string $file): ?array {
		if (!file_exists($file)) {
			return null;
		}

		if ($this->cache !== null) {
			$fileCacheKey = $file . filemtime($file);
			if ($cachedValue = $this->cache->get($fileCacheKey)) {
				return json_decode($cachedValue, true);
			}
		}

		libxml_use_internal_errors(true);
		$xml = simplexml_load_string(file_get_contents($file));

		if ($xml === false) {
			libxml_clear_errors();
			return null;
		}
		$array = $this->xmlToArray($xml);

		if (is_string($array)) {
			return null;
		}

		if (!array_key_exists('info', $array)) {
			$array['info'] = [];
		}
		if (!array_key_exists('remote', $array)) {
			$array['remote'] = [];
		}
		if (!array_key_exists('public', $array)) {
			$array['public'] = [];
		}
		if (!array_key_exists('types', $array)) {
			$array['types'] = [];
		}
		if (!array_key_exists('repair-steps', $array)) {
			$array['repair-steps'] = [];
		}
		if (!array_key_exists('install', $array['repair-steps'])) {
			$array['repair-steps']['install'] = [];
		}
		if (!array_key_exists('pre-migration', $array['repair-steps'])) {
			$array['repair-steps']['pre-migration'] = [];
		}
		if (!array_key_exists('post-migration', $array['repair-steps'])) {
			$array['repair-steps']['post-migration'] = [];
		}
		if (!array_key_exists('live-migration', $array['repair-steps'])) {
			$array['repair-steps']['live-migration'] = [];
		}
		if (!array_key_exists('uninstall', $array['repair-steps'])) {
			$array['repair-steps']['uninstall'] = [];
		}
		if (!array_key_exists('background-jobs', $array)) {
			$array['background-jobs'] = [];
		}
		if (!array_key_exists('two-factor-providers', $array)) {
			$array['two-factor-providers'] = [];
		}
		if (!array_key_exists('commands', $array)) {
			$array['commands'] = [];
		}
		if (!array_key_exists('activity', $array)) {
			$array['activity'] = [];
		}
		if (!array_key_exists('filters', $array['activity'])) {
			$array['activity']['filters'] = [];
		}
		if (!array_key_exists('settings', $array['activity'])) {
			$array['activity']['settings'] = [];
		}
		if (!array_key_exists('providers', $array['activity'])) {
			$array['activity']['providers'] = [];
		}
		if (!array_key_exists('settings', $array)) {
			$array['settings'] = [];
		}
		if (!array_key_exists('admin', $array['settings'])) {
			$array['settings']['admin'] = [];
		}
		if (!array_key_exists('admin-section', $array['settings'])) {
			$array['settings']['admin-section'] = [];
		}
		if (!array_key_exists('personal', $array['settings'])) {
			$array['settings']['personal'] = [];
		}
		if (!array_key_exists('personal-section', $array['settings'])) {
			$array['settings']['personal-section'] = [];
		}
		if (!array_key_exists('dependencies', $array)) {
			$array['dependencies'] = [];
		}
		if (!array_key_exists('backend', $array['dependencies'])) {
			$array['dependencies']['backend'] = [];
		}

		if (array_key_exists('types', $array)) {
			if (is_array($array['types'])) {
				foreach ($array['types'] as $type => $v) {
					unset($array['types'][$type]);
					if (is_string($type)) {
						$array['types'][] = $type;
					}
				}
			} else {
				$array['types'] = [];
			}
		}
		if (isset($array['repair-steps']['install']['step']) && is_array($array['repair-steps']['install']['step'])) {
			$array['repair-steps']['install'] = $array['repair-steps']['install']['step'];
		}
		if (isset($array['repair-steps']['pre-migration']['step']) && is_array($array['repair-steps']['pre-migration']['step'])) {
			$array['repair-steps']['pre-migration'] = $array['repair-steps']['pre-migration']['step'];
		}
		if (isset($array['repair-steps']['post-migration']['step']) && is_array($array['repair-steps']['post-migration']['step'])) {
			$array['repair-steps']['post-migration'] = $array['repair-steps']['post-migration']['step'];
		}
		if (isset($array['repair-steps']['live-migration']['step']) && is_array($array['repair-steps']['live-migration']['step'])) {
			$array['repair-steps']['live-migration'] = $array['repair-steps']['live-migration']['step'];
		}
		if (isset($array['repair-steps']['uninstall']['step']) && is_array($array['repair-steps']['uninstall']['step'])) {
			$array['repair-steps']['uninstall'] = $array['repair-steps']['uninstall']['step'];
		}
		if (isset($array['background-jobs']['job']) && is_array($array['background-jobs']['job'])) {
			$array['background-jobs'] = $array['background-jobs']['job'];
		}
		if (isset($array['commands']['command']) && is_array($array['commands']['command'])) {
			$array['commands'] = $array['commands']['command'];
		}
		if (isset($array['two-factor-providers']['provider']) && is_array($array['two-factor-providers']['provider'])) {
			$array['two-factor-providers'] = $array['two-factor-providers']['provider'];
		}
		if (isset($array['activity']['filters']['filter']) && is_array($array['activity']['filters']['filter'])) {
			$array['activity']['filters'] = $array['activity']['filters']['filter'];
		}
		if (isset($array['activity']['settings']['setting']) && is_array($array['activity']['settings']['setting'])) {
			$array['activity']['settings'] = $array['activity']['settings']['setting'];
		}
		if (isset($array['activity']['providers']['provider']) && is_array($array['activity']['providers']['provider'])) {
			$array['activity']['providers'] = $array['activity']['providers']['provider'];
		}
		if (isset($array['collaboration']['collaborators']['searchPlugins']['searchPlugin'])
			&& is_array($array['collaboration']['collaborators']['searchPlugins']['searchPlugin'])
			&& !isset($array['collaboration']['collaborators']['searchPlugins']['searchPlugin']['class'])
		) {
			$array['collaboration']['collaborators']['searchPlugins'] = $array['collaboration']['collaborators']['searchPlugins']['searchPlugin'];
		}
		if (isset($array['settings']['admin']) && !is_array($array['settings']['admin'])) {
			$array['settings']['admin'] = [$array['settings']['admin']];
		}
		if (isset($array['settings']['admin-section']) && !is_array($array['settings']['admin-section'])) {
			$array['settings']['admin-section'] = [$array['settings']['admin-section']];
		}
		if (isset($array['settings']['personal']) && !is_array($array['settings']['personal'])) {
			$array['settings']['personal'] = [$array['settings']['personal']];
		}
		if (isset($array['settings']['personal-section']) && !is_array($array['settings']['personal-section'])) {
			$array['settings']['personal-section'] = [$array['settings']['personal-section']];
		}
		if (isset($array['navigations']['navigation']) && $this->isNavigationItem($array['navigations']['navigation'])) {
			$array['navigations']['navigation'] = [$array['navigations']['navigation']];
		}
		if (isset($array['dependencies']['backend']) && !is_array($array['dependencies']['backend'])) {
			$array['dependencies']['backend'] = [$array['dependencies']['backend']];
		}

		// Ensure some fields are always arrays
		if (isset($array['screenshot']) && !is_array($array['screenshot'])) {
			$array['screenshot'] = [$array['screenshot']];
		}
		if (isset($array['author']) && !is_array($array['author'])) {
			$array['author'] = [$array['author']];
		}
		if (isset($array['category']) && !is_array($array['category'])) {
			$array['category'] = [$array['category']];
		}

		if ($this->cache !== null) {
			$this->cache->set($fileCacheKey, json_encode($array));
		}
		return $array;
	}

	private function isNavigationItem(array $data): bool {
		// Allow settings navigation items with no route entry
		$type = $data['type'] ?? 'link';
		if ($type === 'settings') {
			return isset($data['name']);
		}
		return isset($data['name'], $data['route']);
	}

	public function xmlToArray(\SimpleXMLElement $xml): array|string {
		$children = $xml->children();
		if ($children === null || count($children) === 0) {
			return (string)$xml;
		}

		$array = [];
		foreach ($children as $element => $node) {
			if ($element === null) {
				throw new \InvalidArgumentException('xml contains a null element');
			}
			$totalElement = count($xml->{$element});

			if (!isset($array[$element])) {
				$array[$element] = $totalElement > 1 ? [] : '';
			}
			/** @var \SimpleXMLElement $node */
			// Has attributes
			if ($attributes = $node->attributes()) {
				$data = [
					'@attributes' => [],
				];
				$converted = $this->xmlToArray($node);
				if (is_string($converted)) {
					if (!empty($converted)) {
						$data['@value'] = $converted;
					}
				} else {
					$data = array_merge($data, $converted);
				}
				foreach ($attributes as $attr => $value) {
					if ($attr === null) {
						throw new \InvalidArgumentException('xml contains a null element');
					}
					$data['@attributes'][$attr] = (string)$value;
				}

				if ($totalElement > 1) {
					$array[$element][] = $data;
				} else {
					$array[$element] = $data;
				}
				// Just a value
			} else {
				if ($totalElement > 1) {
					$array[$element][] = $this->xmlToArray($node);
				} else {
					$array[$element] = $this->xmlToArray($node);
				}
			}
		}

		return $array;
	}

	/**
	 * Select the appropriate l10n version for fields name, summary and description
	 */
	public function applyL10N(array $data, ?string $lang = null): array {
		if ($lang !== '' && $lang !== null) {
			if (isset($data['name']) && is_array($data['name'])) {
				$data['name'] = $this->findBestL10NOption($data['name'], $lang);
			}
			if (isset($data['summary']) && is_array($data['summary'])) {
				$data['summary'] = $this->findBestL10NOption($data['summary'], $lang);
			}
			if (isset($data['description']) && is_array($data['description'])) {
				$data['description'] = trim($this->findBestL10NOption($data['description'], $lang));
			}
		} elseif (isset($data['description']) && is_string($data['description'])) {
			$data['description'] = trim($data['description']);
		} else {
			$data['description'] = '';
		}

		return $data;
	}

	protected function findBestL10NOption(array $options, string $lang): string {
		// only a single option
		if (isset($options['@value'])) {
			return $options['@value'];
		}

		$fallback = $similarLangFallback = $englishFallback = false;

		$lang = strtolower($lang);
		$similarLang = $lang;
		$pos = strpos($similarLang, '_');
		if ($pos !== false && $pos > 0) {
			// For "de_DE" we want to find "de" and the other way around
			$similarLang = substr($lang, 0, $pos);
		}

		foreach ($options as $option) {
			if (is_array($option)) {
				if ($fallback === false) {
					$fallback = $option['@value'];
				}

				if (!isset($option['@attributes']['lang'])) {
					continue;
				}

				$attributeLang = strtolower($option['@attributes']['lang']);
				if ($attributeLang === $lang) {
					return $option['@value'];
				}

				if ($attributeLang === $similarLang) {
					$similarLangFallback = $option['@value'];
				} elseif (str_starts_with($attributeLang, $similarLang . '_')) {
					if ($similarLangFallback === false) {
						$similarLangFallback = $option['@value'];
					}
				}
			} else {
				$englishFallback = $option;
			}
		}

		if ($similarLangFallback !== false) {
			return $similarLangFallback;
		} elseif ($englishFallback !== false) {
			return $englishFallback;
		}
		return (string)$fallback;
	}
}
