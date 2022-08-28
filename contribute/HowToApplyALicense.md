# How to apply a license

Originally Nextcloud was licensed under the GNU AGPLv3 only. From
June, 16 2016 on we switched to "GNU AGPLv3 or any later version" for
better long-term maintainability and to make it more secure from a
legal point of view.

Additionally Nextcloud doesn't require a CLA (Contributor License
Agreement). The copyright belongs to all the individual
contributors.

## Apply a license to a new file

If you create a new file please use a license header

#### Frontend source (`.js`, `.ts`, `.css` and etc)

```js
/**
 * @copyright Copyright (c) <year>, <your name> (<your email address>)
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
````

or `.vue` files

```html
<!--
  - @copyright Copyright (c) <year>, <your name> (<your email address>)
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
-->
```

#### Backend source (`.php`)

```php
/**
 * @copyright Copyright (c) <year>, <your name> (<your email address>)
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
```

## Apply a licence to an existing file

If you modify an existing file, please keep the existing license header as
it is and just add your copyright notice, for example:

````diff
/**
 * @copyright Copyright (c) 2022, Alice (alice@nextcloud.local)
 * @copyright Copyright (c) 2023, Bob (bob@nextcloud.local)
+* @copyright Copyright (c) <year>, <your name> (<your email address>) 
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
````

## DCO

Additionally we require a Developer Certificate of Origin (DCO), look
at [CONTRIBUTING.md][contributing] to learn more how to sign your commits.

[contributing]: ../.github/CONTRIBUTING.md#sign-your-work
