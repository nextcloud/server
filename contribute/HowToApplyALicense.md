<!--
 - SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# How to apply a license

Originally Nextcloud was licensed under the GNU AGPLv3 only. From
June, 16 2016 on we switched to "GNU AGPLv3 or any later version" for
better long-term maintainability and to make it more secure from a
legal point of view.

Additionally Nextcloud doesn't require a CLA (Contributor License
Agreement). The copyright belongs to all the individual
contributors.

## Apply a license to a new file

If you create a new file please use a SPDX license header.
The year should then be the creation time and the email address is optional.

#### Frontend source (`.js`, `.ts`, `.css` and etc)

```js
/**
 * SPDX-FileCopyrightText: [year] [your name] [<your email address>]
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
````

or `.vue` files

```html
<!--
  - SPDX-FileCopyrightText: [year] [your name] [<your email address>]
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
```

#### Backend source (`.php`)

```php
/**
 * SPDX-FileCopyrightText: [year] [your name] [<your email address>]
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
```

## Apply a licence to an existing file

If you modify an existing file, please keep the existing license header as
it is and just add your copyright notice.
In order to do so there are two options:

* If a generic header is already present, please just add yourself to the AUTHORS.md file
* If no generic header is present, you can add yourself with a copyright line as described below

````diff
/**
 * SPDX-FileCopyrightText: 2022 Alice <alice@nextcloud.local>
 * SPDX-FileCopyrightText: 2023 Bob <bob@nextcloud.local>
+* SPDX-FileCopyrightText: [year] [your name] [<your email address>]
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
````

An example of a generic license header where adding yourself to the AUTHORS.md
file is prefered please see the example below

```
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

```

For more information on SPDX headers, please see 

* https://reuse.software/ 
* https://spdx.dev/

## DCO

Additionally we require a Developer Certificate of Origin (DCO), look
at [CONTRIBUTING.md][contributing] to learn more how to sign your commits.

[contributing]: ../.github/CONTRIBUTING.md#sign-your-work
