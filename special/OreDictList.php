<?php

use MediaWiki\Html\FormOptions;
use Wikimedia\Rdbms\ILoadBalancer;
use MediaWiki\Permissions\PermissionManager;

/**
 * OreDictList special page file
 *
 * @file
 * @ingroup Extensions
 * @version 1.0.1
 * @author Jinbobo <paullee05149745@gmail.com>
 * @license
 */

class OreDictList extends SpecialPage {
	protected $opts;

	public function __construct(private ILoadBalancer $dbLoadBalancer, private PermissionManager $permissionManager) {
		parent::__construct('OreDictList');
	}

	/**
	 * Return the group name for this special page.
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function getGroupName() {
		return 'oredict';
	}

	public function execute($par) {
		global $wgQueryPageDefaultLimit;
		$out = $this->getOutput();
		$out->enableOOUI();

		$this->setHeaders();
		$this->outputHeader();

		$opts = new FormOptions();

		$opts->add( 'limit', $wgQueryPageDefaultLimit );
		$opts->add( 'mod', '' );
		$opts->add( 'tag', '' );
		$opts->add( 'start', '' );
		$opts->add( 'from', 1 );
		$opts->add( 'page', 0 );

		$opts->fetchValuesFromRequest( $this->getRequest() );
		$opts->validateIntBounds( 'limit', 0, 5000 );

		// Give precedence to subpage syntax
		if ( isset($par) ) {
			$opts->setValue( 'from', $par );
		}

		// Bind to member variable
		$this->opts = $opts;

		$limit = intval($opts->getValue('limit'));
		$mod = $opts->getValue('mod');
		$tag = $opts->getValue('tag');
		$start = $opts->getValue('start');
		$from = intval($opts->getValue('from'));
		$page = intval($opts->getValue('page'));

		// Load data
		$dbr = $this->dbLoadBalancer->getConnection(DB_REPLICA);
		$maxRows = $dbr->newSelectQueryBuilder()
			->select('COUNT(`entry_id`)')
			->from('ext_oredict_items')
			->where(array(
				'entry_id >= ' . $from,
				'(mod_name = ' . $dbr->addQuotes($mod) . ' OR ' . $dbr->addQuotes($mod) . " = '' OR " .
					'(' . $dbr->addQuotes($mod) . " = 'none' AND mod_name = ''))",
				'(tag_name = ' . $dbr->addQuotes($tag) . ' OR ' . $dbr->addQuotes($tag) . " = '')",
				'item_name BETWEEN ' . $dbr->addQuotes($start) . " AND 'zzzzzzzz'"
			))
			->caller(__METHOD__)
			->limit($limit)
			->fetchField();

		if (!$maxRows) return;

		$begin = $page * $limit;
		$end = min($begin + $limit, $maxRows);
		$order = $start == '' ? 'entry_id ASC' : 'item_name ASC';
		$results = $dbr->newSelectQueryBuilder()
			->select('*')
			->from('ext_oredict_items')
			->where(array(
				'entry_id >= ' . $from,
				'(mod_name = ' . $dbr->addQuotes($mod) . ' OR ' . $dbr->addQuotes($mod) . " = '' OR (" .
					$dbr->addQuotes($mod) . " = 'none' AND mod_name = ''))",
				'(tag_name = ' . $dbr->addQuotes($tag) . ' OR ' . $dbr->addQuotes($tag) . " = '')",
				'item_name BETWEEN ' . $dbr->addQuotes($start) . " AND 'zzzzzzzz'"
			))
			->caller(__METHOD__)
			->orderBy($order)
			->limit($limit)
			->offset($begin)
			->fetchResultSet();

		// Output table
		$table = "{| class=\"mw-datatable\" style=\"width:100%\"\n";
		$msgTagName = wfMessage('oredict-tag-name');
		$msgItemName = wfMessage('oredict-item-name');
		$msgModName = wfMessage('oredict-mod-name');
		$msgGridParams = wfMessage('oredict-grid-params');
		$canEdit = $this->permissionManager->userHasRight($this->getUser(), 'editoredict');
		$table .= "!";
		if ($canEdit) {
			$table .= " !!";
		}
		$table .= " # !! $msgTagName !! $msgItemName !! $msgModName !! $msgGridParams\n";
		$linkStyle = "style=\"width:23px; padding-left:5px; padding-right:5px; text-align:center; font-weight:bold;\"";
		$lastID = 0;
		foreach ($results as $result) {
			$lId = $result->entry_id;
			$lTag = $result->tag_name;
			$lItem = $result->item_name;
			$lMod = $result->mod_name;
			$lParams = $result->grid_params;
			$table .= "|-\n| ";
			// Check user rights
			if ($canEdit) {
				$msgEdit = wfMessage('oredict-list-edit')->text();
				$editLink = "[[Special:OreDictEntryManager/$lId|$msgEdit]]";
				$table .= "$linkStyle | $editLink || ";
			}
			$table .= "$lId || $lTag || $lItem || $lMod || $lParams\n";

			$lastID = $lId;
		}
		$table .= "|}\n";

		// Page nav stuff
		$pPage = $page-1;
		$nPage = $page+1;
		$lPage = max(floor(($maxRows - 1) / $limit), 0);
		$settings = 'start='.$start.'&mod='.$mod.'&limit='.$limit.'&tag='.$tag.'&from='.$from;
		if ($page == 0) {
			$prevPage = "'''" . wfMessage('oredict-list-first')->text() . "'''";
		} else {
			$firstPageArrow = wfMessage('oredict-list-first-arrow')->text();
			if ($page == 1) {
				$prevPage = '[{{fullurl:{{FULLPAGENAME}}|'.$settings.'}} ' . $firstPageArrow . ']';
			} else {
				$prevPage = '[{{fullurl:{{FULLPAGENAME}}|'.$settings.
					"}} $firstPageArrow] [{{fullurl:{{FULLPAGENAME}}|page={$pPage}&".$settings.
					'}} ' . wfMessage('oredict-list-prev')->text() . ']';
			}
		}
		if ($lPage == $page) {
			$nextPage = "'''" . wfMessage('oredict-list-last')->text() . "'''";
		} else {
			$lastPageArrow = wfMessage('oredict-list-last-arrow')->text();
			if ($lPage == $page + 1) {
				$nextPage = "[{{fullurl:{{FULLPAGENAME}}|page={$nPage}&".$settings.'}} ' . $lastPageArrow . ']';
			} else {
				$nextPage = "[{{fullurl:{{FULLPAGENAME}}|page={$nPage}&".$settings.
					'}} ' . wfMessage('oredict-list-next')->text() . "] [{{fullurl:{{FULLPAGENAME}}|page={$lPage}&".$settings.
					'}} ' . $lastPageArrow . ']';
			}
		}
		$pageSelection = '<div style="text-align:center;" class="plainlinks">'.$prevPage.' | '.$nextPage.'</div>';

		$this->displayForm($opts);
		if ($maxRows == 0) {
			$out->addWikiTextAsInterface(wfMessage('oredict-list-display-none')->text());
		} else {
			// We are currently at the end from the iteration earlier in the function, so we have to go back to get the
			// first row's entry ID.
			$results->rewind();
			$firstID = $results->current()->entry_id;
			$out->addWikiTextAsInterface(wfMessage('oredict-list-displaying', $firstID, $lastID, $maxRows)->text());
		}
		$out->addWikiTextAsInterface(" $pageSelection\n");
		$out->addWikiTextAsInterface($table);

		// Add modules
		$out->addModules( 'ext.oredict.list' );
	}

	public function displayForm(FormOptions $opts) {
		$lang = $this->getLanguage();
		$formDescriptor = [
			'from' => [
				'type' => 'int',
				'name' => 'from',
				'default' => $opts->getValue('from'),
				'min' => 1,
				'id' => 'from',
				'label-message' => 'oredict-list-from'
			],
			'start' => [
				'type' => 'text',
				'name' => 'start',
				'default' => $opts->getValue('start'),
				'id' => 'start',
				'label-message' => 'oredict-list-start'
			],
			'tag' => [
				'type' => 'text',
				'name' => 'tag',
				'default' => $opts->getValue('tag'),
				'id' => 'tag',
				'label-message' => 'oredict-list-tag'
			],
			'mod' => [
				'type' => 'text',
				'name' => 'mod',
				'default' => $opts->getValue('mod'),
				'id' => 'mod',
				'label-message' => 'oredict-list-mod'
			],
			'limit' => [
				'type' => 'limitselect',
				'name' => 'limit',
				'label-message' => 'oredict-list-limit',
				'options' => [
					$lang->formatNum(20) => 20,
					$lang->formatNum(50) => 50,
					$lang->formatNum(100) => 100,
					$lang->formatNum(250) => 250,
					$lang->formatNum(500) => 500,
					$lang->formatNum(5000) => 5000
				],
				'default' => $opts->getValue('limit')
			]
		];

		$htmlForm = HTMLForm::factory('ooui', $formDescriptor, $this->getContext());
		$htmlForm
			->setMethod('get')
			->setWrapperLegendMsg('oredict-list-legend')
			->setId('ext-oredict-list-filter')
			->setSubmitTextMsg('oredict-list-submit')
			->prepareForm()
			->displayForm(false);
	}
}
