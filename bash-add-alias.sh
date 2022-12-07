#!/bin/bash

## ###########################################################################
## Creates an alias to run `occ` as the appropriate web server user
##
## Optionally adds the alias to user's .bash_aliases file
## Optionally adds the alias to SUDO_USER's .bash_aliases file
## If neither user has ~/.bash_aliases, optionally add to /etc/bash.bashrc
##
## Verifies `config/config.php` is owned by web server user
## Optionally copies bash completion script `complete.occ` to
##	/etc/bash_completion.d/
##
## @copyright Copyright © 2022, Ronald Barnes (ron@ronaldbarnes.ca)
## ###########################################################################
##
## Usage:
## . bash-add-alias.sh
## or:
## source bash-add-alias.sh
## ###########################################################################


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
	unset -v value
	unset -v httpdUser
	unset -v user_name
	unset -v home_dir
	unset -v aliasExists
	unset -v phpFound
	unset -v answer
	unset -v aliasString
	unset -v addAlias
	unset -v aliasExists

	unset -f searchHttpdUser
	unset -v occOwner
	unset -v occPath
	unset -f getOccPath
	unset -v script_found
	unset -v script_installed
	unset -v alias_installed

	unset -v green
	unset -v yellow
	unset -v red
	unset -v default_colour

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
		unset -f cleanup_vars
		## Reset unbound var checking, else i.e. bash completion breaks, etc.
		set +u
		unset -f define_colours
		unset -f bash_aliases
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
	if [[ $# -ne 2 ]] ; then
		## Expecting path and file, i.e. ~ and .bash_aliases
		return 99
	else
		local home_dir=${1}
		local alias_file=${2}
	fi

	if [[ ! -r ${1}/${2} ]] ; then
		return
	fi
	grep --quiet --no-messages "occ" ${home_dir}/${alias_file}
	aliasExists=$?
	if [[ aliasExists -eq 0 ]]; then
		echo "There is an \"occ\" alias in ${home_dir}/${alias_file}:"
		grep "occ" ${home_dir}/${alias_file}
		alias_installed=0
	elif [[ -w ${home_dir}/${alias_file} ]]; then
		echo -en "Add alias to ${yellow}${home_dir}/${alias_file}${default_colour}?"
		read -s -p " (y/N) " -n 1 answer
		if [[ ${answer} =~ ^Y|y ]] ; then
			echo "Y"
			echo "${aliasString}" >> ${home_dir}/${alias_file}
			answer=$?
			if [[ ${answer} -eq 0 ]] ; then
				echo -ne "${green}Success${default_colour}: "
				grep occ ${home_dir}/${alias_file}
				alias_installed=0
			fi
		else
			echo "N"
		fi
	fi
	}




trap 'cleanup_vars ALL' RETURN EXIT QUIT SIGINT SIGKILL SIGTERM


## Handy red / yellow / green / default colour defs:
define_colours

## Store web server user name(s) from /etc/passwd as indexed array:
declare -a httpdUser

## Find the web server user name:
## More added per
## https://docs.nextcloud.com/server/22/admin_manual/configuration_server/occ_command.html#http-user-label
searchHttpdUser "httpd|www-data|nginx|lighttpd|apache|http|wwwrun"
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
	occOwner=$(stat --format="%U" ${occPath%/*}/config/config.php)
	if [[ ${occOwner} != ${httpdUser[0]} ]] ; then
		echo -en "${red}ERROR${default_colour}: Owner of "
		echo -en "${yellow}config/config.php${default_colour} "
		echo "is not web server user:"
		echo "	${occOwner} != ${httpdUser}"
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
	else
		echo "N"
	fi
fi

## Is there an occ alias in ~/.bash_aliases?
alias_installed=1
bash_aliases $HOME ".bash_aliases"
home_dir=""
if [[ "${SUDO_USER}" != "" ]] ; then
	## Find user-who-ran-sudo's home directory:
	home_dir=$(grep ${SUDO_USER} /etc/passwd)
	## Strip off colon->end-of-line
	home_dir=${home_dir%:*}
	## Strip off start-of-line->last colon:
	home_dir=${home_dir##*:}
	if [[ "$HOME" != "${home_dir}" ]] ; then
		bash_aliases ${home_dir} .bash_aliases
	fi
fi

## If no alias installed into any ~/.bash_aliases file, try ~/.bashrc:
if [[ $alias_installed -ne 0 ]] ; then
	## Try SUDO_USER's home dir, if not defined, use $HOME:
	bash_aliases ${home_dir:-HOME} .bashrc
fi
## Also offer to add alias to /etc/bash.bashrc since ~/.bash_aliases unlikely
## to exist and user may want this option for global alias:
## bash_aliases "/etc" "bash.bashrc"



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
script_installed=1
if [[ -d /etc/bash_completion.d ]] ; then
	if [[ -f /etc/bash_completion.d/complete.occ ]] ; then
		script_installed=0
		echo "Found existing complete.occ in /etc/bash_completion.d/"
	else
		if [[ -f ${occPath}/complete.occ ]] ; then
			echo -en "Copy ${yellow}complete.occ${default_colour} "
			echo -en "to ${yellow}/etc/bash_completion.d/occ${default_colour}?"
			read -sp " (y/N) " -n 1 answer
			if [[ ${answer} =~ ^[Yy] ]] ; then
				echo "Y"
				cp --verbose			\
					--archive				\
					--preserve=all	\
					--interactive		\
					complete.occ /etc/bash_completion.d/occ
				if [[ $? -ne 0 ]] ; then
					echo -ne "${red}ERROR${default_colour}: Could not "
					echo -e "copy complete.occ to /etc/bash_completion.d/"
					script_installed=-1
				else
					script_installed=0
					## Copy successful, set owner and permissions:
					chown -v root:root /etc/bash_completion.d/occ
					chmod -v 0644 /etc/bash_completion.d/occ
				fi
			else
				echo "N"
			fi
		fi
	fi
fi

## If /etc/bash_completion.d does not exist, or user declined to copy
## script to that location, offer to copy to local user storage:
if [[ script_installed -ne 0 ]] ; then
	echo -en "Copy ${yellow}complete.occ${default_colour} to "
	echo -en "${yellow}~/.local/share/bash-completion/completions/occ"
	echo -en "${default_colour}?"
	read -sp " (y/N) " -n 1 answer
	if [[ ${answer} =~ ^[Yy] ]] ; then
		echo "Y"
		mkdir -vp ~/.local/share/bash-completion/completions
		## File name MUST have name of command / alias it operates on when
		## it is in this location:
		cp --verbose			\
			--archive				\
			--preserve=all	\
			--interactive		\
			complete.occ ~/.local/share/bash-completion/completions/occ
	else
		echo "N"
	fi
fi




## Cannot remove trap on RETURN inside a return trap catch, so do it here:
trap - RETURN
## Now clean all vars and remove all traps
cleanup_vars ALL
trap -p RETURN
echo "DONE."
