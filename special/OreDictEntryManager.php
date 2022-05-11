<?php
/**
 * OreDictEntryManager special page file
 *
 * @file
 * @ingroup Extensions
 * @version 1.0.1
 * @author Jinbobo <paullee05149745@gmail.com>
 * @license
 */

class OreDictEntryManager extends SpecialPage {
	public function __construct() {
		parent::__construct('OreDictEntryManager', 'editoredict');
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
		// Restrict access from unauthorized users
		$this->checkPermissions();

		$out = $this->getOutput();
		$out->enableOOUI();

		// Add modules
		$out->addModules( 'ext.oredict.manager' );

		$this->setHeaders();
		$this->outputHeader();

		$opts = new FormOptions();

		$opts->add( 'entry_id', 0 );
		$opts->add( 'tag_name', '' );
		$opts->add( 'item_name', '' );
		$opts->add( 'mod_name', '' );
		$opts->add( 'grid_params', '' );
		$opts->add( 'update', 0 );
		$opts->add('delete', 0);

		$opts->fetchValuesFromRequest( $this->getRequest() );

		// Give precedence to subpage syntax
		if ( isset($par)) {
			$opts->setValue( 'entry_id', $par );
		}

		$out->addHtml($this->outputSearchForm());

		if ($opts->getValue('entry_id') === 0) {
			return;
		}
		if (($opts->getValue('update') == 1 || $opts->getValue('delete') == 1) && $opts->getValue('entry_id') != -1) {
			// XSRF prevention
			if ( !$this->getUser()->matchEditToken( $this->getRequest()->getVal( 'token' ) ) ) {
				return;
			}

			$result = $this->updateEntry($opts);
			// It's obvious if a deletion was successful
			if ($opts->getValue('delete') != 1) {
				switch ($result) {
					case 0: {
						// Success: no op
						break;
					}
					case 1: {
						$out->addWikiText($this->msg('oredict-manager-fail-general')->text());
						break;
					}
					case 2: {
						$out->addWikiText($this->msg('oredict-import-fail-nochange')->text());
						break;
					}
				}
			}
		}
		if ($opts->getValue('update') == 1 && $opts->getValue('entry_id') == -1) {
			$opts->setValue('entry_id', $this->createEntry($opts));
		}

		// Load data
		$dbr = wfGetDB(DB_SLAVE);
		$results = $dbr->select('ext_oredict_items','*',array('entry_id' => $opts->getValue('entry_id')));

		if ($results->numRows() == 0 && $opts->getValue('entry_id') != -1 && $opts->getValue('entry_id') != -2) {
			$out->addWikiText(wfMessage('oredict-manager-fail-norows')->text());
			// $this->>displayUpdateForm();
		} else if ($opts->getValue('entry_id') == -2) {
			$out->addWikiText(wfMessage('oredict-manager-fail-insert')->text());
			$this->displayUpdateForm();
		} else if ($results->numRows() == 1) {
            $this->displayUpdateForm($results->current());
		} else {
            $this->displayUpdateForm();
		}
	}

	private function createEntry(FormOptions $opts) {
		$item = $opts->getValue('item_name');
		$tag = $opts->getValue('tag_name');
		$mod = $opts->getValue('mod_name');
		$params = $opts->getValue('grid_params');

		// Check if exists
		if (OreDict::entryExists($opts->getValue('item_name'), $opts->getValue('tag_name'), $opts->getValue('mod_name'))) {
			return -2;
		}

		$result = OreDict::addEntry($mod, $item, $tag, $this->getUser(), $params);
		return ($result === false ? -2 : $result);
	}

	/**
	 * @param FormOptions $opts
	 * @return bool|int False if it was a deletion or there was nothing to update. These cases are not important for the
	 * 					purposes of this special page.
	 * 					Returns the integer returned by OreDict#editEntry denoting success of the update. This is
	 * 					actually useful.
	 */
	private function updateEntry(FormOptions $opts) {
		$dbw = wfGetDB(DB_MASTER);
		$entryId = intval($opts->getValue('entry_id'));
		$stuff = $dbw->select('ext_oredict_items', '*', array('entry_id' => $entryId));
		$ary = array(
			'tag_name' => $opts->getValue('tag_name'),
			'item_name' => $opts->getValue('item_name'),
			'mod_name' => $opts->getValue('mod_name'),
			'grid_params' => $opts->getValue('grid_params'),
		);
		if ($stuff->numRows() == 0) {
			return false;
		}

		// Try to delete before doing any other processing
		if ($opts->getValue('delete') == 1) {
			OreDict::deleteEntry($entryId, $this->getUser());
			return false;
		}

		return OreDict::editEntry($ary, $entryId, $this->getUser());
	}

	private function displayUpdateForm(stdClass $opts = NULL) {
		$vEntryId = is_object($opts) ? $opts->entry_id : -1;
		$vTagName = is_object($opts) ? $opts->tag_name : '';
		$vTagReadonly = is_object($opts);
		$vItemName = is_object($opts) ? $opts->item_name : '';
		$vModName = is_object($opts) ? $opts->mod_name : '';
		$vGridParams = is_object($opts) ? $opts->grid_params : '';
		$msgFieldsetMain = is_object($opts) ? 'oredict-manager-edit-legend' : 'oredict-manager-create-legend';
		$msgSubmitValue = is_object($opts) ? 'oredict-manager-update' : 'oredict-manager-create';

		$formDescriptor = [
		    'entry_id' => [
		        'type' => 'int',
                'name' => 'entry_id',
                'default' => $vEntryId,
                'readonly' => true,
                'label-message' => 'oredict-manager-entry_id'
            ],
            'tag_name' => [
                'type' => 'text',
                'name' => 'tag_name',
                'default' => $vTagName,
                'readonly' => $vTagReadonly,
                'label-message' => 'oredict-manager-tag_name'
            ],
            'item_name' => [
                'type' => 'text',
                'name' => 'item_name',
                'default' => $vItemName,
                'label-message' => 'oredict-manager-item_name'
            ],
            'mod_name' => [
                'type' => 'text',
                'name' => 'mod_name',
                'default' => $vModName,
                'label-message' => 'oredict-manager-mod_name'
            ],
            'grid_params' => [
                'type' => 'text',
                'name' => 'grid_params',
                'default' => $vGridParams,
                'label-message' => 'oredict-manager-grid_params'
            ]
        ];
		$htmlForm = HTMLForm::factory('ooui', $formDescriptor, $this->getContext());
		$htmlForm
            ->addButton([
                'name' => 'delete',
                'value' => 1,
                'label-message' => 'oredict-manager-entry_del',
                'flags' => ['destructive'],
                'attribs' => ['disabled' => $vEntryId == -1],
                'iconElement' => 'remove'
            ])
            ->addHIddenField('token', $this->getUser()->getEditToken())
            ->setMethod('get')
            ->addHiddenField('update', 1)
            ->setWrapperLegendMsg($msgFieldsetMain)
            ->setId('ext-oredict-manager-form')
            ->setSubmitTextMsg($msgSubmitValue)
            ->setSubmitProgressive()
            ->prepareForm()
            ->displayForm(false);
	}

	private function outputSearchForm() {
		global $wgScript;
		$fieldset = new OOUI\FieldsetLayout([
			'label' => $this->msg('oredict-manager-filter-legend')->text(),
			'items' => [
				new OOUI\FieldLayout(
					new OOUI\TextInputWidget([
						'type' => 'number',
						'name' => 'entry_id',
						'value' => '',
						'min' => '1',
						'id' => 'form-entry-id',
						'icon' => 'search'
					]),
					[
						'label' => $this->msg('oredict-manager-filter-entry_id')->text()
					]
				),
				new OOUI\HorizontalLayout([
					'items' => [
						new OOUI\ButtonInputWidget([
							'label' => $this->msg('oredict-manager-submit')->text(),
							'type' => 'submit'
						]),
						new OOUI\ButtonInputWidget([
							'id' => 'form-create-new',
							'label' => $this->msg('oredict-manager-create-legend')->text(),
							'name' => 'entry_id',
							'value' => -1,
							'type' => 'submit'
						])
					]
				])
			]
		]);
		$form = new OOUI\FormLayout([
			'method' => 'GET',
			'action' => $wgScript,
			'id' => 'ext-oredict-manager-filter'
		]);
		$form->appendContent(
			$fieldset,
			new OOUI\HtmlSnippet(Html::hidden('title', $this->getPageTitle()->getPrefixedText()))
		);

		return new OOUI\PanelLayout([
			'classes' => ['entry-manager-filter-wrapper'],
			'framed' => true,
			'expanded' => false,
			'padded' => true,
			'content' => $form
		]);
	}
}
