<?php
/**
 * Execution code
 */

class Git2PagesHooks {

	/**
	 * Sets the value of $wgGit2PagesDataDir
	 */
	public static function setGit2PagesDataDir() {
		global $wgGit2PagesDataDir;
		// Options default values
		$wgGit2PagesDataDir = sys_get_temp_dir();
	}

	/**
	 * Registers the parser function hook
	 * @param Parser $parser
	 * @return true
	 */
	public static function Git2PagesSetup( $parser ) {
		$parser->setFunctionHook( 'snippet', [ 'Git2PagesHooks', 'PullContentFromRepo' ] );
		return true;
	}

	/**
	 * Converts an array of values in form [0] => "name=value" into a real
	 * associative array in form [name] => value
	 *
	 * @param array $options
	 * @return array
	 */
	static function extractOptions( array $options ) {
		$results = [];
		foreach ( $options as $option ) {
			$pair = explode( '=', $option );
			if ( count( $pair ) == 2 ) {
				$name = trim( $pair[0] );
				$value = trim( $pair[1] );
				$results[$name] = $value;
			}
		}
		return $results;
	}

	/**
	 * Checks if value is an int whether it is type string or int.
	 *
	 * @param mixed $mixed contains value to be checked
	 * @return bool true if it is an int value, false otherwise
	 */
	static function isint( $mixed ) {
		return preg_match( '/^\d*$/', $mixed ) == 1;
	}

	/**
	 * Pulls the content from a repository
	 *
	 * @param array $parser Array, the first element a Parser instance, then the user input values
	 * @return string|array
	 */
	public static function PullContentFromRepo( $parser ) {
		global $wgGit2PagesDataDir;

		$opts = func_get_args();
		array_shift( $opts );

		$options = self::extractOptions( $opts );
		$url = $options['repository'];
		if ( isset( $options['branch'] ) ) {
			$branch = $options['branch'];
		} else {
			$branch = 'master';
		}
		$gitFolder = $wgGit2PagesDataDir . DIRECTORY_SEPARATOR . md5( $url . $branch );
		if ( !isset( $options['repository'] ) || !isset( $options['filename'] ) ) {
			return 'repository and/or filename not defined.';
		}
		$gitRepo = new GitRepository( $url );
		$filename = $options['filename'];
		$startLine = isset( $options['startline'] ) ? $options['startline'] : 1;
		$endLine = isset( $options['endline'] ) ? $options['endline'] : -1;
		if ( !self::isint( $startLine ) ) {
			return '<strong class="error">startline is not an integer.</strong>';
		}
		if ( $endLine != -1 && !self::isint( $endLine ) ) {
			return '<strong class="error">endline is not an integer.</strong>';
		}
		try {
			$gitRepo->SparseCheckoutNewRepo( $url, $gitFolder, $filename, $branch );
			$fileContents = $gitRepo->FindAndReadFile( $filename, $gitFolder, $startLine, $endLine );
			$output = '<pre>' . htmlspecialchars( $fileContents ) . '</pre>';
		} catch ( Exception $ex ) {
			$output = '<strong class="error">' . $ex->getMessage() . '</strong>';
		}
		return [ $output, 'nowiki' => true, 'noparse' => true, 'isHTML' => true ];
	}
}
