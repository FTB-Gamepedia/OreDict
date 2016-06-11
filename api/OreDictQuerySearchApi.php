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
            'from' => array(
                ApiBase::PARAM_TYPE => 'integer',
                ApiBase::PARAM_MIN => 0,
                ApiBase::PARAM_DFLT => 0,
            )
        );
    }

    public function getParamDescription() {
        return array(
            'limit' => 'The maximum number of entries to list',
            'prefix' => 'Restricts results to those that have a tag alphabetically after the prefix',
            'mod' => 'Restricts results to this mod',
            'tag' => 'Restricts results to this tag name',
            'name' => 'Restricts results to this item name',
            'from' => 'The entry ID to start listing at.',
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
        $from = $this->getParameter('from');
        $dbr = wfGetDB(DB_SLAVE);

        $conditions = array(
            "entry_id >= $from"
        );

        if ($prefix != '') {
            $conditions[] = 'item_name BETWEEN ' . $dbr->addQuotes($prefix) . " AND 'zzzzzzzzz'";
        }
        if ($tag != '') {
            $conditions['tag_name'] = $tag;
        }
        if ($mod != '') {
            $conditions['mod_name'] = $mod;
        }
        if ($name != '') {
            $conditions['item_name'] = $name;
        }

        $results = $dbr->select(
            'ext_oredict_items',
            '*',
            $conditions,
            __METHOD__,
            array(
                'LIMIT' => $limit + 1,
            )
        );

        $ret = array();

        $count = 0;
        foreach ($results as $res) {
            $count++;
            if ($count > $limit) {
                $this->setContinueEnumParameter('from', $res->entry_id);
                break;
            }
            $ret[] = OreDict::getArrayFromRow($res);
        }

        $this->getResult()->addValue('query', 'totalhits', $results->numRows());
        $this->getResult()->addValue('query', 'oredictentries', $ret);
    }
}
