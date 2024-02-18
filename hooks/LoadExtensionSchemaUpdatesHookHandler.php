<?php
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

/**
 * Handler for the "LoadExtensionSchemaUpdates" hook.
 *
 * This cannot use dependency injection as this hook is used in a context where the global service locator is not yet initialized.
 *
 * @author elifoster
 */
class LoadExtensionSchemaUpdatesHookHandler implements LoadExtensionSchemaUpdatesHook {
	/**
	 * Setups and Modifies Database Information
	 *
	 * @access	public
	 * @param	object	DatabaseUpdater Object
	 * @return	boolean	true
	 */
	public function onLoadExtensionSchemaUpdates($updater) {
		$extDir = __DIR__;
		$updater->addExtensionUpdate(['addTable', 'ext_oredict_items', "{$extDir}/install/sql/ext_oredict_items.sql", true]);
		return true;
	}
}