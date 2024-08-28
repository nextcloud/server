#!/bin/bash
#
# SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
#
# Set git safe.directory
git config --global --add safe.directory /var/www/html
git config --global --add safe.directory /var/www/html/3rdparty