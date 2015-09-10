<?php
/**
 * Setup instructions for GitRepoExtension parser function
 */

$wgExtensionCredits['parserhook'][] = array(
    'path' => __FILE__,
    'name' => 'Git2Pages',
    'descriptionmsg' => 'git2pages-desc',
    'version' => '1.1.1',
    'author' => array( 'Teresa Cho' , 'Himeshi de Silva' ),
    'url' => 'https://www.mediawiki.org/wiki/Extension:Git2Pages',
);

$dir = __DIR__;

// Load the extension body to call the static function in the hook
$wgAutoloadClasses['Git2PagesHooks'] = $dir . '/Git2Pages.body.php';

// The function that will initialize the parser function
$wgHooks['ParserFirstCallInit'][] = 'Git2PagesHooks::Git2PagesSetup';

// i18n
$wgMessagesDirs['Git2Pages'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['Git2Pages'] = $dir . '/Git2Pages.i18n.php';
$wgExtensionMessagesFiles['Git2PagesMagic'] = $dir . '/Git2Pages.i18n.magic.php';

// Options default values
$wgGit2PagesDataDir = sys_get_temp_dir();
