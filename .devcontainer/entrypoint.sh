#!/bin/bash
#
# SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
#
set -e

sudo service apache2 start

# Keep the devcontainer alive independently of Apache so developers can
# restart services without tearing down the workspace container.
exec sleep infinity
