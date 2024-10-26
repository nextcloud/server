#!/usr/bin/env bash
#
# SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
#

DC_IP=$1
shift

docker run --rm --name client -v /tmp/shared:/shared --dns $DC_IP --hostname client.domain.test icewind1991/samba-krb-test-client $@
