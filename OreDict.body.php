<?php
use Wikimedia\Rdbms\ILoadBalancer;
use MediaWiki\MediaWikiServices;

/**
 * OreDict main file
 *
 * @file
 * @ingroup Extensions
 * @version 1.0.1
 * @author Jinbobo <paullee05149745@gmail.com>
 * @license
 */

class OreDict{
	private $mOutputLimit;
	private $mItemName;
	private $mItemMod;
	private $mRawArray;

	static private $mQueries;

	/**
	 * Init properties.
	 *
	 * @param string $itemName
	 * @param string $itemMod
	 */
	public function __construct($itemName, $itemMod = '') {
		$this->mItemName = $itemName;
		$this->mOutputLimit = 20;
		$this->mItemMod = $itemMod;
	}

	/**
	 * Change the output limit of the query.
	 *
	 * @param int $limit
	 */

	public function setOutputLimit($limit) {
		$this->mOutputLimit = $limit;
	}

	/**
	 * Output a list of item names.
	 *
	 * @return array
	 */

	public function getRawOutput() {
		$out = "";
		foreach ($this->mRawArray as $item) {
			if (is_object($item)) {
				if (get_class($item) == "OreDictItem") {
					$out .= $item->getItemName() . " \n";
				}
			}
		}
		return array($out, 'noparse' => true, 'isHTML' => true);
	}

	/**
	 * Execute output hooks and returns their output.
	 *
	 * @param string $params
	 * @return array
	 */

	public function runHooks($params = "") {
		$out = "";
		MediaWikiServices::getInstance()->getHookContainer()->run("OreDictOutput", array(&$out, $this->mRawArray, $params));
		return array($out, 'noparse' => false, 'isHTML' => false);
	}

	/**
	 * Queries the OreDict table
	 *
	 * @param bool $byTag
	 * @param bool $noFallback
	 * @return bool
	 */
	public function exec($byTag = false, $noFallback = false) {
		$dbr = wfGetDB(DB_REPLICA);

		// Vars
		$itemModEscaped = $dbr->addQuotes($this->mItemMod);

		$sLim = $this->mOutputLimit;

		// Query database
		if (!isset(self::$mQueries[$this->mItemName][$this->mItemMod])) {
			if ($byTag) {
				OreDictError::notice(wfMessage('oredict-query-notice')->params('Tag', $this->mItemName, $this->mItemMod)->text());

				$result = $dbr->select(
					"ext_oredict_items",
					["*"],
					[
						'tag_name' => $this->mItemName,
						"($itemModEscaped = '' OR mod_name = $itemModEscaped)",
					],
					__METHOD__,
					[
						"ORDER BY" => "entry_id",
						"LIMIT" => $sLim,
					]
				);
			} else {
				OreDictError::notice(wfMessage('oredict-query-notice')->params('Item', $this->mItemName, $this->mItemMod)->text());

				$subResult = $dbr->select(
					"ext_oredict_items",
					['tag_name'],
					[
						'item_name' => $this->mItemName,
						"($itemModEscaped = '' OR mod_name = $itemModEscaped)",
					],
					__METHOD__
				);

				$tagNameList = [];

				foreach ($subResult as $r) {
					$tagNameList[] = $r->tag_name;
				}
				$where = [];
				if (!empty($tagNameList)) {
					$where['tag_name'] = $tagNameList;
				}

				$result = $dbr->select(
					"ext_oredict_items",
					["*"],
					$where,
					__METHOD__,
					[
						"ORDER BY" => "entry_id",
						"LIMIT" => $sLim,
					]
				);
			}
			//OreDictError::query($query);
			//$result = $dbr->query($query);
			foreach ($result as $row) {
				self::$mQueries[$this->mItemName][$this->mItemMod][] = new OreDictItem($row);
			}

			if (!isset(self::$mQueries[$this->mItemName][$this->mItemMod])) {
				self::$mQueries[$this->mItemName][$this->mItemMod] = [];
				OreDictError::notice(wfMessage('oredict-empty-query-notice')->text());
			} else {
				$rows = $result->numRows();
				OreDictError::notice(wfMessage('oredict-nonempty-query-notice')->numParams($rows)->text());
			}
		}

		if (empty(self::$mQueries[$this->mItemName][$this->mItemMod])) {
			if ($noFallback) {
				$this->mRawArray = [];
			} else {
				$this->mRawArray = [new OreDictItem($this->mItemName, '', $this->mItemMod, '')];
			}
		} else {
			$this->mRawArray = self::$mQueries[$this->mItemName][$this->mItemMod];
		}

		return true;
	}

	/**
	 * Shuffles an associated array.
	 *
	 * @param array $array
	 * @return bool
	 */

	static public function shuffleAssocArray(&$array) {
		$keys = array_keys($array);
		shuffle($keys);
		foreach ($keys as $key) {
			$new[$key] = $array[$key];
		}
		if (!isset($new)) {
			$new = [];
		}
		$array = $new;
		return true;
	}

	/**
	 * @param string $item
	 * @param string $tag
	 * @param string $mod
	 * @param ILoadBalancer $dbLoadBalancer
	 * @return bool
	 */
	static public function entryExists($item, $tag, $mod, ILoadBalancer $dbLoadBalancer) {
		$dbr = $dbLoadBalancer->getConnection(DB_REPLICA);

		$result = $dbr->select(
			'ext_oredict_items',
			'COUNT(entry_id) AS total',
			[
				'item_name' => $item,
				'tag_name' => $tag,
				'mod_name' => $mod
			],
			__METHOD__
		);
		return $result->current()->total != 0;
	}

	/**
	 * @param $id	Int		The entry ID
	 * @param $dbLoadBalancer ILoadBalancer
	 * @return mixed		See checkExists.
	 */
	static public function checkExistsByID($id, ILoadBalancer $dbLoadBalancer) {
		$dbr = $dbLoadBalancer->getConnection(DB_REPLICA);
		$res = $dbr->select(
			'ext_oredict_items',
			array(
				'item_name',
				'tag_name',
				'mod_name'
			),
			array('entry_id' => $id),
			__METHOD__
		);
		$row = $res->current();
		return OreDict::entryExists($row->item_name, $row->tag_name, $row->mod_name, $dbLoadBalancer);
	}

	/**
	 * Deletes the entry by its ID, and logs it as the user.
	 * @param $id	Int		The entry ID
	 * @param $user User	The user
	 * @param $dbLoadBalancer ILoadBalancer
	 * @return mixed		The first deletion.
	 */
	static public function deleteEntry($id, $user, ILoadBalancer $dbLoadBalancer) {
		$dbw = $dbLoadBalancer->getConnection(DB_PRIMARY);
		$res = $dbw->select('ext_oredict_items', array('tag_name', 'item_name', 'mod_name'), array('entry_id' => $id));
		$row = $res->current();
		$tag = $row->tag_name;
		$item = $row->item_name;
		$mod = $row->mod_name;
		$target = empty($mod) || $mod == '' ? "$tag - $item" : "$tag - $item ($mod)";

		$result = $dbw->delete(
			'ext_oredict_items',
			array('entry_id' => $id),
			__METHOD__
		);

		$logEntry = new ManualLogEntry('oredict', 'delete');
		$logEntry->setPerformer($user);
		$logEntry->setTarget(Title::newFromText("Entry/$target", NS_SPECIAL));
		$logEntry->setParameters(array("6::tag" => $tag, "7::item" => $item, "8::mod" => $mod, "15::id" => $id));
		$logId = $logEntry->insert();
		$logEntry->publish($logId);
		return $result;
	}

	/**
	 * Adds an entry.
	 * @param string $mod Mod name
	 * @param string $name Item name
	 * @param string $tag Tag name
	 * @param User $user User performing the addition
	 * @param ILoadBalancer $dbLoadBalancer
	 * @param string $params Grid parameters
	 * @return bool|int False if it failed to add, or the new ID.
	 */
	static public function addEntry($mod, $name, $tag, $user, ILoadBalancer $dbLoadBalancer, $params = '') {
		if (!self::isStrValid($mod) || !self::isStrValid($name) || !self::isStrValid($tag)) {
			return false;
		}

		$dbw = $dbLoadBalancer->getConnection(DB_PRIMARY);

		$result = $dbw->insert(
			'ext_oredict_items',
			[
				'item_name' => $name,
				'tag_name' => $tag,
				'mod_name' => $mod,
				'grid_params' => $params,
			],
			__METHOD__
		);

		if ($result == false) {
			return false;
		}

		$result = $dbw->select(
			'ext_oredict_items',
			['`entry_id` AS id'],
			[],
			__METHOD__,
			[
				'ORDER BY' => '`entry_id` DESC',
				"LIMIT" => 1
			]
		);
		$lastInsert = intval($result->current()->id);
		$target = ($mod == "" ? "$tag - $name" : "$tag - $name ($mod)");

		// Start log
		$logEntry = new ManualLogEntry('oredict', 'createentry');
		$logEntry->setPerformer($user);
		$logEntry->setTarget(Title::newFromText("Entry/$target", NS_SPECIAL));
		$logEntry->setParameters(array(
			"4::id" => $lastInsert,
			"5::tag" => $tag,
			"6::item" => $name,
			"7::mod" => $mod,
			"8::params" => $params));
		$logEntry->setComment(wfMessage('oredict-import-comment')->text());
		$logId = $logEntry->insert();
		$logEntry->publish($logId);
		// End log
		return $lastInsert;
	}

	/**
	 * @param $str string The string to check
	 * @return bool Whether this string is non-empty and does not contain invalid values.
	 */
	public static function isStrValid($str) {
		return !empty($str) && Title::newFromText($str) !== null;
	}

	/**
	 * Edits an entry based on the data given in the first parameter.
	 * @param $update	array	An array essentially identical to a row. This contains the new data.
	 * @param $id		Int		The entry ID.
	 * @param $user		User	The user performing this edit.
	 * @param $dbLoadBalancer ILoadBalancer
	 * @return int				1 if the update query failed, 2 if there was no change, or 0 if successful.
	 * @throws MWException		See Database#query.
	 */
	static public function editEntry($update, $id, $user, ILoadBalancer $dbLoadBalancer) {
		$dbw = $dbLoadBalancer->getConnection(DB_PRIMARY);
		$stuff = $dbw->select(
			'ext_oredict_items',
			array('*'),
			array('entry_id' => $id),
			__METHOD__
		);
		$row = $stuff->current();

		$tag = empty($update['tag_name']) ? $row->tag_name : $update['tag_name'];
		$item = empty($update['item_name']) ? $row->item_name : $update['item_name'];
		$mod = empty($update['mod_name']) ? $row->mod_name : $update['mod_name'];
		$params = empty($update['grid_params']) ? $row->grid_params : $update['grid_params'];

		// Sanitize input
		if (!self::isStrValid($tag) || !self::isStrValid($item) || !self::isStrValid($mod)) {
			return 1;
		}

		$result = $dbw->update(
			'ext_oredict_items',
			array(
				'tag_name' => $tag,
				'item_name' => $item,
				'mod_name' => $mod,
				'grid_params' => $params
			),
			array('entry_id' => $id),
			__METHOD__
		);

		if ($result == false) {
			return 1;
		}

		// Prepare log vars
		$target = empty($mod) ? "$tag - $item" : "$tag - $item ($mod)";

		$diff = array();
		if ($row->item_name != $item) {
			$diff['item'][] = $row->item_name;
			$diff['item'][] = $item;
		}
		if ($row->mod_name != $mod) {
			$diff['mod'][] = $row->mod_name;
			$diff['mod'][] = $mod;
		}
		if ($row->grid_params != $params) {
			$diff['params'][] = $row->grid_params;
			$diff['params'][] = $params;
		}
		$diffString = "";
		foreach ($diff as $field => $change) {
			$diffString .= "$field [$change[0] -> $change[1]] ";
		}
		if ($diffString == "" || count($diff) == 0) {
			return 2;
		}

		$logEntry = new ManualLogEntry('oredict', 'editentry');
		$logEntry->setPerformer($user);
		$logEntry->setTarget(Title::newFromText("Entry/$target", NS_SPECIAL));
		$logEntry->setParameters(array(
			"6::tag" => $tag,
			"7::item" => $row->item_name,
			"8::mod" => $row->mod_name,
			"9::params" => $row->grid_params,
			"10::to_item" => $item,
			"11::to_mod" => $mod,
			"12::to_params" => $params,
			"13::id" => $id,
			"4::diff" => $diffString,
			"5::diff_json" => json_encode($diff)));
		$logId = $logEntry->insert();
		$logEntry->publish($logId);
		return 0;
	}

	/**
	 * @param $row ?		The row to get the data from.
	 * @return array		An array containing the tag, mod, item, and grid params for use throughout the API.
	 */
	static public function getArrayFromRow($row) {
		return array(
			'tag_name' => $row->tag_name,
			'mod_name' => $row->mod_name,
			'item_name' => $row->item_name,
			'grid_params' => $row->grid_params,
			'id' => intval($row->entry_id)
		);
	}
}

class OreDictItem{
	private $mTagName;
	private $mItemName;
	private $mItemMod;
	private $mItemParams;
	private $mId;

	/**
	 * Contsructor, inits properties
	 *
	 * @param string|stdClass $item
	 * @param string $tag
	 * @param string $mod
	 * @param string $params
	 * @throws MWException Throws and MWException when input format is incorrect.
	 */
	public function __construct($item, $tag = '', $mod = '', $params = '') {
		OreDictError::debug(wfMessage('oredictitem-constructor-debug')->text());
		if (is_object($item))
			if (get_class($item) == "stdClass") {
				if (isset($item->item_name)) {
					$this->mItemName = $item->item_name;
				} else {
					throw new MWException(wfMessage('oredictitem-constructor-error')->params('item_name')->text());
				}
				if (isset($item->mod_name)) {
					$this->mItemMod = $item->mod_name;
				} else {
					throw new MWException(wfMessage('oredictitem-constructor-error')->params('mod_name')->text());
				}
				if (isset($item->tag_name)) {
					 $this->mTagName = $item->tag_name;
				} else {
					throw new MWException(wfMessage('oredictitem-constructor-error')->params('tag_name')->text());
				}
				if (isset($item->grid_params)) {
					$this->mItemParams = OreDictHooks::ParseParamString($item->grid_params);
				} else {
					throw new MWException(wfMessage('oredictitem-constructor-error')->params('grid_params')->text());
				}
				if (isset($item->entry_id)) {
					$this->mId = $item->entry_id;
				} else {
					throw new MWException(wfMessage('oredictitem-constructor-error')->params('entry_id')->text());
				}
				return 0;
			}
		$this->mItemName = $item;
		$this->mTagName = $tag;
		$this->mItemMod = $mod;
		$this->mItemParams = OreDictHooks::ParseParamString($params);
		$this->setMainParams();
		return true;
	}

	/**
	 * Helper function that rejoins and overwrites the default parameters provided in the OreDict.
	 *
	 * @param string $params
	 * @param bool $override
	 */

	public function joinParams($params, $override = false) {
		OreDictError::debug(wfMessage('oredictitem-join-debug')->params($params));
		$input = OreDictHooks::ParseParamString($params);
		foreach ($input as $key => $value) {
			if (isset($this->mItemParams[$key])) {
				if ($override) $this->mItemParams[$key] = $value;
			} else {
				$this->mItemParams[$key] = $value;
			}
		}
		$this->setMainParams();
	}

	/**
	 * Sets the value of a parameter.
	 *
	 * @param string $name
	 * @param string $value
	 */

	public function setParam($name, $value) {
		$this->mItemParams[$name] = $value;
	}

	/**
	 * Removes item from parameter list.
	 *
	 * @param string $name
	 */

	public function unsetParam($name) {
		if (isset($this->mItemParams[$name])) unset($this->mItemParams[$name]);
	}

	/**
	 * @return mixed
	 */
	public function getMod() { return $this->mItemMod; }

	/**
	 * @return mixed
	 */
	public function getItem() { return $this->mItemName; }

	/**
	 * @return mixed
	 */
	public function getItemName() { return $this->mItemName; }

	/**
	 * @return mixed
	 */
	public function getName() { return $this->mItemName; }

	/**
	 * Sets parameters that are not to be overwritten, is called every time join params is called.
	 */

	public function setMainParams() {
		$this->mItemParams[1] = $this->mItemName;
		$this->mItemParams['mod'] = $this->mItemMod;
		$this->mItemParams['ore-dict-name'] = $this->mTagName;
	}

	/**
	 * @return string
	 */

	public function getParamString() {
		return OreDictHooks::BuildParamString($this->mItemParams);
	}
}

class OreDictError{
	static private $mDebug;
	private $mDebugMode;

	/**
	 * @param $debugMode
	 */

	public function __construct($debugMode) {
		$this->mDebugMode = $debugMode;
	}

	/**
	 * Generate errors on edit page preview
	 *
	 * @return string
	 */

	public function output() {
		if (!isset(self::$mDebug)) return "";

		$colors = array(
			"log" => "#CEFFFD",
			"warning" => "#FFFFB5",
			"deprecated" => "#CCF",
			"query" => "#D1FFB3",
			"error" => "#FFCECE",
			"notice" => "blue"
		);

		$textColors = array(
			"log" => "black",
			"warning" => "black",
			"deprecated" => "black",
			"query" => "black",
			"error" => "black",
			"notice" => "white"
		);

		$html = "<table class=\"wikitable\" style=\"width:100%;\">";
		$html .= "<caption>" . wfMessage('oredicterror-heading')->text() . "</caption>";
		$html .= "<tr><th style=\"width:10%;\">" . wfMessage('oredicterror-heading-type')->text() . "</th><th>" . wfMessage('oredicterror-heading-msg')->text() . "</th><tr>";
		$flag = true;
		foreach (self::$mDebug as $message) {
			if (!$this->mDebugMode && $message[0] != "warning" && $message[0] != "error" && $message[0] != "notice") {
				continue;
			}
			$html .= "<tr><td style=\"text-align:center; background-color:{$colors[$message[0]]}; color:{$textColors[$message[0]]}; font-weight:bold;\">" . wfMessage("oredicterror-type-{$message[0]}")->text() . "</td><td>{$message[1]}</td></tr>";
			if ($message[0] == "warnings" || $message[0] == "error") $flag = false;
		}
		if ($flag) {
			$html .= "<tr><td style=\"text-align:center; background-color:blue; color:white; font-weight:bold;\">" . wfMessage('oredicterror-type-notice')->text() . "</td><td>" . wfMessage('oredicterror-none')->text() . "</td></tr>";
		}
		$html .= "</table>";

		return $html;
	}

	/**
	 * @param $message
	 * @param string $type
	 */

	public static function debug($message, $type = "log") {
		self::$mDebug[] = array($type, $message);
	}

	/**
	 * @param $message
	 */

	public static function deprecated($message) {
		MWDebug::deprecated("(OreDict) ".$message);
		self::debug($message, "deprecated");
	}

	/**
	 * @param $query
	 */

	public static function query($query) {
		global $wgShowSQLErrors;

		// Hide queries if debug option is not set in LocalSettings.php
		if ($wgShowSQLErrors)
			self::debug($query, "query");
	}

	/**
	 * @param $message
	 */

	public static function log($message) {
		MWDebug::log("(OreDict) ".$message);
		self::debug($message);
	}

	/**
	 * @param $message
	 */

	public static function warn($message) {
		MWDebug::warning("(OreDict) ".$message);
		self::debug($message, "warning");
	}

	/**
	 * @param $message
	 */

	public static function error($message) {
		MWDebug::warning("(OreDict) "."Error: ".$message);
		self::debug($message, "error");
	}

	/**
	 * @param $message
	 */

	public static function notice($message) {
		MWDebug::warning("(OreDict) "."Notice: ".$message);
		self::debug($message, "notice");
	}
}
