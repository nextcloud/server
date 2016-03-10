#!/usr/bin/env bash

FTPD=$1

sudo apt-get update -qq
sudo apt-get install -yqq bc $FTPD
sudo /etc/init.d/$FTPD restart
if [ "$FTPD" = "vsftpd" ]; then echo "local_enable=YES" | sudo tee -a /etc/vsftpd.conf; echo "write_enable=YES" | sudo tee -a /etc/vsftpd.conf; fi
sudo /etc/init.d/$FTPD restart

pass=$(perl -e 'print crypt("test", "password")')
sudo useradd -m -p $pass test
