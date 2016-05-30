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
			push(@found,$dir.'/'.$i) if $i =~ /.*(?<!\.min)\.js$/ || $i =~ /\.php$/;
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

sub getPluralInfo {
	my( $info ) = @_;

	# get string
	$info =~ s/.*Plural-Forms: (.+)\\n.*/$1/;
	$info =~ s/^(.*)\\n.*/$1/g;

	return $info;
}

sub init() {
	# let's get the version from stdout of xgettext
	my $out = `xgettext --version`;
	# we assume the first line looks like this 'xgettext (GNU gettext-tools) 0.19.3'
	$out = substr $out, 29, index($out, "\n")-29;
	$out =~ s/^\s+|\s+$//g;
	$out = "v" . $out;
	my $actual = version->parse($out);
	# 0.18.3 introduced JavaScript as a language option
	my $expected = version->parse('v0.18.3');
	if ($actual < $expected) {
		die( "Minimum expected version of xgettext is " . $expected . ". Detected: " . $actual );
	}
}

init();

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
			my $keywords = '';
			if( $file =~ /\.js$/ ){
				$keywords = '--keyword=t:2 --keyword=n:2,3';
			}
			else{
				$keywords = '--keyword=t --keyword=n:1,2';
			}
			my $language = ( $file =~ /\.js$/ ? 'Javascript' : 'PHP');
			my $joinexisting = ( -e $output ? '--join-existing' : '');
			print "    Reading $file\n";
			`xgettext --output="$output" $joinexisting $keywords --language=$language "$file" --add-comments=TRANSLATORS --from-code=UTF-8 --package-version="8.0.0" --package-name="ownCloud Core" --msgid-bugs-address="translations\@owncloud.org"`;
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
			my @js_strings = ();
			my $plurals;

			TRANSLATIONS: foreach my $string ( @{$array} ){
				if( $string->msgid() eq '""' ){
					# Translator information
					$plurals = getPluralInfo( $string->msgstr());
				}
				elsif( defined( $string->msgstr_n() )){
					# plural translations
					my @variants = ();
					my $msgid = $string->msgid();
					$msgid =~ s/^"(.*)"$/$1/;
					my $msgid_plural = $string->msgid_plural();
					$msgid_plural =~ s/^"(.*)"$/$1/;
					my $identifier = "_" . $msgid."_::_".$msgid_plural . "_";

					foreach my $variant ( sort { $a <=> $b} keys( %{$string->msgstr_n()} )){
						next TRANSLATIONS if $string->msgstr_n()->{$variant} eq '""';
						push( @variants, $string->msgstr_n()->{$variant} );
					}

					push( @strings, "\"$identifier\" => array(".join(",", @variants).")");
					push( @js_strings, "\"$identifier\" : [".join(",", @variants)."]");
				}
				else{
					# singular translations
					next TRANSLATIONS if $string->msgstr() eq '""';
					push( @strings, $string->msgid()." => ".$string->msgstr());
					push( @js_strings, $string->msgid()." : ".$string->msgstr());
				}
			}
			next if $#strings == -1; # Skip empty files

			for (@strings) {
				s/\$/\\\$/g;
			}

            # delete old php file
            unlink "$language.php";

			# Write js file
			open( OUT, ">$language.js" );
			print OUT "OC.L10N.register(\n    \"$app\",\n    {\n    ";
			print OUT join( ",\n    ", @js_strings );
			print OUT "\n},\n\"$plurals\");\n";
			close( OUT );

			# Write json file
			open( OUT, ">$language.json" );
			print OUT "{ \"translations\": ";
			print OUT "{\n    ";
			print OUT join( ",\n    ", @js_strings );
			print OUT "\n},\"pluralForm\" :\"$plurals\"\n}";
			close( OUT );

		}
		chdir( $whereami );
	}
}
else{
	print "unknown task!\n";
}
