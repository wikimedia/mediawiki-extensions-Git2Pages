<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'Git2Pages' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['Git2Pages'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['Git2PagesMagic'] = __DIR__ . '/Git2Pages.i18n.magic.php';
	wfWarn(
	'Deprecated PHP entry point used for Git2Pages extension. Please use wfLoadExtension instead, ' .
	'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the Git2Pages extension requires MediaWiki 1.25+' );
}
