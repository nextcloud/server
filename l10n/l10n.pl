#!/usr/bin/perl
use strict;
use Locale::PO;
use Cwd;
use Data::Dumper;

sub crawl{
	my( $dir ) = @_;
	my @found = ();

	opendir( DIR, $dir );
	my @files = readdir( DIR );
	closedir( DIR );

	foreach my $i ( @files ){
		next if substr( $i, 0, 1 ) eq '.';
		if( -d $dir.'/'.$i ){
			push( @found, crawl( $dir.'/'.$i ));
		}
		elsif( $i eq 'xgettextfiles' ){
			push( @found, $dir );
		}
	}

	return @found;
}

my $task = shift( @ARGV );
my $place = '..';

die( "Usuage: l10n.pl task\ntask: read, write\n") unless $task && $place;

# Our current position
my $whereami = cwd();
die( "Program must be executed in a l10n-folder called 'l10n'" ) unless $whereami =~ m/\/l10n$/;

# Where are i18n-files?
my @dirs = crawl( $place );

# Languages
mkdir( 'templates' ) unless -d 'templates';

my @languages = ();
opendir( DIR, '.' );
my @files = readdir( DIR );
closedir( DIR );
foreach my $i ( @files ){
	push( @languages, $i ) if -d $i && substr( $i, 0, 1 ) ne '.';
}

if( $task eq 'read' ){
	foreach my $dir ( @dirs ){
		my @temp = split( /\//, $dir );
		pop( @temp );
		my $app = pop( @temp );
		chdir( $dir );
		my $output = "${whereami}/templates/$app.pot";
			
		if( -e $output ){
			`xgettext --files-from=xgettextfiles --join-existing --output="$output" --keyword=t`
		}
		else{
			`xgettext --files-from=xgettextfiles --output="$output" --keyword=t`
		}
		chdir( $whereami );
	}
}
elsif( $task eq 'write' ){
	foreach my $dir ( @dirs ){
		my @temp = split( /\//, $dir );
		pop( @temp );
		my $app = pop( @temp );
		chdir( $dir );
		foreach my $language ( @languages ){
			next if $language eq 'templates';
			
			my $input = "${whereami}/$language/$app.po";
			next unless -e $input;

			my $array = Locale::PO->load_file_asarray( $input );
			# Create array
			my @strings = ();
			foreach my $string ( @{$array} ){
				next if $string->msgid() eq '""';
				next if $string->msgstr() eq '""';
				push( @strings, $string->msgid()." => ".$string->msgstr());
			}
			next if $#strings == -1; # Skip empty files

			# Write PHP file
			open( OUT, ">$language.php" );
			print OUT "<?php \$TRANSLATIONS = array(\n";
			print OUT join( ",\n", @strings );
			print OUT "\n);\n";
			close( OUT );
		}
		chdir( $whereami );
	}
}
else{
	print "unknown task!\n";
}
