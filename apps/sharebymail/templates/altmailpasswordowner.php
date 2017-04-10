<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
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

/** @var OC_Theme $theme */
/** @var array $_ */
print_unescaped($l->t("Hey there,\n\nyou just shared »%s« with %s.\nThe share was already send to the recipient. Due to the security policies\neach share needs to be protected by email and it is not allowed to send the\npassword directly by mail to the recipient. Therefore you need to forward\nthe password manually to the recipient.\n\nThis is the password: %s\n\nYou can chose a different password at any time in the share dialog.\n\n", [$_['filename'], $_['recipient'], $_['password']]));
// TRANSLATORS term at the end of a mail
p($l->t("Cheers!"));
print_unescaped("\n");
?>

	--
<?php p($theme->getName() . ' - ' . $theme->getSlogan()); ?>
<?php print_unescaped("\n".$theme->getBaseUrl());
