#!/bin/bash

## ###########################################################################
## Companion script bash-add-alias.sh will install / invoke this script.
##
## bash-add-alias.sh or the user can copy this file to either
##	/etc/bash_completion.d/
## or
##	~/.local/share/bash-completion/completions/
##  so that it can get processed each time a new bash session is run.
##
## If copied to /etc/bash_completion.d/, then
## ensure it is owned by root:root (chown -v root:root thisFileName
## Also, only root can edit it: (chmod -v 0644 thisFileName)
##
## You may need to install bash-completion package, i.e.:
##		sudo apt install bash-completion
##
## @author Ronald Barnes
## @copyright Copyright 2022, Ronald Barnes ron@ronaldbarnes.ca
## ###########################################################################
##
## NOTE: for best bash_completion experience, add the following lines to
## ~/.inputrc or /etc/inputrc:
## from "man readline":
set colored-completion-prefix on
## set colored-stats on





## Get list of options beginning with "-", i.e. --help, --verbose, etc.:
function _occ_get_opts_list()
	{
	## Check if passed name of app to run to get app-specific options:
	local appName
	if [[ $# -gt 0 ]] ; then
		appName=${1}
	fi

	## Read the \n-delimited list into an array:
	while read -r value; do
		## Don't display option if already on command line:
		## Use loop; word expansion via [@] does partial matches:
		local X foundOpt="no"
		for (( X=1 ; X<((${#COMP_WORDS[@]}-1)) ; X++ )) ; do
			if [[ ${COMP_WORDS[$X]} == ${value} ]] ; then
				foundOpt="yes"
			fi
		done
		## Don't add CWORD, it can be just "-":
		if [[ ${foundOpt} == "no" && ${value} != ${CWORD} ]] ; then
			opts_list+=("${value}")
		fi

		## Read output of "occ" and get word-chars following "-" or "--":
		done <<< $(eval ${occ_alias_name} ${appName} |
			grep --extended-regex --only-matching " \-(\-|\w)+"
			)
	## Verbose settings hard to parse: i.e. "-v|vv|vvv, --verbose"
	## Just manually add -vv and -vvv, and place in order (after -v):
	## Note: `complete` built-in handles de-duplication and sortation
	opts_list+=("-vv")
	opts_list+=("-vvv")
	}





## Get arguments and options for specific app, i.e. files:scan
function _occ_get_apps_args_and_opts()
	{
	local occCommand getOpts="no" length
	if [[ $# -eq 0 ]] ; then
		## Expecting app to run
		return 99
	else
		occCommand="${@}"
	fi

	## Check if user is asking for opts; normally excluded:
	if [[ $CWORD =~ "-" ]] ; then
		getOpts="yes"
		## If a command was passed, it cannot END with "--", that means
		## "end of options, next is input" in bash.
		## So, strip "-" or "--" if they're CWORD:
		if [[ $CWORD =~ "-"$ ]] ; then
			occCommand=${@%%-*}
		fi
	fi

	local value
	local args args_list
	local opts opts_list

	## Get all output into one var:
	args=$(eval ${occ_alias_name} ${occCommand} --help --raw 2>&1)
	## Save a copy and avoid running occ twice (once for args, once for opts):
	opts=args

	if [[ ${args} =~ Arguments: && $getOpts == no ]] ; then
		## Remove to and including Arguments: header:
		args=${args#*Arguments:}
		## Remove Options: header to end of list:
		args=${args%%Options:*}
		## Found arguments, read them to array:
		while read -r value ; do
			## Strip trailing blanks:
			value=${value%% *}
			## Skip blank lines:
			if [[ ${value} =~ [:punct::alnum:]+ ]] ; then
				args_list+=("${value}")
			fi
		done <<< ${args}

		## If an arg is "app", get list of all apps,
		if [[ ${args_list[@]} =~ "app" ]] ; then
			## Put list of apps into array for completing next input
			_occ_get_apps_list
		fi

		## If an arg is "user_id", get list of all users:
		## Note: occ dav:list-calendars expects uid, not user_id!
		if [[ ${args_list[@]} =~ "user_id"
			|| ${args_list[@]} =~ "uid" ]] ; then
			## Put list of users into array for completing next input
			_occ_get_users_list
		fi

		## if an arg is "name", get list of all config setting names:
		if [[ ${args_list[@]} =~ "name" ]] ; then
			## Put list of users into array for completing next input
			_occ_get_setting_names
		fi

		## if an arg is "file", let `readline` defaults handle it:
		if [[ ${args_list[@]} =~ "file" ]] ; then
			compgen_skip="yes"
		fi

		## if an arg is "lang", get list of all languages FOR CHOSEN APP:
		if [[ ${args_list[@]} =~ "lang" ]] ; then
			## Put list of languages into array for completing next input
			_occ_get_languages_list
		fi
	fi

	if [[ $getOpts == yes ]] ; then
		_occ_get_opts_list "${occCommand} --help"
		display_args_arr+=(${opts_list[@]})
	fi
	}



## Put list of users into array for completing next input
function _occ_get_users_list()
	{
	local value
	while read -r value ; do
		## Strip trailing characters after and including colon:
		value=${value%%:*}
		## Strip leading blanks:
		value=${value##* }
		## Don't include a user already on the command line:
		if [[ ! ${COMP_WORDS[@]} =~ ${value} ]] ; then
			display_args_arr+=("${value}")
		fi
	done <<< $(eval ${occ_alias_name} user:list)
	}



## Put list of apps into array for completing next input
function _occ_get_apps_list()
	{
	local args value
	## Only repopulate apps list if not done already in this instance:
	if [[ -z ${occ_apps_list} ]] ; then
		occ_apps_list=$(eval ${occ_alias_name} app:list)
	fi
	## If Previous WORD was an app name, don't reload apps as completion options:
	if [[ ! ${occ_apps_list[@]} =~ $PWORD ]] ; then
		while read -r value ; do
			## Strip trailing characters after and including colon:
			value=${value%%:*}
			## Strip leading blanks:
			value=${value##* }
			## Skip "Enabled" and "Disabled" section headers:
			if [[ ! (${value} == "Enabled" || ${value} == "Disabled") ]] ; then
				display_args_arr+=("${value}")
			fi
		done <<< ${occ_apps_list}
	fi
	}



## Put list of setting names into array for completing next input
function _occ_get_setting_names()
	{
	local value skip_until_app="yes" app_name=${PWORD} space_count=4

	## If last word is "--value", just return: no competion options to give:
	if [[ ${PWORD} == --value ]] ; then
		display_args_arr=()
		return
	fi

	## Do not expect app name if, i.e. "config.system" has been typed:
	if [[ ${PWORD} =~ "config:system" ]] ; then
		skip_until_app="no"
		app_name="system"
	fi

	## If occ config:system:set is 3 words ago (and Current WORD is blank),
	## only valid option is --value=:
	## i.e. occ config:system:set mail_domain [tab][tab]
	##                length-3      length-2   length-1
	##
	local pword3=${#COMP_WORDS[@]}
	pword3=$((pword3-3))
	##
	if [[ ( ${COMP_WORDS[$pword3]} == "config:system:set"
			||  ${COMP_WORDS[$((pword3-1))]} == "config:app:set" )
			&& $CWORD == "" ]] ; then
		display_args_arr=("--value")
		return

	## If occ config:app:get, then sub-options are 6 spaces from beginning:
	elif [[ ${COMP_WORDS[$pword3]} == config:app:get ]] ; then
		space_count=6
		skip_until_app="no"

	## If occ config:app:set, then sub-options are 6 spaces from beginning:
	elif [[ ${COMP_WORDS[$pword3]} == config:app:set ]] ; then
		space_count=4,
		skip_until_app="yes"
		space_count=6
		skip_until_app="no"
	fi

	while read -r value ; do
		## Strip trailing characters after and including colon:
		value=${value%%:*}
		## Strip leading blanks:
		value=${value##* }
		## Check if we've reached section header for app named $PWORD
		if [[ ${value} == ${PWORD} ]] ; then
			skip_until_app="no"
		## Skip until we've reached section header for app named $PWORD:
		## OR, a preceding app isn't necessary if config:system is in effect:
		elif [[ ${skip_until_app} == "no" ]] ; then
			display_args_arr+=("${value}")
		fi
	done <<< $(eval ${occ_alias_name} config:list ${app_name} --output plain |
		grep --extended-regex --only-matching "^ {$space_count}- [[:alnum:]_.-]+"
		)
	}




## If an app expects "lang" arg (language), then present completion list of
## file system entries so they can locate xx_YY.php
function _occ_get_languages_list()
	{
	## NOTE: l18n:createjs <app> <lang> expects:
	## app: an app name from app:list
	## lang: a PHP file, which user must provide:
	##	PHP translation file </var/www/nextcloud/apps/spreed/l10n/es_MX.php>
	##	does not exist.
	##
	## Nothing can be done until PWORD is an app.

	## If we have an apps list and the Previous WORD is in it...
	if [[ -n ${occ_apps_list} &&  ${occ_apps_list} =~ ${PWORD} ]] ; then
		## Allow for file system completions:
		compgen_skip="yes"
	fi
	}














## This runs the completions for "occ" command, which is an alias to:
##	sudo -u $web_server_user php occ
function _occ()
	{
	## When >1 NC installations, each should have own alias pointing to own
	## installation directory.
	## When fetching, say, app list, it's critical that correct alias invoked
	## i.e. Alias `occ` calls main NC v24 & alias `occbclug` calls NC v25
	occ_alias_name=${1}
#echo -e "_occ() occ_alias_name=\"${occ_alias_name}\" "

	## Word being inspected (CWORD==Current WORD, COMP_CWORD is index):
	local CWORD=${COMP_WORDS[COMP_CWORD]}
	## Previous Word being inspected (PWORD==Previous WORD):
	local PWORD=${COMP_WORDS[COMP_CWORD-1]}

	## Change word break chars to exclude ":" since occ uses as separator:
	COMP_WORDBREAKS="${COMP_WORDBREAKS/:/}"

	## Default is word list, but files:scan, etc. require file name,
	## allow readline to handle that, per
	## 'https://stackoverflow.com/questions/12933362/getting-compgen-to-include-slashes-on-directories-when-looking-for-files/19062943#19062943' 
	compgen_skip="no"
	compopt +o default
	## Put spaces after completed words:
	compopt +o nospace


	## Put the options to present to user into this:
	declare -a display_args_arr

	## Parse out options (all start with "-" or "--"):
	declare -a opts_list

	## List of all args returned by running "occ", minus options that begin
	## with "-":
	declare -a occ_args_arr

	## temp storage to parse to array:
	local occ_args

	## List of apps available is used in multiple locations,
	## populate once, globally:
	occ_apps_list=""

	## Gather all valid args reported by running "occ":
	while read -r occ_args ; do
		if [[ ${occ_args} != command && ${occ_args:0:1} != - ]]; then
			## "command" is section heading, not valid arg:
			## Skip switches that begin with "-":
			occ_args_arr+=("${occ_args}")
		fi
	done <<< $(eval ${occ_alias_name} --raw 2>&1|
		## Starts with alpha-numerics, can include colons, hyphens, _s:
		grep --extended-regex --only-matching	"^[[:alnum:]:_-]+"
		)

	## If we're expecing a file path, let readline defaults handle it:
	if [[ $CWORD == -p || $PWORD =~ "--path" || $CWORD =~ "--path" ]] ; then
		compgen_skip="yes"
	## If ANY PREVIOUS word has colon, it's a command, run it and get its
	## arguments and options:
	elif [[ ( ${COMP_WORDS[@]:1:((${#COMP_WORDS[@]}-1))} =~ : )
			## Ensure that above condition does not include current word,
			## which can happen if index 0 == occ and index 1 == files:scan
			## Also, ensure we don't handle --path= here:
			&& ! ( ${CWORD} =~ : || ${PWORD} =~ = ) ]] ; then
		local length=${#COMP_WORDS[@]}-1
		_occ_get_apps_args_and_opts "${COMP_WORDS[@]:1:length}"
	## Check for hyphen / dash / "-" as first char, if so, get options list:
	elif [[	${CWORD:0:1} == - ]] ; then
		_occ_get_opts_list
		display_args_arr=${opts_list[@]}
	## If this is first word (after "occ"), shorten list of valid choices
	## by stripping everything after first ":"
	## There can easily be 180+ options otherwise!
	## Note: there's initially an empty second array element after "occ":
	## Test for incomplete word by seeking colons:
	elif [[ ! ${CWORD} =~ : ]]; then
		## Use associative array to get *unique* values:
	 	declare -A short_list_args_arr
		local X
		for (( X=0 ; X<${#occ_args_arr[@]} ; X++ )); do
			## Here's the find/replace of colon -> end-of-line with just colon (:):
			local value="${occ_args_arr[X]/:*/:}"
			## Here's the de-duplication via associate array:
			short_list_args_arr[$value]=$value
		done
		display_args_arr=${short_list_args_arr[@]}
		## Since ":" is valid, and requires further sub-args, add NO SPACEs to end:
		compopt -o nospace
	else
		## So far, line consists of "occ ..." i.e. something more than "occ"
		## See what matches CWORD
		local X
		for (( X=0 ; X<${#occ_args_arr[@]} ; X++ )); do
			## Add only args that match current word:
			if [[ ${occ_args_arr[X]} =~ ^${CWORD} ]] ; then
				local value="${occ_args_arr[X]}"
				display_args_arr+=("${value}")
			fi
		done
	fi


	local candidates=$(
		compgen -W "${display_args_arr[*]}" -- "${CWORD}"
		)
	## When no matches, or an option takes a list of files, support
	## readline's default behaviour of completing files/directories:
	if [ ${#candidates[@]} -eq 0 ] || [ "${compgen_skip}" == "yes" ]; then
		compopt -o default
		COMPREPLY=()
	else
		COMPREPLY=($(printf '%s' "${candidates[@]}"))
	fi

	unset occ_apps_list
	}

complete -F _occ occ
## If there are multiple NC instances, then edit the
##	~/.bashrc or ~/.bash_aliases file
## and duplicate the alias that `bash-add-alias.sh` created so each has
## a unique name.
## Then, copy the `complete -F _occ` from above and finish it with the other
## alias name for each additional alias.
## Then, source the alias script (`. ~/.bash_aliases` or `source ~/.bashrc`).
## Then, source this script (`. occ.bash` or `source occ.bash`).
