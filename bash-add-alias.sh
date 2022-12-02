#!/bin/bash

## ###########################################################################
## Creates an alias to run `occ` as the appropriate web server user
##
## Optionally adds the alias to user's .bash_aliases file
## Optionally adds the alias to SUDO_USER's .bash_aliases file
## Verifies `occ` is owned by web server user
## Optionally copies bash completion script `complete.occ` to
##	/etc/bash_completion.d/
##
## @copyright Copyright © 2022, Ronald Barnes (ron@ronaldbarnes.ca)
## ###########################################################################


## Dubugging:
## set -x
## Errors exit immediately:
## set -e
## Catch undefined vars:
set -u


## Define colours:
function define_colours()
	{
	green="\e[1;32m"
	yellow='\e[1;33m'
	red='\e[1;31m'
	default_colour="\e[00m"
	}


## Leave no trace after exit (except alias(es)):
function cleanup_vars()
	{
	unset value
	unset httpdUser
	unset user_name
	unset home_dir
	unset aliasExists
	unset phpFound
	unset answer
	unset aliasString
	unset addAlias
	unset aliasExists

	unset searchHttpdUser
	unset occOwner
	unset occPath
	unset getOccPath
	unset script_found

	unset green
	unset yellow
	unset red
	unset default_colour

	## Reset all trap signals:
	trap - SIGINT
	trap - SIGHUP
	trap - SIGTERM
	trap - SIGKILL
	trap - EXIT
	trap - QUIT
	## If param was passed, i.e. "ALL", cleanup EVERYTHING, we're done:
	if [[ ${#@} -ge 1 ]]; then
		trap - RETURN
		unset cleanup_vars
		## Reset unbound var checking, else i.e. bash completion breaks, etc.
		set +u
		unset define_colours
	fi
	}

function searchHttpdUser()
	{
	if [[ $# -eq 0 ]] ; then
		## Expecting user name to search for
		return 99
	else
		searchUser=${1}
	fi

	## Fetch possible httpd users (for sudo -u ...) into array:
	##
	## Params: regex / string of name(s) to search for
	while read value; do
		## Normal, indexed array:
		httpdUser+=($value)
	done <<< $(grep													  \
		--extended-regex												\
		--ignore-case														\
		--only-matching													\
		--max-count=1														\
		"${searchUser}" /etc/passwd							\
		)
	}




function getOccPath()
	{
	read -ep "Path to file 'occ': " -i "/" occPath
	if [[ ! -f ${occPath} ]] ; then
		getOccPath
	fi
	}


function bash_aliases()
	{
	if [[ $# -eq 0 ]] ; then
		## Expecting path to .bash_aliases directory
		return 99
	else
		home_dir=${1}
	fi

	grep --no-messages "occ" ${home_dir}/.bash_aliases
	aliasExists=$?
	if [[ aliasExists -eq 0 ]]; then
		echo "There is an \"occ\" alias in ${home_dir}.bash_aliases:"
		grep "occ" ${home_dir}/.bash_aliases
	elif [[ -w ${home_dir}/.bash_aliases ]]; then
		echo -en "Add alias to ${yellow}${home_dir}/.bash_aliases${default_colour}?"
		read -s -p " (y/N) " -n 1 answer
		if [[ ${answer} =~ ^Y|y ]] ; then
			echo "Y"
			echo "${aliasString}" >> ${home_dir}/.bash_aliases
			answer=$?
			if [[ ${answer} -eq 0 ]] ; then
				echo -ne "${green}Success${default_colour}: "
				grep occ ${home_dir}/.bash_aliases
			fi
		else
			echo "N"
		fi
	else
		echo -ne "${yellow}NOTICE${default_colour}: Cannot access "
		echo -e "${home_dir}/.bash_aliases"
	fi
	}




trap 'cleanup_vars ALL' RETURN EXIT QUIT SIGINT SIGKILL SIGTERM


## Handy red / yellow / green / default colour defs:
define_colours

## Store web server user name(s) from /etc/passwd as indexed array:
declare -a httpdUser

## Find the web server user name:
searchHttpdUser "httpd|www-data|nginx|lighthttpd"
if [ ${#httpdUser[0]} -eq 0 ] ; then
	## No standard httpd user found, try "nobody":
	searchHttpdUser "nobody"
fi


if [ ${#httpdUser[0]} -eq 0 ] ; then
	echo -e "${red}ERROR${default_colour}: No web server user found."
	## kill -s SIGINT $$
	return 1
else
	echo -ne "Web server user name: "
	echo -e "\"${green}${httpdUser[0]}${default_colour}\"."
fi


## Looks for existing occ alias:
occPath=""
alias occ 2>/dev/null
aliasExists=$?
## USER=root, HOME=/root, SUDO_USER=me: 
## user_name=${SUDO_USER:-$USER}
user_name=${USER:-$SUDO_USER}
if [ ${aliasExists} -eq 0 ] ; then
	echo "Alias for occ found for user \"${user_name}\"."
	aliasString=$(alias occ)
	occPath=${aliasString##* }
else
	echo "Alias for occ command not found for user \"${user_name}\"."
	which php 2>&1 > /dev/null
	phpFound=$?
	if [ $phpFound -ne 0 ]; then
		echo -e "${red}ERROR${default_colour}: php not found in path."
		kill -s SIGKILL $$
	fi
	occPath="$(pwd)/occ"
	if [[ -f ${occPath} ]] ; then
		occPath=$(pwd)/occ
	else
		echo "Can't find \"occ\", not in current directory."
		getOccPath
	fi
	occOwner=$(stat --format="%U" ${occPath})
	if [[ ${occOwner} != ${httpdUser[0]} ]] ; then
		echo -e "${red}ERROR${default_colour}: Owner of occ is not web server user:"
		echo "	${occOwner} != ${httpdUser}"
		## kill -s SIGKILL $$
		kill -s SIGINT $$
		trap - RETURN
		return 99
	fi

	aliasString="occ='sudo --user ${httpdUser} php ${occPath}'"
	echo -ne "Run \"${yellow}alias ${green}${aliasString}${default_colour}\""
	read -s -p " (y/N)? " -n 1 answer
	if [[ ${answer} =~ ^[Yy] ]] ; then
		echo "Y"
		eval alias "${aliasString}"
		alias occ
##	elif [[ ${answer} != "" ]] ; then
	else
		echo "N"
	fi
fi

## Is there an occ alias in ~/.bash_aliases?
bash_aliases $HOME
home_dir=""
if [[ "${SUDO_USER}" != "" ]] ; then
	## Find user-who-ran-sudo's home directory:
	home_dir=$(grep ${SUDO_USER} /etc/passwd)
	## Strip off colon->end-of-line
	home_dir=${home_dir%:*}
	## Strip off start-of-line->last colon:
	home_dir=${home_dir##*:}
	if [[ "$HOME" != "${home_dir}" ]] ; then
		bash_aliases ${home_dir}
	fi
fi


## Run complete.occ to handle bash auto completion?
script_found=1	## aka False
## Strip "occ" from occPath:
occPath=${occPath%/*}
if [[ -f ${occPath}/complete.occ ]] ; then
	script_found=0
	echo -en "Run bash completion script "
	echo -en "${green}complete.occ${default_colour}? "
	read -sp " (Y/n) " -N 1 answer
	if [[ ${answer} =~ ^[Nn] ]] ; then
		echo "N"
	else
		echo "Y"
		echo -n "Running ${occPath}/complete.occ ... "
		## Do not run cleanup_vars() when complete.occ returns:
		trap - RETURN
		source ${occPath}/complete.occ
		## Reset trap:
		trap 'cleanup_vars ALL' RETURN
		status=$?
		if [[ ${status} -eq 0 ]] ; then
			echo -e "${green}success${default_colour}."
		else
			echo -e "${red}Error${default_colour}."
		fi
	fi
fi


## Does `complete.occ` exist in /etc/bash_completion.d/?
## Does `complete.occ` exist in /usr/share/bash-completion/completions/?
## If neither, add it to /etc/bash_completion.d/?
if [[ -d /etc/bash_completion.d ]] ; then
	if [[ -f /etc/bash_completion.d/complete.occ ]] ; then
		script_found=0
		echo "Found existing complete.occ in /etc/bash_completion.d/"
	else
		if [[ -f ${occPath}/complete.occ ]] ; then
			echo -en "Add ${yellow}complete.occ${default_colour} "
			echo -en "to /etc/bash_completion.d?"
			read -sp " (y/N) " -n 1 answer
			if [[ ${answer} =~ ^[Yy] ]] ; then
				echo "Y"
					cp -v complete.occ /etc/bash_completion.d
				if [[ $? -ne 0 ]] ; then
					echo -ne "${red}ERROR${default_colour}: Could not "
					echo -e "copy complete.occ to /etc/bash_completion.d/"
				else
					## Copy successful, set owner and permissions:
					chown -v root:root /etc/bash_completion.d/complete.occ
					chmod -v 0644 /etc/bash_completion.d/complete.occ
				fi
			else
				echo "N"
			fi
		fi
	fi
fi

		

## Cannot remove trap on RETURN inside a return trap catch, so do it here:
trap - RETURN
## Now clean all vars and remove all traps
cleanup_vars ALL
trap -p RETURN
echo "DONE."
