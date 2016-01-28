<?php
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
	private $mCallType;
	private $mRawArray;

	static private $mQueries;

	// Bit 0: Call type: cell
	// Bit 1: Call type: tag
	// Bit 2: Display type: cell
	// Bit 3: Display type: tag

	// Bit 4: Randomize output
	// Bit 5:
	// Bit 6: [Tag] Call type: cell
	// Bit 7: [Tag] Call type: tag

	// Default flag: 0b11001111
	const CALL_GRID = 0x01;
	const CALL_TAG = 0x02;

	const DISP_GRID = 0x04;
	const DISP_TAG = 0x08;

	const CTRL_RAND = 0x10;

	const MASK_CALL = 0x03;
	const MASK_DISP = 0x0c;
	const MASK_CTRL = 0x30;
	const MASK_FLAG = 0xc0;

	const FLAG_DEFAULT = 0xcf;

	const MODE_GRID = 0x45;
	const MODE_TAG = 0x8a;
	const MODE_FORCE = 0xcf;

	const FLAG_DEL = 0x100;

	/**
	 * Init properties.
	 *
	 * @param string $itemName
	 * @param string $itemMod
	 * @param int $callType
	 */

	public function __construct($itemName, $itemMod = '', $callType = OreDict::CALL_GRID) {
		$this->mItemName = $itemName;
		$this->mOutputLimit = 20;
		$this->mCallType = $callType;
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
				if (get_class($item) == "OreDictList") {
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
		wfRunHooks("OreDictOutput", array(&$out, $this->mRawArray, $params));
		return array($out, 'noparse' => false, 'isHTML' => false);
	}

	/**
	 * Queries the OreDict table
	 *
	 * @param bool $byTag
	 * @return bool
	 */

	public function exec($byTag = false) {
		// Get db object
		$dbr = wfGetDB(DB_SLAVE);

		// Masks
		$mfTag = OreDict::MASK_FLAG;
		$mfCall = OreDict::MASK_CALL;
		$mfDisp = OreDict::MASK_DISP;
		$mfCtrl = OreDict::MASK_CTRL;
		$fDel = OreDict::FLAG_DEL;

		// Vars
		$fItem = $dbr->addQuotes($this->mItemName); // This will be tag name if mode is call by tag
		$fMod = $dbr->addQuotes($this->mItemMod);
		$fType = $this->mCallType;
		//$fTableName = $dbr->tableName("ext_oredict_items");
		$fTableName = "ext_oredict_items";

		$sLim = $this->mOutputLimit;
		if ($mfCtrl & $fType & OreDict::CTRL_RAND) {
			$sRand = "RAND()";
		} else {
			$sRand = "entry_id";
		}

		// Generate dummy entry
		if ($fType == 0x00) {
			self::$mQueries[$fItem][$fMod][$fType][] = new OreDictItem($this->mItemName, '', $this->mItemMod,'',0xcf);
		}

		// Query database
		if (!isset(self::$mQueries[$fItem][$fMod][$fType])) {
			if ($byTag) {
				OreDictError::notice("Querying the ore dictionary for Tag = $fItem Mod = $fMod (Call type = $fType)");
				//$query = "SELECT * FROM $fTableName WHERE `tag_name` = $fItem AND ($fMod = '' OR `mod_name` = $fMod) AND ($mfTag & $fType & `flags`) AND ($mfCall & $fType & `flags`) AND ($mfDisp & $fType & `flags`) AND NOT($fDel & `flags`) $sRand $sLim";

				$result = $dbr->select(
					$fTableName,
					"*",
					[
						'tag_name' => $fItem,
						$dbr->makeList([$fMod => '','mod_name' => $fMod], LIST_OR),
						"($mfTag & $fType & `flags`)",
						"($mfCall & $fType & `flags`)",
						"($mfDisp & $fType & `flags`)",
						"NOT ($fDel & `flags`)"
				 	],
					__METHOD__,
					[
						"ORDER BY" => $sRand,
						"LIMIT" => $sLim,
						"OFFSET" => 0
					]
				);

			} else {
				OreDictError::notice("Querying the ore dictionary for Item = $fItem Mod = $fMod (Call type = $fType)");
				//$query = "SELECT * FROM $fTableName WHERE `tag_name` IN (SELECT `tag_name` FROM $fTableName WHERE `item_name` = $fItem AND ($fMod = '' OR `mod_name` = $fMod) AND ($mfTag & $fType & `flags`) AND ($mfCall & $fType & `flags`) AND NOT($fDel & `flags`)) AND ($mfDisp & $fType & `flags`) AND NOT($fDel & `flags`) $sRand $sLim";

				$subResult = $dbr->select(
					$fTableName,
					'tag_name',
					[
						'item_name' => $fItem,
						$dbr->makeList([$fMod => '','mod_name' => $fMod], LIST_OR),
						"($mfTag & $fType & `flags`)",
						"($mfCall & $fType & `flags`)",
						"NOT($fDel & `flags`)"
					],
					__METHOD__
				);

				$tagNameList = [];

				foreach ($subResult as $r) {
					$tagNameList[] = $r['tag_name'];
				}

				$result = $dbr->select(
					$fTableName,
					"*",
					[
						'`tag_name` IN ('.$dbr->makeList($tagNameList, LIST_COMMA).')',
						"($mfDisp & $fType & `flags`)",
						"NOT($fDel & `flags`)"
				 	],
					__METHOD__,
					[
						"ORDER BY" => $sRand,
						"LIMIT" => $sLim,
						"OFFSET" => 0
					]
				);

			}
			//OreDictError::query($query);
			//$result = $dbr->query($query);
			foreach ($result as $row) {
				self::$mQueries[$fItem][$fMod][$fType][] = new OreDictItem($row);
			}

			if (!isset(self::$mQueries[$fItem][$fMod][$fType])) {
				self::$mQueries[$fItem][$fMod][$fType][] = new OreDictItem($this->mItemName, '', $this->mItemMod,'',0xcf);
				OreDictError::notice("OreDict returned an empty set (i.e. 0 rows)! Using provided params as is. Suppressing future identical warnings.");
			} else {
				$rows = $result->numRows();
				OreDictError::notice("OreDict returned $rows rows. ");
			}
		}

		// Rotate results if not randomized
		if (!($mfCtrl & $fType & OreDict::CTRL_RAND) && !$byTag) {
			while (current(self::$mQueries[$fItem][$fMod][$fType])->getItemName() != $this->mItemName && (empty($this->mItemMod) || current(self::$mQueries[$fItem][$fMod][$fType])->getMod() == $this->mItemMod)) {
				array_push(self::$mQueries[$fItem][$fMod][$fType], array_shift(self::$mQueries[$fItem][$fMod][$fType]));
			}
		}

		$this->mRawArray = self::$mQueries[$fItem][$fMod][$fType];
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
		if (!isset($new)) $new = array();
		$array = $new;
		return true;
	}

	static public function checkExists($item, $tag, $mod) {
		$dbr = wfGetDB(DB_SLAVE);

		$result = $dbr->select(
			'ext_oredict_items',
			'COUNT(entry_id) AS count',
			[
				'item_name' => $item,
				'tag_name' => $tag,
				'mod_name' => $mod
			]
		);
		return $result->current()->count;
	}
}

class OreDictItem{
	private $mTagName;
	private $mItemName;
	private $mItemMod;
	private $mItemParams;
	private $mId;
	private $mFlags;

	/**
	 * Contsructor, inits properties
	 *
	 * @param string|stdClass $item
	 * @param string $tag
	 * @param string $mod
	 * @param string $params
	 * @param string $flags
	 * @throws MWException Throws and MWException when input format is incorrect.
	 */

	public function __construct($item, $tag = '', $mod = '', $params = '', $flags = '') {
		OreDictError::debug("Constructing OreDictItem.");
		if (is_object($item))
			if (get_class($item) == "stdClass") {
				if (isset($item->item_name)) {
					$this->mItemName = $item->item_name;
				} else {
					throw new MWException("Incorrect input format! Missing property \"item_name\" in stdClass.");
				}
				if (isset($item->mod_name)) {
					$this->mItemMod = $item->mod_name;
				} else {
					throw new MWException("Incorrect input format! Missing property \"mod_name\" in stdClass.");
				}
				if (isset($item->tag_name)) {
					 $this->mTagName = $item->tag_name;
				} else {
					throw new MWException("Incorrect input format! Missing property \"tag_name\" in stdClass.");
				}
				if (isset($item->flags)) {
					$this->mFlags = $item->flags;
				} else {
					throw new MWException("Incorrect input format! Missing property \"flags\" in stdClass.");
				}
				if (isset($item->grid_params)) {
					$this->mItemParams = OreDictHooks::ParseParamString($item->grid_params);
				} else {
					throw new MWException("Incorrect input format! Missing property \"grid_params\" in stdClass.");
				}
				if (isset($item->entry_id)) {
					$this->mId = $item->entry_id;
				} else {
					throw new MWException("Incorrect input format! Missing property \"entry_id\" in stdClass.");
				}
				return 0;
			}
		$this->mItemName = $item;
		$this->mTagName = $tag;
		$this->mItemMod = $mod;
		$this->mItemParams = OreDictHooks::ParseParamString($params);
		$this->mFlags = $flags;
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
		OreDictError::debug("Joining params: $params.");
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
		$this->mItemParams['entry-flags'] = $this->mFlags;
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
			"Log" => "#CEFFFD",
			"Warning" => "#FFFFB5",
			"Deprecated" => "#CCF",
			"Query" => "#D1FFB3",
			"Error" => "#FFCECE",
			"Notice" => "blue"
		);

		$textColors = array(
			"Log" => "black",
			"Warning" => "black",
			"Deprecated" => "black",
			"Query" => "black",
			"Error" => "black",
			"Notice" => "white"
		);

		$html = "<table class=\"wikitable\" style=\"width:100%;\">";
		$html .= "<caption>OreDict extension warnings</caption>";
		$html .= "<tr><th style=\"width:10%;\">Type</th><th>Message</th><tr>";
		$flag = true;
		foreach (self::$mDebug as $message) {
			if (!$this->mDebugMode && $message[0] != "Warning" && $message[0] != "Error" && $message[0] != "Notice") {
				continue;
			}
			$html .= "<tr><td style=\"text-align:center; background-color:{$colors[$message[0]]}; color:{$textColors[$message[0]]}; font-weight:bold;\">{$message[0]}</td><td>{$message[1]}</td></tr>";
			if ($message[0] == "Warnings" || $message[0] == "Error") $flag = false;
		}
		if ($flag) {
			$html .= "<tr><td style=\"text-align:center; background-color:blue; color:white; font-weight:bold;\">Notice</td><td>No warnings.</td></tr>";
		}
		$html .= "</table>";

		return $html;
	}

	/**
	 * @param $message
	 * @param string $type
	 */

	public static function debug($message, $type = "Log") {
		self::$mDebug[] = array($type, $message);
	}

	/**
	 * @param $message
	 */

	public static function deprecated($message) {
		MWDebug::deprecated("(OreDict) ".$message);
		self::debug($message, "Deprecated");
	}

	/**
	 * @param $query
	 */

	public static function query($query) {
		global $wgShowSQLErrors;

		// Hide queries if debug option is not set in LocalSettings.php
		if ($wgShowSQLErrors)
			self::debug($query, "Query");
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
		self::debug($message, "Warning");
	}

	/**
	 * @param $message
	 */

	public static function error($message) {
		MWDebug::warning("(OreDict) "."Error: ".$message);
		self::debug($message, "Error");
	}

	/**
	 * @param $message
	 */

	public static function notice($message) {
		MWDebug::warning("(OreDict) "."Notice: ".$message);
		self::debug($message, "Notice");
	}
}
