#!/usr/bin/perl
use strict;
use Locale::PO;
use Cwd;
use Data::Dumper;
use File::Path;

sub crawlPrograms{
	my( $dir, $ignore ) = @_;
	my @found = ();

	opendir( DIR, $dir );
	my @files = readdir( DIR );
	closedir( DIR );
	@files = sort( @files );

	foreach my $i ( @files ){
		next if substr( $i, 0, 1 ) eq '.';
		if( $i eq 'l10n' && !$ignore ){
			push( @found, $dir );
		}
		elsif( -d $dir.'/'.$i ){
			push( @found, crawlPrograms( $dir.'/'.$i ));
		}
	}

	return @found;
}

sub crawlFiles{
	my( $dir ) = @_;
	my @found = ();

	opendir( DIR, $dir );
	my @files = readdir( DIR );
	closedir( DIR );
	@files = sort( @files );

	foreach my $i ( @files ){
		next if substr( $i, 0, 1 ) eq '.';
		next if $i eq 'l10n';
		
		if( -d $dir.'/'.$i ){
			push( @found, crawlFiles( $dir.'/'.$i ));
		}
		else{
			push(@found,$dir.'/'.$i) if $i =~ /\.js$/ || $i =~ /\.php$/;
		}
	}

	return @found;
}

sub readIgnorelist{
	return () unless -e 'l10n/ignorelist';
	my %ignore = ();
	open(IN,'l10n/ignorelist');
	while(<IN>){
		my $line = $_;
		chomp($line);
		$ignore{"./$line"}++;
	}
	close(IN);
	return %ignore;
}

my $task = shift( @ARGV );
my $place = '..';

die( "Usage: l10n.pl task\ntask: read, write\n" ) unless $task && $place;

# Our current position
my $whereami = cwd();
die( "Program must be executed in a l10n-folder called 'l10n'" ) unless $whereami =~ m/\/l10n$/;

# Where are i18n-files?
my @dirs = crawlPrograms( $place, 1 );

# Languages
my @languages = ();
opendir( DIR, '.' );
my @files = readdir( DIR );
closedir( DIR );
foreach my $i ( @files ){
	push( @languages, $i ) if -d $i && substr( $i, 0, 1 ) ne '.';
}

if( $task eq 'read' ){
	rmtree( 'templates' );
	mkdir( 'templates' ) unless -d 'templates';
	print "Mode: reading\n";
	foreach my $dir ( @dirs ){
		my @temp = split( /\//, $dir );
		my $app = pop( @temp );
		chdir( $dir );
		my @totranslate = crawlFiles('.');
		my %ignore = readIgnorelist();
		my $output = "${whereami}/templates/$app.pot";
		print "  Processing $app\n";

		foreach my $file ( @totranslate ){
			next if $ignore{$file};
			my $keyword = ( $file =~ /\.js$/ ? 't:2' : 't');
			my $language = ( $file =~ /\.js$/ ? 'C' : 'PHP');
			my $joinexisting = ( -e $output ? '--join-existing' : '');
			print "    Reading $file\n";
			`xgettext --output="$output" $joinexisting --keyword=$keyword --language=$language "$file"`;
		}
		chdir( $whereami );
	}
}
elsif( $task eq 'write' ){
	print "Mode: write\n";
	foreach my $dir ( @dirs ){
		my @temp = split( /\//, $dir );
		my $app = pop( @temp );
		chdir( $dir.'/l10n' );
		print "  Processing $app\n";
		foreach my $language ( @languages ){
			next if $language eq 'templates';
			
			my $input = "${whereami}/$language/$app.po";
			next unless -e $input;

			print "    Language $language\n";
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
