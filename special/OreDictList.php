<?php
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

	public function __construct() {
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
		$dbr = wfGetDB(DB_SLAVE);
		$results =  $dbr->select(
			'ext_oredict_items',
			'COUNT(`entry_id`) AS row_count',
			array(
				'entry_id >= '.$from,
				'(mod_name = '.$dbr->addQuotes($mod).' OR '.$dbr->addQuotes($mod).' = \'\' OR'.
				'('.$dbr->addQuotes($mod).' = \'none\' AND mod_name = \'\'))',
				'(tag_name = '.$dbr->addQuotes($tag).' OR '.$dbr->addQuotes($tag).' = \'\')',
				'item_name BETWEEN '.$dbr->addQuotes($start)." AND 'zzzzzzzz'"
			),
			__METHOD__,
			array('LIMIT' => $limit)
		);
		foreach ($results as $result) {
			$maxRows = $result->row_count;
		}

		if (!isset($maxRows)) {
			return;
		}

		$begin = $page * $limit;
		$end = min($begin + $limit, $maxRows);
		$order = $start == '' ? 'entry_id ASC' : 'item_name ASC';
		$results = $dbr->select(
			'ext_oredict_items',
			'*',
			array(
				'entry_id >= '.$from,
				'(mod_name = '.$dbr->addQuotes($mod).' OR '.$dbr->addQuotes($mod).' = \'\' OR ('.
				$dbr->addQuotes($mod).' = \'none\' AND mod_name = \'\'))',
				'(tag_name = '.$dbr->addQuotes($tag).' OR '.$dbr->addQuotes($tag).' = \'\')',
				'item_name BETWEEN '.$dbr->addQuotes($start)." AND 'zzzzzzzz'"
			),
			__METHOD__,
			array(
				'ORDER BY' => $order,
				'LIMIT' => $limit,
				'OFFSET' => $begin
			)
		);

		// Output table
		$table = "{| class=\"mw-datatable\" style=\"width:100%\"\n";
		$msgTagName = wfMessage('oredict-tag-name');
		$msgItemName = wfMessage('oredict-item-name');
		$msgModName = wfMessage('oredict-mod-name');
		$msgGridParams = wfMessage('oredict-grid-params');
		$canEdit = in_array("editoredict", $this->getUser()->getRights());
		$table .= "! !! # !! $msgTagName !! $msgItemName !! $msgModName !! $msgGridParams\n";
		foreach ($results as $result) {
			$lId = $result->entry_id;
			$lTag = $result->tag_name;
			$lItem = $result->item_name;
			$lMod = $result->mod_name;
			$lParams = $result->grid_params;
			$table .= "|-\n";
			// Check user rights
			if ($canEdit) {
				$msgEdit = wfMessage('oredict-list-edit')->text();
				$editLink = "[[Special:OreDictEntryManager/$lId|$msgEdit]]";
			} else {
				$editLink = "";
			}
			$table .= "| style=\"width:23px; padding-left:5px; padding-right:5px; text-align:center; font-weight:bold;\" | $editLink || $lId || $lTag || $lItem || $lMod || $lParams\n";
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

		$out->addHtml($this->buildForm($opts));
		$out->addWikiText(wfMessage('oredict-list-displaying', $begin, $end, $maxRows)->text() . " $pageSelection\n");
		$out->addWikitext($table);

		// Add modules
		$out->addModules( 'ext.oredict.list' );
	}

	const SIZES = [
		['data' => 20],
		['data' => 50],
		['data' => 100],
		['data' => 250],
		['data' => 500],
		['data' => 5000]
	];

	public function buildForm(FormOptions $opts) {
		global $wgScript;

		$fieldset = new OOUI\FieldsetLayout([
			'label' => $this->msg('oredict-list-legend')->text(),
			'items' => [
				new OOUI\FieldLayout(
					new OOUI\TextInputWidget([
						'type' => 'number',
						'name' => 'from',
						'value' => $opts->getValue('from'),
						'min' => '1',
						'id' => 'from'
					]),
					['label' => $this->msg('oredict-list-from')->text()]
				),
				new OOUI\FieldLayout(
					new OOUI\TextInputWidget([
						'name' => 'start',
						'value' => $opts->getValue('start'),
						'id' => 'start'
					]),
					['label' => $this->msg('oredict-list-start')->text()]
				),
				new OOUI\FieldLayout(
					new OOUI\TextInputWidget([
						'name' => 'tag',
						'value' => $opts->getValue('tag'),
						'id' => 'tag'
					]),
					['label' => $this->msg('oredict-list-tag')->text()]
				),
				new OOUI\FieldLayout(
					new OOUI\TextInputWidget([
						'name' => 'mod',
						'value' => $opts->getValue('mod'),
						'id' => 'mod'
					]),
					['label' => $this->msg('oredict-list-mod')->text()]
				),
				new OOUI\FieldLayout(
					new OOUI\DropdownInputWidget([
						'options' => self::SIZES,
						'value' => $opts->getValue('limit')
					]),
					['label' => $this->msg('oredict-list-limit')->text()]
				),
				new OOUI\ButtonInputWidget([
					'type' => 'submit',
					'label' => $this->msg('oredict-list-submit')->text(),
					'flags' => ['primary', 'progressive']
				])
			]
		]);

		$form = new OOUI\FormLayout([
			'method' => 'GET',
			'action' => $wgScript,
			'id' => 'ext-oredict-list-filter',
		]);
		$form->appendContent(
			$fieldset,
			new OOUI\HtmlSnippet(Html::hidden('title', $this->getTitle()->getPrefixedText()))
		);
		return new OOUI\PanelLayout([
			'classes' => ['oredictlist-filter-wrapper'],
			'framed' => true,
			'expanded' => false,
			'padded' => true,
			'content' => $form
		]);
	}
}
