<?php
/**
 * OreDict
 *
 * @file
 * @ingroup Extensions
 * @version 1.0.2
 * @author Jinbobo <paullee05149745@gmail.com>
 * @license
 */

if( !defined( 'MEDIAWIKI' ) )
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );

$wgShowExceptionDetails = true;

$wgExtensionCredits['parserhooks'][] = array(
	'path' => __FILE__,
	'name' => 'OreDict',
	'descriptionmsg' => 'oredict-desc',
	'version' => '1.0.2',
	'author' => '[http://ftb.gamepedia.com/User:Jinbobo Jinbobo], Telshin, [http://ftb.gamepedia.com/User:Retep998 Retep998], [http://ftb.gamepedia.com/User:TheSatanicSanta SatanicSanta], noahm',
	'url' => 'http://help.gamepedia.com/Extension:OreDict'
);

// Setup logging
$wgLogTypes[] = 'oredict';
$wgLogActionsHandlers['oredict/*'] = 'LogFormatter';

$wgMessagesDirs['OreDict'] = __DIR__ .'/i18n';
$wgExtensionMessagesFiles['OreDict'] = dirname(__FILE__)."/OreDict.i18n.php";
$wgExtensionMessagesFiles['OreDictMagic'] = dirname(__FILE__)."/OreDict.i18n.magic.php";

$wgAutoloadClasses['OreDict'] = dirname(__FILE__)."/OreDict.body.php";
$wgAutoloadClasses['OreDictItem'] = dirname(__FILE__)."/OreDict.body.php";
$wgAutoloadClasses['OreDictError'] = dirname(__FILE__)."/OreDict.body.php";
$wgAutoloadClasses['OreDictHooks'] = dirname(__FILE__)."/OreDict.hooks.php";
$wgAutoloadClasses['OreDictForm'] = dirname(__FILE__)."/classes/OreDictForm.php";

$wgAutoloadClasses['OreDictEntryManager'] = dirname(__FILE__)."/special/OreDictEntryManager.php";
$wgAutoloadClasses['OreDictList'] = dirname(__FILE__)."/special/OreDictList.php";
$wgAutoloadClasses['ImportOreDict'] = dirname(__FILE__)."/special/ImportOreDict.php";

$wgSpecialPages['OreDictEntryManager'] = "OreDictEntryManager";
$wgSpecialPageGroups['OreDictEntryManager'] = "oredict";
$wgSpecialPages['OreDictList'] = "OreDictList";
$wgSpecialPageGroups['OreDictList'] = "oredict";
$wgSpecialPages['ImportOreDict'] = "ImportOreDict";
$wgSpecialPageGroups['ImportOreDict'] = "oredict";

$wgHooks['ParserFirstCallInit'][] = 'OreDictHooks::SetupParser';
$wgHooks['EditPage::showEditForm:initial'][] = 'OreDictHooks::OutputWarnings';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'OreDictHooks::SchemaUpdate';

$wgAvailableRights[] = 'edittilesheets';
$wgAvailableRights[] = 'importtilesheets';

// Resource loader modules
$wgResourceModules['ext.oredict.list'] = array(
	'styles' => 'css/ext.oredict.list.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'OreDict'
);
$wgResourceModules['ext.oredict.manager'] = array(
	'scripts' => 'js/ext.oredict.manager.js',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'OreDict'
);

// Default configuration
$wgOreDictDebug = false;
