<?php

namespace tests;

use esphinx2\ApiConnection;
use esphinx2\Query;
use esphinx2\enum;
use esphinx2\SearchCriteria;

class ApiSearchTest extends \yii\test\TestCase
{
    public function setUp()
    {
        \Yii::$app->fixture->resetTable('article');
        \Yii::$app->fixture->loadFixture('article');

        exec('./setup.sh');

        parent::setUp();
    }


    /**
     * @return ApiConnection
     */
    protected function createConnection()
    {
        $sphinx = new ApiConnection;
        $sphinx->setServer(array('localhost', 9877));
        $sphinx->init();

        return $sphinx;
    }

    public function testCreate()
    {
        $sphinx = $this->createConnection();
        $this->assertInstanceOf('esphinx2\ApiConnection', $sphinx);
        $this->assertFalse($sphinx->getIsConnected());
    }

    public function testSimpleQuery()
    {
        $sphinx = $this->createConnection();

        $query = new Query('First Article with Title', 'article', array(
            'matchMode' => enum\Match::PHRASE,
        ));

        $result = $sphinx->executeQuery($query);
        $this->assertInstanceOf('esphinx2\Result', $result);
        $this->assertEquals($result->getFound(), 1);

        /** @var ESphinxMatchResult $math */
        $math = $result[0];

        $this->assertEquals($math->getId(), 1);
        $this->assertEquals($math->getAttribute('id'), 1);
    }

    public function testQueryWithParams()
    {
        $sphinx = $this->createConnection();

        $query = new Query('Article with Title', 'article', array(
            'filters' => array(
                array('user_id', array(1000, 2000))
            )
        ));
        $result = $sphinx->executeQuery($query);
        $this->assertInstanceOf('esphinx2\Result', $result);

        $this->assertEquals($result->getFound(), 2);
    }

    public function testQueries()
    {
        $sphinx = $this->createConnection();

        $query1 = new Query('Article with Title', 'article', array('filters' => array(
            array('user_id', array(1000, 2000))
        )));
        $query2 = new Query('Article with Title', 'article', array('filters' => array(
            array('user_id', array(3000, 4000))
        )));

        $result = $sphinx->executeQueries(array($query1, $query2));
        $this->assertCount(2, $result);

        $result1 = $result[0];
        $result2 = $result[1];

        $this->assertEquals($result1->getFoundTotal(), 2);
        $this->assertEquals($result2->getFoundTotal(), 3);

        $this->assertEquals($result1[0]->id, 1);
        $this->assertEquals($result1[1]->id, 2);

        $this->assertEquals($result2[0]->id, 3);
        $this->assertEquals($result2[1]->id, 4);
        $this->assertEquals($result2[2]->id, 5);
    }



    public function testFilters()
    {
        $sphinx = $this->createConnection();

        $query1 = new Query('Article with Title', 'article', array(
            'filters'      => array(array('user_id', array(1000, 2000))),
            'rangeFilters' => array(array('rating', 'min' => 1.4, 'max' => 1.4)),
        ));

        $result = $sphinx->executeQuery($query1);
        $this->assertEquals($result->getFound(), 1);
    }

    public function testIdFilter()
    {
        $sphinx = $this->createConnection();

        $query = new Query('', 'article', array(
            'minId' => 2,
            'maxId' => 3,
        ));

        $result = $sphinx->executeQuery($query);
        $this->assertEquals($result->getFound(), 2);

        $this->assertEquals($result[0]->id, 2);
        $this->assertEquals($result[1]->id, 3);
    }

    public function testSimpleSort()
    {
        $sphinx = $this->createConnection();

        $criteria = new SearchCriteria;
        $criteria->sortMode = enum\Sort::ATTR_DESC;
        $criteria->setSortBy('user_id');

        $query = new Query('', 'article', $criteria);
        $result = $sphinx->executeQuery($query);

        $this->assertEquals($result->getFound(), 5);

        $this->assertEquals($result[0]->id, 4);
        $this->assertEquals($result[1]->id, 5);
        $this->assertEquals($result[2]->id, 3);
        $this->assertEquals($result[3]->id, 2);
        $this->assertEquals($result[4]->id, 1);

        $criteria->sortMode = enum\Sort::ATTR_ASC;

        $query = new Query('', 'article', $criteria);
        $result = $sphinx->executeQuery($query);

        $this->assertEquals($result->getFound(), 5);

        $this->assertEquals($result[0]->id, 1);
        $this->assertEquals($result[1]->id, 2);
        $this->assertEquals($result[2]->id, 3);
        $this->assertEquals($result[3]->id, 4);
        $this->assertEquals($result[4]->id, 5);
    }

    public function testExtendedSort()
    {
        $sphinx = $this->createConnection();
        $criteria = new SearchCriteria;
        $criteria->sortMode = enum\Sort::EXTENDED;

        $criteria->addOrder('user_id', 'ASC');
        $criteria->addOrder('id', 'DESC');

        $query = new Query('', 'article', $criteria);
        $result = $sphinx->executeQuery($query);

        $this->assertEquals($result->getFound(), 5);

        $this->assertEquals($result[0]->id, 1);
        $this->assertEquals($result[1]->id, 2);
        $this->assertEquals($result[2]->id, 3);
        $this->assertEquals($result[3]->id, 5);
        $this->assertEquals($result[4]->id, 4);
    }

    public function testGroupBy()
    {
        $sphinx = $this->createConnection();
        $criteria = new SearchCriteria;
        $criteria->groupBy = 'user_id';
        $criteria->groupBySort = '@count desc';

        $query = new Query('', 'article', $criteria);
        $result = $sphinx->executeQuery($query);

        $this->assertEquals(4, $result->getFound());
        $first = $result[0];


        $this->assertEquals(4000, $first->user_id);
        $this->assertEquals(2, $first->{'@count'});
    }

    public function testLimit()
    {
        $sphinx = $this->createConnection();

        $query = new Query('', 'article', array('limit' => 2));
        $result = $sphinx->executeQuery($query);

        $this->assertEquals(2, count($result));
    }

    public function testMultiQuery()
    {
        $sphinx = $this->createConnection();

        $query1 = new Query('first', 'article', array('limit' => 1));
        $query2 = new Query('second', 'article', array('limit' => 1));


        $result = $sphinx->executeQueries(array($query1, $query2));
        $this->assertCount(2, $result);

        $first  = $result[0][0];
        $second = $result[1][0];

        $this->assertEquals(1, $first->id);
        $this->assertEquals(2, $second->id);
    }

    public function testAddQuery()
    {
        $sphinx = $this->createConnection();

        try {
            $sphinx->runQueries();
            $this->setExpectedException('esphinx2\Exception');
        } catch (\Exception $e) {
            $this->assertInstanceOf('esphinx2\Exception', $e);
        }

        $sphinx->addQuery(new Query('first', 'article', array('limit' => 1)));
        $sphinx->addQuery(new Query('second', 'article', array('limit' => 1)));

        $result = $sphinx->runQueries();

        $this->assertCount(2, $result);

        $first  = $result[0][0];
        $second = $result[1][0];

        $this->assertEquals(1, $first->id);
        $this->assertEquals(2, $second->id);
    }

    public function testExprRanking()
    {
        $sphinx = $this->createConnection();

        $extRank = $sphinx->executeQuery(new Query('', 'article', array(
            'rankingMode'       => enum\Rank::EXPR,
            'rankingExpression' => 'sum(lcs*user_weight)*1000+bm25',
        )));
        $bm025 = $sphinx->executeQuery(new Query('', 'article', array(
            'rankingMode'       => enum\Rank::BM25
        )));

        $this->assertEquals($extRank, $bm025);
    }
}