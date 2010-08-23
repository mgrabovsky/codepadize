<?php

if( isset( $_SERVER['SERVER_ADDR'] ) || isset( $_SERVER['REMOTE_ADDR'] ) ) {
	echo 'this script can only be run from the command line', PHP_EOL;
	exit( 1 );
}

$opts = array(
	'lang'    => null,
	'code'    => null,
	'private' => null,
	'run'     => null,
);

array_shift( $argv );
while( count( $argv ) > 0 ) {
	switch( strtolower( $argv[0] ) ) {
	case '-l':
	case '--language':
		array_shift( $argv );
		$language = languages::get_lang( array_shift( $argv ) );
		if( !is_null( $language ) ) {
			$opts['lang'] = $language;
		}
		break;
	case '-p':
	case '--private':
		array_shift( $argv );
		$opts['private'] = 'True';
		break;
	case '-r':
	case '--run':
		array_shift( $argv );
		$opts['run'] = 'True';
		break;
	case '-h':
	case '--help':
		usage();
		exit( 0 );
	default:
		$opts['file'] = array_shift( $argv );
		break;
	}
}

// Load code
if( isset( $opts['file'] ) && $opts['file'] !== '-' && file_exists( $opts['file'] ) ) {
	// From file, if exists
	$opts['code'] = file_get_contents( $opts['file'] );
} else {
	// Or from STDIN
	while( !feof( STDIN ) ) {
		$opts['code'] .= fgets( STDIN );
	}
}
if( isset( $opts['file'] ) ) unset( $opts['file'] );

// If language wasn't set by user, set it to plaint text
// In future we might check file extension, maybe opening token, etc.
if( !isset( $opts['lang'] ) )
	$opts['lang'] = languages::get_lang( 'plain-text' );

// Make POST request to codepad with supplied data
$request = curl_init();

curl_setopt_array( $request, array(
	CURLOPT_URL => 'http://codepad.org/',
	CURLOPT_POST => true,
	CURLOPT_POSTFIELDS => http_build_query( array_merge( $opts, array(
		'submit' => 'Submit', ) ) ),
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_USERAGENT => 'github.com/mgrabovsky/codepadize-php' )
);

if( curl_exec( $request ) === false ) {
	throw new Exception( 'an unexpected curl error occured: (' . curl_errno( $request ) .
		') ' . curl_error( $request ) );
	exit( 1 );
}

// Print where we've been redirected to -- the paste URL
echo curl_getinfo( $request, CURLINFO_EFFECTIVE_URL ), PHP_EOL;

curl_close( $request );

function usage() {
	echo <<<TERM
Usage: codepad [-l|--language <language>] [-p|--private] [-r|--run] [<file>]
       codepad -h|--help

  -l, --language <language> Syntax highlight and eventually run the code
                              in specified language
  -h, --help                Show this help message
  -p, --private             Make the paste private
  -r, --run                 Run the code after submitting
  <file>                    Submit code from specified file

  If file name is -, is not supplied or file does not exist, code will be
    read from STDIN until EOF
TERM;
}

class languages {
	static $languages = array(
		array( 'C',          array() ),
		array( 'C++',        array( 'cpp' ) ),
		array( 'D',          array() ),
		array( 'Haskell',    array( 'hs' ) ),
		array( 'Lua',        array() ),
		array( 'OCaml',      array() ),
		array( 'PHP',        array() ),
		array( 'Perl',       array( 'pl' ) ),
		array( 'Plain Text', array( 'plain-text', 'plain', 'text' ) ),
		array( 'Python',     array( 'py' ) ),
		array( 'Ruby',       array( 'rb' ) ),
		array( 'Scheme',     array( 'scm' ) ),
		array( 'Tcl',        array() ), );

	public static function get_lang( $code ) {
			foreach( self::$languages as $language ) {
				if( in_array( strtolower( $code ), $language[1] ) ||
					strcasecmp( $code, $language[0] ) == 0 )
				{
					return $language[0];
				}
			}

			return null;
	}
}
