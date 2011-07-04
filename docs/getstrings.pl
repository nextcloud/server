#!/usr/bin/perl
use strict;

if( -e 'messages.pot' ){
	`xgettext --files-from=xgettextfiles --join-existing --output=messages.pot --keyword=t`
}
else{
	`xgettext --files-from=xgettextfiles --output=messages.pot --keyword=t`
}

opendir( DIR, '.' );
my @files = readdir( DIR );
closedir( DIR );

foreach my $i ( @files ){
	next unless $i =~ m/^(.*)\.po$/;
	`xgettext --files-from=xgettextfiles --join-existing --output=$i --keyword=t`
}
