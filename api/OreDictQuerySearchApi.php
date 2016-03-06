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
            'offset' => array(
                ApiBase::PARAM_TYPE => 'integer',
                ApiBase::PARAM_DFLT => 0,
                ApiBase::PARAM_MIN => 0,
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
            'offset' => 'The query offset, like a continue param.'
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
        $offset = $this->getParameter('offset');
        $dbr = wfGetDB(DB_SLAVE);

        $conditions = array();
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
            __METHOD__
        );

        $ret = array();

        $i = 0;
        $more = $results->numRows() - $offset > $limit;
        if ($results->numRows() > 0) {
            foreach ($results as $row) {
                if ($i < $offset) {
                    $i++;
                    continue;
                }
                if (count($ret) < $limit) {
                    $i++;
                    $ret[] = OreDict::getArrayFromRow($row);
                }
            }
        }

        if ($more) {
            $this->getResult()->addValue('continue', 'offset', $i);
        }

        $this->getResult()->addValue('query', 'totalhits', $results->numRows());
        $this->getResult()->addValue('query', 'oredictentries', $ret);
    }
}
