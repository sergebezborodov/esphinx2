<?php

namespace tests;

use esphinx2;
use esphinx2\Query;
use esphinx2\SearchCriteria;
use esphinx2\enum;

class QueryTest extends \yii\test\TestCase
{
    public function testCreate()
    {
        $query = new Query('text', 'index');
        $this->assertEquals($query->getText(), 'text');
        $this->assertEquals($query->getIndexes(), 'index');
    }

    public function testWithCriteria()
    {
        $criteriaData = array(
            'rankingMode' => enum\Rank::BM25,
            'sortMode'    => enum\Sort::ATTR_DESC,
            'sortBy'      => 'field1',
        );
        $criteria = new SearchCriteria($criteriaData);
        $query = new Query('text', 'index', $criteria);
        $this->assertEquals($criteria, $query->getCriteria());
    }
}
