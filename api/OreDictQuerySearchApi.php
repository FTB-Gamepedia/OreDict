<?php

class OreDictQuerySearchApi extends ApiQueryBase {
    public function __construct($query, $moduleName) {
        parent::__construct($query, $moduleName, 'od');
    }

    public function getAllowedParams() {
        return array(
            'limit' => array(
                ApiBase::PARAM_TYPE => 'limit',
                ApiBase::PARAM_DFLT => 10,
                ApiBase::PARAM_MIN => 1,
                ApiBase::PARAM_MAX => ApiBase::LIMIT_BIG1,
                ApiBase::PARAM_MAX2 => ApiBase::LIMIT_BIG2,
            ),
            'prefix' => array(
                ApiBase::PARAM_TYPE => 'string',
                ApiBase::PARAM_DFLT => '',
            ),
            'mod' => array(
                ApiBase::PARAM_TYPE => 'string',
                ApiBase::PARAM_DFLT => '',
            ),
            'tag' => array(
                ApiBase::PARAM_TYPE => 'string',
                ApiBase::PARAM_DFLT => '',
            ),
            'name' => array(
                ApiBase::PARAM_TYPE => 'string',
                ApiBase::PARAM_DFLT => '',
            ),
        );
    }

    public function getParamDescription() {
        return array(
            'limit' => 'The maximum number of entries to list',
            'prefix' => 'Restricts results to those that have a tag alphabetically after the prefix',
            'mod' => 'Restricts results to this mod',
            'tag' => 'Restricts results to this tag name',
            'name' => 'Restricts results to this item name',
        );
    }

    public function getDescription() {
        return 'Searches for OreDict entries that meet specific criteria defined by the optional parameters';
    }

    public function getExamples() {
        return array(
            'api.php?action=query&list=oredictsearch&odmod=V&odtag=logWood',
            'api.php?action=query&list=oredictsearch&odname=Oak Wood Log&odmod=V',
        );
    }

    public function execute() {
        $prefix = $this->getParameter('prefix');
        $tag = $this->getParameter('tag');
        $mod = $this->getParameter('mod');
        $name = $this->getParameter('name');
        $limit = $this->getParameter('limit');
        $dbr = wfGetDB(DB_SLAVE);

        $resultPrefix = $dbr->select(
            'ext_oredict_items',
            '*',
            array('item_name BETWEEN '.$dbr->addQuotes($prefix)." AND 'zzzzzzzz'"),
            __METHOD__,
            array('LIMIT' => $limit)
        );
        $resultTag = $dbr->select(
            'ext_oredict_items',
            '*',
            array('tag_name' => $tag),
            __METHOD__,
            array('LIMIT' => $limit)
        );
        $resultMod = $dbr->select(
            'ext_oredict_items',
            '*',
            array('mod_name' => $mod),
            __METHOD__,
            array('Limit' => $limit)
        );
        $resultName = $dbr->select(
            'ext_oredict_items',
            '*',
            array('item_name' => $name),
            __METHOD__,
            array('LIMIT' => $limit)
        );

        $ret = array();

        if ($resultTag->numRows() > 0) {
            foreach ($resultTag as $row) {
                $ret[$row->entry_id] = OreDict::getArrayFromRow($row);
            }
        }

        if ($resultMod->numRows() > 0) {
            foreach ($resultMod as $row) {
                $ret[$row->entry_id] = OreDict::getArrayFromRow($row);
            }
        }

        if ($resultName->numRows() > 0) {
            foreach ($resultName as $row) {
                $ret[$row->entry_id] = OreDict::getArrayFromRow($row);
            }
        }

        if ($resultPrefix->numRows() > 0) {
            foreach ($resultPrefix as $row) {
                $ret[$row->entry_id] = OreDict::getArrayFromRow($row);
            }
        }

        $this->getResult()->addValue('query', 'oredictentries', $ret);
    }
}