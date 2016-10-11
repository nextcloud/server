#!/usr/bin/env bash

lang=(ach ady af_ZA ak am_ET ar ast az bal be bg_BG bn_BD bn_IN bs ca cs_CZ cy_GB da de de_AT de_DE el en_GB en@pirate eo es es_AR es_CL es_MX et_EE eu fa fi_FI fil fr fy_NL gl gu he hi hr hu_HU hy ia id io is it ja jv ka_GE km kn ko ku_IQ la lb lo lt_LT lv mg mk ml ml_IN mn mr ms_MY mt_MT my_MM nb_NO nds ne nl nn_NO nqo oc or_IN pa pl pt_BR pt_PT ro ru si_LK sk_SK sl sq sr sr@latin su sv sw_KE ta_IN ta_LK te tg_TJ th_TH tl_PH tr tzl tzm ug uk ur_PK uz vi yo zh_CN zh_HK zh_TW)

ignore=""

for fignore in "${lang[@]}"; do
  ignore=${ignore}"-not -name ${fignore}.js -not -name ${fignore}.json "
done


find ../lib/l10n -type f $ignore -delete
find ../settings/l10n -type f $ignore -delete
find ../core/l10n -type f $ignore -delete
find ../apps/files/l10n -type f $ignore -delete
find ../apps/encryption/l10n -type f $ignore -delete
find ../apps/files_external/l10n -type f $ignore -delete
find ../apps/files_sharing/l10n -type f $ignore -delete
find ../apps/files_trashbin/l10n -type f $ignore -delete
find ../apps/files_versions/l10n -type f $ignore -delete
find ../apps/user_ldap/l10n -type f $ignore -delete
find ../apps/user_webdavauth/l10n -type f $ignore -delete


