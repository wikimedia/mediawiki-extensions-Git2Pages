<?php

use MediaWiki\MediaWikiServices;

/**
 * A class to manipulate a Git repository.
 */
class GitRepository {
	/** @var string */
	protected $gitUrl;

	/**
	 * Initializes a new instance of the class GitRepository.
	 *
	 * @param string $gitUrl contains url to the git repository
	 */
	function __construct( $gitUrl ) {
		$this->gitUrl = $gitUrl;
	}

	/**
	 * Clones Git repo in unique folder.
	 *
	 * @param string $url contains the git url
	 * @param string $gitFolder path to local git repo where repo is cloned
	 */
	static function CloneGitRepo( $url, $gitFolder ) {
		$proxy = (string)MediaWikiServices::getInstance()->getMainConfig()->get( 'HTTPProxy' );
		if ( !file_exists( $gitFolder ) ) {
			wfShellExec( 'git clone ' . wfEscapeShellArg( $url ) . ' ' . $gitFolder, $retval, [
				"http_proxy" => $proxy,
				"https_proxy" => $proxy,
			] );
			wfDebug( 'GitRepository: Cloned a git repository.' );
		} else {
			wfDebug( 'GitRepository: git repository exists, didn\'t clone.' );
		}
	}

	/**
	 * Adds a file or directory to sparse-checkout and updates the working tree
	 *
	 * @param string $gitFolder contains path to local Git repository
	 * @param string $checkoutItem contains the file or directory to checkout
	 */
	static function AddToSparseCheckout( $gitFolder, $checkoutItem ) {
		$oldDir = getcwd();
		chdir( $gitFolder );
		$sparseCheckoutFile = '.git/info/sparse-checkout';
		$file = file_get_contents( $gitFolder . DIRECTORY_SEPARATOR . $sparseCheckoutFile );
		if ( $file ) {
			if ( strpos( $file, $checkoutItem ) === false ) {
				wfShellExec(
					'echo ' . wfEscapeShellArg( $checkoutItem ) .
					' >> ' . wfEscapeShellArg( $sparseCheckoutFile )
				);
			}
		} else {
			wfShellExec( 'touch ' . wfEscapeShellArg( $sparseCheckoutFile ) );
			wfShellExec(
				'echo ' . wfEscapeShellArg( $checkoutItem ) .
				' >> ' . wfEscapeShellArg( $sparseCheckoutFile )
			);
		}

		$proxy = (string)MediaWikiServices::getInstance()->getMainConfig()->get( 'HTTPProxy' );
		wfShellExec( 'git read-tree -mu HEAD', $retval, [
			"http_proxy" => $proxy,
			"https_proxy" => $proxy,
		] );
		chdir( $oldDir );
	}

	/**
	 * Clones just the .git folder
	 *
	 * @param string $url
	 * @param string $gitFolder
	 * @param string $checkoutItem
	 * @param string $branch
	 */
	static function SparseCheckoutNewRepo( $url, $gitFolder, $checkoutItem, $branch ) {
		$oldDir = getcwd();
		if ( !file_exists( $gitFolder ) ) {
			mkdir( $gitFolder );
			chdir( $gitFolder );

			$proxy = (string)MediaWikiServices::getInstance()->getMainConfig()->get( 'HTTPProxy' );

			$sparseCheckoutFile = '.git/info/sparse-checkout';
			wfShellExec( 'git init' );
			wfShellExec( 'git remote add -f origin ' . wfEscapeShellArg( $url ), $retval, [
				"http_proxy" => $proxy,
				"https_proxy" => $proxy,
			] );
			wfShellExec( 'git config core.sparsecheckout true' );
			wfShellExec( 'touch ' . wfEscapeShellArg( $sparseCheckoutFile ) );
			wfShellExec(
				'echo ' . wfEscapeShellArg( $checkoutItem ) .
				' >> ' . wfEscapeShellArg( $sparseCheckoutFile )
			);
			wfShellExec(
				'git pull ' . wfEscapeShellArg( $url ) . ' ' . wfEscapeShellArg( $branch ),
				$retval,
				[
					"http_proxy" => $proxy,
					"https_proxy" => $proxy,
				]
			);
			wfDebug( 'GitRepository: Sparse checkout subdirectory' );
			chdir( $oldDir );
		} else {
			wfDebug( 'GitRepository: Git folder already exists, will add to sparse checkout' );
			self::AddToSparseCheckout( $gitFolder, $checkoutItem );
		}
	}

	/**
	 * Checkouts out Git branch
	 *
	 * @param string $branch is the branch to be checked
	 * @param string $gitFolder is the Git repository in which the branch will be checked in
	 */
	function GitCheckoutBranch( $branch, $gitFolder ) {
		$folder = wfEscapeShellArg( $gitFolder );
		wfShellExec(
			'git --git-dir=' . $folder . '/.git' .
			' --work-tree=' . $folder . ' checkout ' .
			wfEscapeShellArg( $branch )
		);
		wfDebug( 'GitRepository: Changed to branch ' . $branch );
	}

	/**
	 * Finds and reads the file.
	 *
	 * @param string $filename
	 * @param string $gitFolder contains the path to Git repo folder
	 * @param int $startLine
	 * @param int $endLine
	 * @return string
	 * @throws Exception
	 */
	function FindAndReadFile( $filename, $gitFolder, $startLine = 1, $endLine = -1 ) {
		# Remove file separators (dots) and slashes to prevent directory traversal attack
		$filename = preg_replace( '@[\\\\/!]|^\.+?&#@', '', $filename );
		$filePath = $gitFolder . DIRECTORY_SEPARATOR . $filename;

		# Throw an exception if $gitFolder doesn't look like a folder
		if ( strcmp( $gitFolder, realpath( $gitFolder ) ) !== 0 ) {
			throw new Exception( 'The parameter "$gitFolder" does not seem to be a folder.' );
		}

		$fileArray = file( $filePath );
		if ( $fileArray ) {
			if ( $endLine == -1 ) {
				$lineBlock = array_slice( $fileArray, $startLine - 1 );
			} else {
				$offset = $endLine - $startLine;
				$lineBlock = array_slice( $fileArray, $startLine - 1, $offset + 1 );
			}
			return implode( $lineBlock );
		} else {
			wfDebug( 'GitRepository: File does not exist or is unreadable' );
			throw new Exception( "File does not exist or is unreadable." );
		}
	}
}
