#!/usr/bin/perl
use strict;
use Locale::PO;
use Data::Dumper;

opendir( DIR, '.' );
my @files = readdir( DIR );
closedir( DIR );

foreach my $i ( @files ){
	next unless $i =~ m/^(.*)\.po$/;
	my $lang = $1;
	my $hash = Locale::PO->load_file_ashash( $i );

	# Create array
	my @strings = ();
	foreach my $key ( keys( %{$hash} )){
		next if $key eq '""';
		push( @strings, $hash->{$key}->msgid()." => ".$hash->{$key}->msgstr());
	}

	# Write PHP file
	open( OUT, ">$lang.php" );
	print OUT "<?php \$TRANSLATIONS = array(\n";
	print OUT join( ",\n", @strings );
	print OUT "\n);\n";
	close( OUT );
}