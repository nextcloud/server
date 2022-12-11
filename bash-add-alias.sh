#!/bin/bash

## ###########################################################################
## Creates an alias to run `occ` as the appropriate user:
##	the owner of nextcloud/config/config.php
## or
##	a common web server user name found in /etc/passwd
##
## Optionally adds the alias to user's .bash_aliases file, if found
## If not found, optionally add alias to ~/.bashrc
##
## Optionally copies bash completion script `occ.bash` to
##	/etc/bash_completion.d/
##
## @author Ronald Barnes
## @copyright Copyright 2022, Ronald Barnes ron@ronaldbarnes.ca
## ###########################################################################
##
## Usage:
## . bash-add-alias.sh
## or:
## source bash-add-alias.sh
## ###########################################################################

## Save original "catch undefined vars" setting:
_occ_orig_set_u=$-
if [[ $_occ_orig_set_u =~ u ]] ; then
	_occ_orig_set_u=0
else
	_occ_orig_set_u=1
fi
## Catch undefined vars:
set -u


## Define colours:
function _occ_define_colours()
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
	unset -v user_name
	unset -v home_dir
	unset -v _occ_alias_exists
	unset -v phpFound
	unset -v answer
	unset -v _occ_alias_string
	unset -v _occ_alias_exists

	unset -v _occ_nc_path
	unset -f _occ_get_nc_path
	unset -v _occ_completion_script
	unset -v script_found
	unset -v _occ_script_installed
	unset -v _occ_alias_installed
	unset -v _occ_status
	unset -v _occ_sudo_user

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
		## Reset unbound var checking to original state:
		if [[ ! _occ_orig_set_u -eq 0 ]] ; then
			set +u
		fi
		unset -v _occ_orig_set_u
		unset -f _occ_define_colours
		unset -f _occ_bash_aliases
	fi
	}




function _occ_get_nc_path()
	{
	read -ep "Path to NextCloud directory? " -i "/" _occ_nc_path
	if [[ ! -f ${_occ_nc_path}/occ ]] ; then
		_occ_get_nc_path
	fi
	}


function _occ_bash_aliases()
	{
	if [[ $# -ne 2 ]] ; then
		## Expecting path and file, i.e. ~ and .bash_aliases
		return 99
	else
		local home_dir=${1}
		local alias_file=${2}
	fi

	if [[ ! -w ${home_dir}/${alias_file} ]] ; then
		return
	fi
	grep --quiet --no-messages "occ" ${home_dir}/${alias_file}
	_occ_alias_exists=$?
	if [[ _occ_alias_exists -eq 0 ]]; then
		echo "There is an \"occ\" alias in ${home_dir}/${alias_file}:"
		echo -n " --> "
		grep --color=auto "occ" ${home_dir}/${alias_file}
		_occ_alias_installed=0
	elif [[ -w ${home_dir}/${alias_file} ]]; then
		echo -en "Add alias to "
		echo -en "${yellow}${home_dir}/${alias_file}${default_colour}?"
		read -s -p " (y/N) " -n 1 answer
		if [[ ${answer} =~ ^Y|y ]] ; then
			echo "Y"
			echo ""																	>> ${home_dir}/${alias_file}
			echo "## tab completion for NextCloud:"	>> ${home_dir}/${alias_file}
			echo "alias occ=${_occ_alias_string}"		>> ${home_dir}/${alias_file}
			answer=$?
			if [[ ${answer} -eq 0 ]] ; then
				echo -ne "${green}Success${default_colour}: "
				grep --color=auto occ ${home_dir}/${alias_file}
				_occ_alias_installed=0
			fi
		else
			echo "N"
		fi
	fi
	}



## Capture exit conditions to clean up all variables:
trap 'cleanup_vars ALL' RETURN EXIT QUIT SIGINT SIGKILL SIGTERM


## Handy red / yellow / green / default colour defs:
_occ_define_colours

user_name=$(whoami)
_occ_completion_script="occ.bash"

## Find NextCloud installation directory
_occ_nc_path=$(pwd)
if [[ ! -f ${_occ_nc_path}/occ ]] ; then
	echo -e "Can't find ${yellow}occ${default_colour} in current directory."
	_occ_get_nc_path
	## Strip trailing "/" from path:
	_occ_nc_path=${_occ_nc_path%/}
fi


## Find owner of config/config.php, should be the web server user, per:
## https:/docs.nextcloud.com/server/latest/admin_manual/configuration_server/occ_command.html#http-user-label
## but in shared hosting, might not be(?)
_occ_sudo_user=""
if [[ -f ${_occ_nc_path}/config/config.php ]] ; then
	_occ_sudo_user=$(stat --format="%U" ${_occ_nc_path}/config/config.php)
else
	echo -en "${red}WARNING${default_colour}: "
	echo -en "Cannot locate ${yellow}${_occ_nc_path}/config/config.php"
	echo -e "${default_colour}"
	## return 100
	read _occ_sudo_user <<< $(grep --only-matching --extended-regex		\
		"www-data|httpd|nginx|lighttpd|apache|http|wwwrun"			\
		/etc/passwd
		)
fi

_occ_alias_string=""
## Looks for existing occ alias:
_occ_alias_string=$(alias occ 2>/dev/null)
_occ_alias_exists=$?
if [ ${_occ_alias_exists} -eq 0 ] ; then
	echo "occ alias found for user \"${user_name}\":"
	echo -e " --> ${green}$(alias occ)${default_colour}"
else
	echo "No occ alias found for user \"${user_name}\"."
	## Note: `which` command not always installed, see if `php` exists this way:
	php --version 1>/dev/null 2>/dev/null
	phpFound=$?
	if [ $phpFound -ne 0 ]; then
		echo -e "${red}ERROR${default_colour}: php not found in path."
		return 99
	fi
	_occ_alias_string="'sudo --user ${_occ_sudo_user} php ${_occ_nc_path}/occ'"
	echo -ne "Run \"${yellow}alias occ="
	echo -ne "${green}${_occ_alias_string}${default_colour}\""
	read -s -p " (Y/n)? " -n 1 answer
	if [[ ${answer} =~ ^[Nn] ]] ; then
		echo "N"
	else
		echo "Y"
		eval alias "occ=${_occ_alias_string}"
		alias occ
	fi
fi


_occ_alias_installed=1
## Is there an occ alias in ~/.bash_aliases?
_occ_bash_aliases ${HOME} ".bash_aliases"

## If no alias installed into ~/bash_aliases file, try ~/.bashrc:
if [[ $_occ_alias_installed -ne 0 ]] ; then
	_occ_bash_aliases ${HOME} ".bashrc"
fi


## Run ${_occ_completion_script} to handle bash auto completion?
script_found=1	## aka False
if [[ -f ${_occ_nc_path}/${_occ_completion_script} ]] ; then
	script_found=0
	echo -en "Run bash completion script "
	echo -en "${green}${_occ_completion_script}${default_colour}? "
	read -sp " (Y/n) " -N 1 answer
	if [[ ${answer} =~ ^[Nn] ]] ; then
		echo "N"
	else
		echo "Y"
		echo -n "Running ${_occ_nc_path}/${_occ_completion_script} ... "
		## Do not run cleanup_vars() when ${_occ_completion_script} returns:
		trap - RETURN
		source ${_occ_nc_path}/${_occ_completion_script}
		_occ_status=$?
		## Reset trap:
		trap 'cleanup_vars ALL' RETURN
		if [[ ${_occ_status} -eq 0 ]] ; then
			echo -e "${green}success${default_colour}."
		else
			echo -e "${red}Error${default_colour}."
		fi
	fi
else
	echo -en "${red}WARNING${default_colour}: "
	echo -en "Cannot find ${yellow}${_occ_nc_path}/${_occ_completion_script}"
	echo -e "${default_colour}"
fi



## Does ${_occ_completion_script} exist in...
##	/etc/bash_completion.d/?
_occ_script_installed=1
if [[ -f ${_occ_nc_path}/${_occ_completion_script} ]] ; then
	if [[ -r /etc/bash_completion.d/${_occ_completion_script} ]] ; then
		echo -en "Found ${yellow}/etc/bash_completion.d/"
		echo -e "${_occ_completion_script}${default_colour}."
	else
		echo -en "Copy ${yellow}${_occ_completion_script}${default_colour} to "
		echo -en "${yellow}/etc/bash_completion.d/"
		echo -en "${default_colour}?"
		read -sp " (y/N) " -n 1 answer
		if [[ ${answer} =~ ^[Yy] ]] ; then
			echo "Y"
			mkdir -vp /etc/bash_completion.d
			## File name MUST have name of command / alias it operates on when
			## it is in this location, i.e. occ, _occ, or occ.bash:
			cp --verbose																	\
				--archive																		\
				--preserve=all															\
				--interactive																\
				${_occ_nc_path}/${_occ_completion_script}		\
				/etc/bash_completion.d/
		else
			echo "N"
		fi
	fi
fi



## Cannot remove trap on RETURN inside a return trap catch, so do it here:
trap - RETURN
## Now clean all vars and remove all traps
cleanup_vars ALL
trap -p RETURN
echo "DONE."
