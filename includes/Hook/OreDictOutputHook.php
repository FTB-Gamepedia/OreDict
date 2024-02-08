<?php
namespace OreDict\Hook;
/**
 * This is a hook handler interface, see docs/Hooks.md.
 * Use the hook name "OreDictOutput" to register handlers implementing this interface.
 */
interface OreDictOutputHook {
	/**
	 * This hook is called by the #dict and #grid_foreach parser functions (see OreDict.hooks.php and OreDict::runHooks in OreDict.body.php).
	 * 
	 * Its implementation is handled in the Tilesheets extension. See Tilesheets/Tilesheets.hooks.php.
	 *
	 * @param string $out
	 * @param array $items
	 * @param string $params
	 * @return bool
	 */
	public function onOreDictOutput(&$out, $items, $params);
}