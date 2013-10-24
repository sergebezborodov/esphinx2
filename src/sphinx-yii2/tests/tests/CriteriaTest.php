<?php

namespace tests;

use esphinx2\enum;
use esphinx2\SearchCriteria;


/**
 * Tests for sphinx criteria
 */
class CriteriaTest extends \yii\test\TestCase
{
    public function testFilters()
    {
        $criteria = new SearchCriteria;

        try {
            $criteria->addFilters('');
            $this->setExpectedException('esphinx2\Exception');
        } catch (\Exception $e) { $this->assertInstanceOf('esphinx2\Exception', $e); }
        try {
            $criteria->addFilters(array());
            $this->setExpectedException('esphinx2\Exception');
        } catch (\Exception $e) { $this->assertInstanceOf('esphinx2\Exception', $e); }
        try {
            $criteria->addFilters('', '');
            $this->setExpectedException('esphinx2\Exception');
        } catch (\Exception $e) { $this->assertInstanceOf('esphinx2\Exception', $e); }

        // add attribute
        $criteria->addFilter('attribute', 10);
        $this->assertEquals($criteria->getFilters(), array(
            array('attribute' => 'attribute', 'value' => array(10), 'exclude' => false)
        ));

        // clean
        $criteria->cleanFilters();
        $this->assertEquals($criteria->getFilters(), array());

        // adds value with exclude
        $criteria->addFilter('attribute', array(10, 20), true);
        $this->assertEquals($criteria->getFilters(), array(
            array('attribute' => 'attribute', 'value' => array(10, 20), 'exclude' => true)
        ));

        // clean all
        $criteria->cleanFilters();
        $this->assertEquals($criteria->getFilters(), array());

        // add two filters
        $criteria->addFilters(array(
            array('a1', 11),
            array('a2', 22, 'exclude' => 1),
        ));

        $this->assertEquals($criteria->getFilters(), array(
            array('attribute' => 'a1', 'value' => array(11), 'exclude' => false),
            array('attribute' => 'a2', 'value' => array(22), 'exclude' => true),
        ));

        // установка новых значений
        $criteria->setFilters(array(
            array('a22', 'some-value'),
        ));
        $this->assertEquals($criteria->getFilters(), array(
            array('attribute' => 'a22', 'value' => array('some-value'), 'exclude' => false)
        ));
    }


    public function testRangeFilters()
    {
        $criteria = new SearchCriteria;

        try {
            $criteria->addRangeFilters('');
            $this->setExpectedException('esphinx2\Exception');
        } catch (\Exception $e) { $this->assertInstanceOf('esphinx2\Exception', $e); }

        try {
            $criteria->addRangeFilters(array());
            $this->setExpectedException('esphinx2\Exception');
        } catch (\Exception $e) { $this->assertInstanceOf('esphinx2\Exception', $e); }

        try {
            $criteria->addRangeFilters(array('attr'));
            $this->setExpectedException('esphinx2\Exception');
        } catch (\Exception $e) { $this->assertInstanceOf('esphinx2\Exception', $e); }

        try {
            $criteria->addRangeFilter('', 1, 2);
            $this->setExpectedException('esphinx2\Exception');
        } catch (\Exception $e) { $this->assertInstanceOf('esphinx2\Exception', $e); }

        // add vaue
        $criteria->addRangeFilter('attribute', 1, 10);
        $this->assertEquals($criteria->getRangeFilters(), array(
            array('attribute' => 'attribute', 'min' => 1, 'max' => 10, 'exclude' => false, 'float' => false)
        ));

        $criteria->cleanRangeFilters();
        $this->assertEquals($criteria->getRangeFilters(), array());

        // add float value
        $criteria->addRangeFilter('attribute', 1.1, 10, true);
        $this->assertEquals($criteria->getRangeFilters(), array(
            array('attribute' => 'attribute', 'min' => 1.1, 'max' => 10, 'exclude' => true, 'float' => true)
        ));

        $criteria->cleanRangeFilters();
        $this->assertEquals($criteria->getRangeFilters(), array());

        // добавление нескольких значений
        $criteria->addRangeFilters(array(
            array('attribute1', 'min' => 1, 'max' => (float)100),
            array('attribute2', 'min' => 1.2, 'max' => 100.1),
            array('attribute3', 'min' => 1, 'max' => 100, 'exclude' => true),
            array('attribute4', 'min' => 1, 'max' => 100),
        ));

        $this->assertEquals($criteria->getRangeFilters(), array(
            array('attribute' => 'attribute1', 'min' => 1.0, 'max' => 100.0, 'exclude' => false, 'float' => true),
            array('attribute' => 'attribute2', 'min' => 1.2, 'max' => 100.1, 'exclude' => false, 'float' => true),
            array('attribute' => 'attribute3', 'min' => 1, 'max' => 100, 'exclude' => true, 'float' => false),
            array('attribute' => 'attribute4', 'min' => 1, 'max' => 100, 'exclude' => false, 'float' => false),
        ));
    }


    public function testOrderModeException()
    {
        $criteria = new SearchCriteria;
        $criteria->sortMode = enum\Sort::ATTR_DESC;

        try {
            $criteria->getOrders();
            $this->setExpectedException('esphinx2\Exception');
        } catch (\Exception $e) { $this->assertInstanceOf('esphinx2\Exception', $e); }

        try {
            $criteria->cleanOrders();
            $this->setExpectedException('esphinx2\Exception');
        } catch (\Exception $e) { $this->assertInstanceOf('esphinx2\Exception', $e); }

        try {
            $criteria->setOrders(array());
            $this->setExpectedException('esphinx2\Exception');
        } catch (\Exception $e) { $this->assertInstanceOf('esphinx2\Exception', $e); }

        try {
            $criteria->addOrders(array());
            $this->setExpectedException('esphinx2\Exception');
        } catch (\Exception $e) { $this->assertInstanceOf('esphinx2\Exception', $e); }

        try {
            $criteria->addOrder('', '');
            $this->setExpectedException('esphinx2\Exception');
        } catch (\Exception $e) { $this->assertInstanceOf('esphinx2\Exception', $e); }

        try {
            $criteria->sortMode = enum\Sort::EXTENDED;
            $criteria->getOrders();
            $criteria->cleanOrders();
            $criteria->setOrders(array('a' => 'asc'));
            $criteria->addOrders(array('b' => 'desc'));
            $criteria->addOrder('c', 'ASC');
        } catch (\Exception $e) {
            $this->fail('Fail order functions with EXTENDED mode');
        }
    }

    public function testOrderModeExtended()
    {
        $criteria = new SearchCriteria;
        $criteria->sortMode = enum\Sort::EXTENDED;

        try {
            $criteria->addOrder('', '');
            $this->setExpectedException('esphinx2\Exception');
        } catch(\Exception $e) { $this->assertInstanceOf('esphinx2\Exception', $e); }
        try {
            $criteria->addOrder('field', 'OSC');
            $this->setExpectedException('esphinx2\Exception');
        } catch(\Exception $e) { $this->assertInstanceOf('esphinx2\Exception', $e); }

        $criteria->addOrder('field1', 'asc');
        $criteria->addOrder('field2', 'DESC');
        $this->assertEquals($criteria->getOrders(), array(
            'field1' => 'ASC',
            'field2' => 'DESC',
        ));

        $criteria->cleanOrders();
        $this->assertEquals($criteria->getOrders(), array());

        $criteria->addOrders(array(
            'field1' => 'asc',
            'field2' => 'desc',
        ));

        $this->assertEquals($criteria->getOrders(), array(
            'field1' => 'ASC',
            'field2' => 'DESC',
        ));
    }

    public function testSortBy()
    {
        $criteria = new SearchCriteria();
        $criteria->sortMode = enum\Sort::ATTR_ASC;

        $criteria->setSortBy('field');
        $this->assertEquals($criteria->getSortBy(), 'field');
        $criteria->setSortBy(null);
        $this->assertNull($criteria->getSortBy());
    }

    public function testFieldWeights()
    {
        $criteria = new SearchCriteria;

        try {
            $criteria->addFieldWeight('', '');
            $this->setExpectedException('esphinx2\Exception');
        } catch(\Exception $e) { $this->assertInstanceOf('esphinx2\Exception', $e); }
        try {
            $criteria->addFieldWeight('field', '123');
            $this->setExpectedException('esphinx2\Exception');
        } catch(\Exception $e) { $this->assertInstanceOf('esphinx2\Exception', $e); }
        try {
            $criteria->addFieldWeight('field', 123.0);
            $this->setExpectedException('esphinx2\Exception');
        } catch(\Exception $e) { $this->assertInstanceOf('esphinx2\Exception', $e); }

        $criteria->addFieldWeight('field', 123);
        $criteria->addFieldWeight('field2', 123);
        $this->assertEquals($criteria->getFieldWeights(), array(
            'field' => 123,
            'field2' => 123,
        ));

        $criteria->cleanFieldWeights();
        $this->assertEquals($criteria->getFieldWeights(), array());

        $criteria->addFieldWeights(array(
            'field' => 123,
            'field2' => 123,
        ));

        $this->assertEquals($criteria->getFieldWeights(), array(
            'field' => 123,
            'field2' => 123,
        ));
    }

    public function testIndexWeights()
    {
        $criteria = new SearchCriteria;

        try {
            $criteria->addIndexWeight('', '');
            $this->setExpectedException('esphinx2\Exception');
        } catch(\Exception $e) { $this->assertInstanceOf('esphinx2\Exception', $e); }
        try {
            $criteria->addIndexWeight('index', '123');
            $this->setExpectedException('esphinx2\Exception');
        } catch(\Exception $e) { $this->assertInstanceOf('esphinx2\Exception', $e); }
        try {
            $criteria->addIndexWeight('index', 123.0);
            $this->setExpectedException('esphinx2\Exception');
        } catch(\Exception $e) { $this->assertInstanceOf('esphinx2\Exception', $e); }

        $criteria->addIndexWeight('index', 123);
        $criteria->addIndexWeight('index2', 123);
        $this->assertEquals($criteria->getIndexWeights(), array(
            'index' => 123,
            'index2' => 123,
        ));

        $criteria->cleanIndexWeights();
        $this->assertEquals($criteria->getIndexWeights(), array());

        $criteria->addIndexWeights(array(
            'index' => 123,
            'index2' => 123,
        ));

        $this->assertEquals($criteria->getIndexWeights(), array(
            'index' => 123,
            'index2' => 123,
        ));
    }

    public function testGroupBy()
    {
        $criteria = new SearchCriteria;

        $this->assertNull($criteria->groupBy);

        $criteria->groupBy = 'field';
        $criteria->groupByFunc = enum\Group::BY_ATTR;
        $criteria->groupBySort = '@weight desc';

        $this->assertEquals('field', $criteria->groupBy);
        $this->assertEquals(enum\Group::BY_ATTR, $criteria->groupByFunc);
        $this->assertEquals('@weight desc', $criteria->groupBySort);
    }

    public function testCreate()
    {
        $criteria = new SearchCriteria(array(
            'filters' => array(
                array('attr1', 1),
                array('attr2', 2)
            ),
            'rangeFilters' => array(
                array('attr1', 'min' => 1, 'max' => 100),
                array('attr2', 'min' => 1, 'max' => 100, 'exclude' => true),
                array('attr3', 'min' => 1.2, 'max' => 100.1),
                array('attr4', 'min' => 1, 'max' => (float)100),
            ),
            'sortMode' => enum\Sort::EXTENDED,
            'orders' => array(
                'field1' => 'asc',
                'field2' => 'desc',
            ),
            'fieldWeights' => array(
                'field' => 123,
                'field2' => 123,
            ),
            'groupBy' => 'field2',
            'groupByFunc' => enum\Group::BY_ATTR,
            'groupBySort' => '@group desc',
            'limit'  => 20,
            'offset' => 10,
        ));
        $this->assertInstanceOf('esphinx2\SearchCriteria', $criteria);

        $this->assertEquals($criteria->getFilters(), array(
            array('attribute' => 'attr1', 'value' => array(1), 'exclude' => false),
            array('attribute' => 'attr2', 'value' => array(2), 'exclude' => false),
        ));

        $this->assertEquals($criteria->getRangeFilters(), array(
            array('attribute' => 'attr1', 'min' => 1, 'max' => 100, 'exclude' => false, 'float' => false),
            array('attribute' => 'attr2', 'min' => 1, 'max' => 100, 'exclude' => true, 'float' => false),
            array('attribute' => 'attr3', 'min' => 1.2, 'max' => 100.1, 'exclude' => false, 'float' => true),
            array('attribute' => 'attr4', 'min' => 1.0, 'max' => 100.0, 'exclude' => false, 'float' => true),
        ));

        $this->assertEquals($criteria->getOrders(), array(
            'field1' => 'ASC',
            'field2' => 'DESC',
        ));

        $this->assertEquals($criteria->getFieldWeights(), array(
            'field' => 123,
            'field2' => 123,
        ));

        $this->assertEquals('field2', $criteria->groupBy);
        $this->assertEquals(enum\Group::BY_ATTR, $criteria->groupByFunc);
        $this->assertEquals('@group desc', $criteria->groupBySort);

        $this->assertEquals($criteria->limit, 20);
        $this->assertEquals($criteria->offset, 10);
    }


    public function testQueryTimeOut()
    {
        $criteria = new SearchCriteria;
        $this->assertNull($criteria->queryTimeout);

        $criteria->queryTimeout = 10;
        $this->assertEquals(10, $criteria->queryTimeout);
    }

}
