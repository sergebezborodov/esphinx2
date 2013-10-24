<?php

namespace tests;

use esphinx2;
use esphinx2\enum;

/**
 * Tests for enums
 */
class EnumTest extends \yii\test\TestCase
{
    public function testGroup()
    {
        $this->assertEquals(enum\Group::BY_DAY, SPH_GROUPBY_DAY);
        $this->assertEquals(enum\Group::BY_WEEK, SPH_GROUPBY_WEEK);
        $this->assertEquals(enum\Group::BY_MONTH, SPH_GROUPBY_MONTH);
        $this->assertEquals(enum\Group::BY_YEAR, SPH_GROUPBY_YEAR);
        $this->assertEquals(enum\Group::BY_ATTR, SPH_GROUPBY_ATTR);

        $this->assertCount(5, enum\Group::items());
    }

    public function testMath()
    {
        $this->assertEquals(enum\Match::ALL, SPH_MATCH_ALL);
        $this->assertEquals(enum\Match::ANY, SPH_MATCH_ANY);
        $this->assertEquals(enum\Match::PHRASE, SPH_MATCH_PHRASE);
        $this->assertEquals(enum\Match::BOOLEAN, SPH_MATCH_BOOLEAN);
        $this->assertEquals(enum\Match::EXTENDED, SPH_MATCH_EXTENDED);
        $this->assertEquals(enum\Match::FULLSCAN, SPH_MATCH_FULLSCAN);
        $this->assertEquals(enum\Match::EXTENDED2, SPH_MATCH_EXTENDED2);

        $this->assertCount(7, enum\Match::items());
    }

    public function testRank()
    {
        $this->assertEquals(enum\Rank::PROXIMITY_BM25, SPH_RANK_PROXIMITY_BM25);
        $this->assertEquals(enum\Rank::BM25, SPH_RANK_BM25);
        $this->assertEquals(enum\Rank::NONE, SPH_RANK_NONE);
        $this->assertEquals(enum\Rank::WORDCOUNT, SPH_RANK_WORDCOUNT);
        $this->assertEquals(enum\Rank::PROXIMITY, SPH_RANK_PROXIMITY);
        $this->assertEquals(enum\Rank::MATCHANY, SPH_RANK_MATCHANY);
        $this->assertEquals(enum\Rank::FIELDMASK, SPH_RANK_FIELDMASK);
        $this->assertEquals(enum\Rank::SPH04, SPH_RANK_SPH04);
        $this->assertEquals(enum\Rank::EXPR, SPH_RANK_EXPR);
        $this->assertEquals(enum\Rank::TOTAL, SPH_RANK_TOTAL);

        $this->assertCount(9, enum\Rank::items());
    }

    public function testSort()
    {
        $this->assertEquals(enum\Sort::RELEVANCE, SPH_SORT_RELEVANCE);
        $this->assertEquals(enum\Sort::ATTR_DESC, SPH_SORT_ATTR_DESC);
        $this->assertEquals(enum\Sort::ATTR_ASC, SPH_SORT_ATTR_ASC);
        $this->assertEquals(enum\Sort::TIME_SEGMENTS, SPH_SORT_TIME_SEGMENTS);
        $this->assertEquals(enum\Sort::EXTENDED, SPH_SORT_EXTENDED);
        $this->assertEquals(enum\Sort::EXPR, SPH_SORT_EXPR);

        $this->assertCount(6, enum\Sort::items());
    }
}
