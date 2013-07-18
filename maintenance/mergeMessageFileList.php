<?php
/**
 * Merge $wgExtensionMessagesFiles from various extensions to produce a
 * single array containing all message files.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Maintenance
 */

# Start from scratch
define( 'MW_NO_EXTENSION_MESSAGES', 1 );

require_once __DIR__ . '/Maintenance.php';
$maintClass = 'MergeMessageFileList';
$mmfl = false;

/**
 * Maintenance script that merges $wgExtensionMessagesFiles from various
 * extensions to produce a single array containing all message files.
 *
 * @ingroup Maintenance
 */
class MergeMessageFileList extends Maintenance {

	function __construct() {
		parent::__construct();
		$this->addOption( 'list-file', 'A file containing a list of extension setup files, one per line.', true, true );
		$this->addOption( 'extensions-dir', 'Path where extensions can be found.', false, true );
		$this->addOption( 'output', 'Send output to this file (omit for stdout)', false, true );
		$this->mDescription = 'Merge $wgExtensionMessagesFiles from various extensions to produce a ' .
			'single array containing all message files.';
	}

	public function execute() {
		global $mmfl;

		# Add setup files contained in file passed to --list-file
		$lines = file( $this->getOption( 'list-file' ) );
		if ( $lines === false ) {
			$this->error( 'Unable to open list file.' );
		}
		$mmfl = array( 'setupFiles' => array() );

		# Strip comments, discard empty lines, and trim leading and trailing
		# whitespace. Comments start with '#' and extend to the end of the line.
		foreach( $lines as $line ) {
			$line = trim( preg_replace( '/#.*/', '', $line ) );
			if ( $line !== '' ) {
				$mmfl['setupFiles'][] = $line;
			}
		}

		# Now find out files in a directory
		$hasError = false;
		if ( $this->hasOption( 'extensions-dir' ) ) {
			$extdir = $this->getOption( 'extensions-dir' );
			$entries = scandir( $extdir );
			foreach ( $entries as $extname ) {
				if ( $extname == '.' || $extname == '..' || !is_dir( "$extdir/$extname" ) ) {
					continue;
				}
				$extfile = "{$extdir}/{$extname}/{$extname}.php";
				if ( file_exists( $extfile ) ) {
					$mmfl['setupFiles'][] = $extfile;
				} else {
					$hasError = true;
					$this->error( "Extension {$extname} in {$extdir} lacks expected {$extname}.php" );
				}
			}
		}

		if ( $hasError ) {
			$this->error( "Some files are missing (see above). Giving up.", 1 );
		}

		if ( $this->hasOption( 'output' ) ) {
			$mmfl['output'] = $this->getOption( 'output' );
		}
		if ( $this->hasOption( 'quiet' ) ) {
			$mmfl['quiet'] = true;
		}
	}
}

require_once RUN_MAINTENANCE_IF_MAIN;

foreach ( $mmfl['setupFiles'] as $fileName ) {
	if ( strval( $fileName ) === '' ) {
		continue;
	}
	$fileName = str_replace( '$IP', $IP, $fileName );
	if ( empty( $mmfl['quiet'] ) ) {
		fwrite( STDERR, "Loading data from $fileName\n" );
	}
	if ( !( include_once $fileName ) ) {
		fwrite( STDERR, "Unable to read $fileName\n" );
		exit( 1 );
	}
}
fwrite( STDERR, "\n" );
$s =
	"<" . "?php\n" .
	"## This file is generated by mergeMessageFileList.php. Do not edit it directly.\n\n" .
	"if ( defined( 'MW_NO_EXTENSION_MESSAGES' ) ) return;\n\n" .
	'$wgExtensionMessagesFiles = ' . var_export( $wgExtensionMessagesFiles, true ) . ";\n\n";

$dirs = array(
	$IP,
	dirname( __DIR__ ),
	realpath( $IP )
);

foreach ( $dirs as $dir ) {
	$s = preg_replace(
		"/'" . preg_quote( $dir, '/' ) . "([^']*)'/",
		'"$IP\1"',
		$s );
}

if ( isset( $mmfl['output'] ) ) {
	file_put_contents( $mmfl['output'], $s );
} else {
	echo $s;
}
