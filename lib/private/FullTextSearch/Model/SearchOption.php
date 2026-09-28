<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\FullTextSearch\Model;

use JsonSerializable;
use OCP\FullTextSearch\Model\ISearchOption;

/**
 * @since 15.0.0
 *
 * Class ISearchOption
 *
 * @package OC\FullTextSearch\Model
 */
final class SearchOption implements ISearchOption, JsonSerializable {
	/**
	 *     *
	 *
	 * The array can be empty in case no search options are available.
	 * The format of the array must be like this:
	 *
	 * [
	 *   'panel' => [
	 *     'options' => [
	 *         OPTION1,
	 *         OPTION2,
	 *         OPTION3
	 *     ]
	 *   ],
	 *   'navigation' => [
	 *     'icon'    => 'css-class-of-the-icon',
	 *     'options' => [
	 *         OPTION1,
	 *         OPTION2,
	 *         OPTION3
	 *     ]
	 *   ]
	 * ]
	 *
	 * - PANEL contains entries that will be displayed in the app itself, when
	 *   a search is initiated.
	 * - NAVIGATION contains entries that will be available when using the
	 *   FullTextSearch navigation page
	 * - OPTION is an element that define each option available to the user.
	 *
	 * The format for the options must be like this:
	 *
	 * [
	 *   'name'        => 'name_of_the_option',
	 *   'title'       => 'Name displayed in the panel',
	 *   'type'        => '',
	 *   'size'        => ''   (optional),
	 *   'placeholder' => ''   (optional)
	 * ]
	 *
	 * - NAME is the variable name that is sent to the IFullTextSearchProvider
	 *   when a ISearchRequest is requested. (keys in the array returned by the
	 *   ISearchRequest->getOptions())
	 * - TYPE can be 'input' or 'checkbox'
	 * - SIZE is only used in case TYPE='input', default is 'large' but can be
	 *   'small'
	 * - PLACEHOLDER is only used in case TYPE='input', default is empty.
	 */

	/**
	 * ISearchOption constructor.
	 *
	 * Some value can be set during the creation of the object.
	 *
	 * @since 15.0.0
	 */
	public function __construct(
		private string $name = '',
		private string $title = '',
		private string $type = '',
		private string $size = '',
		private string $placeholder = '',
	) {
	}


	/**
	 * Set the name/key of the option.
	 * The string should only contain alphanumerical chars and underscore.
	 * The key can be retrieved when using ISearchRequest::getOption
	 *
	 * @see ISearchRequest::getOption
	 *
	 * @since 15.0.0
	 */
	public function setName(string $name): ISearchOption {
		$this->name = $name;

		return $this;
	}

	/**
	 * Get the name/key of the option.
	 *
	 * @since 15.0.0
	 */
	public function getName(): string {
		return $this->name;
	}


	/**
	 * Set the title/display name of the option.
	 *
	 * @since 15.0.0
	 */
	public function setTitle(string $title): ISearchOption {
		$this->title = $title;

		return $this;
	}

	/**
	 * Get the title of the option.
	 *
	 * @since 15.0.0
	 */
	public function getTitle(): string {
		return $this->title;
	}


	/**
	 * Set the type of the option.
	 * $type can be ISearchOption::CHECKBOX or ISearchOption::INPUT
	 *
	 * @since 15.0.0
	 */
	public function setType(string $type): ISearchOption {
		$this->type = $type;

		return $this;
	}

	/**
	 * Get the type of the option.
	 *
	 * @since 15.0.0
	 */
	public function getType(): string {
		return $this->type;
	}


	/**
	 * In case of Type is INPUT, set the size of the input field.
	 * Value can be ISearchOption::INPUT_SMALL or not defined.
	 *
	 * @since 15.0.0
	 */
	public function setSize(string $size): ISearchOption {
		$this->size = $size;

		return $this;
	}

	/**
	 * Get the size of the INPUT.
	 *
	 * @since 15.0.0
	 */
	public function getSize(): string {
		return $this->size;
	}

	/**
	 * In case of Type is , set the placeholder to be displayed in the input
	 * field.
	 *
	 * @since 15.0.0
	 */
	public function setPlaceholder(string $placeholder): ISearchOption {
		$this->placeholder = $placeholder;

		return $this;
	}

	/**
	 * Get the placeholder.
	 *
	 * @since 15.0.0
	 */
	public function getPlaceholder(): string {
		return $this->placeholder;
	}

	/**
	 * @since 15.0.0
	 */
	public function jsonSerialize(): array {
		return [
			'name' => $this->getName(),
			'title' => $this->getTitle(),
			'type' => $this->getType(),
			'size' => $this->getSize(),
			'placeholder' => $this->getPlaceholder()
		];
	}
}
