<?php

namespace esphinx2;

/**
 * Class implements sphinx query model.
 * Query contains search text, indexes list, and sphinx criteria.
 *
 * @property SearchCriteria $criteria
 * @property string $text
 * @property string $indexes
 */
class Query extends \yii\base\Component
{
	/**
	 * @var string
	 */
	private $_text;

	/**
	 * @var string
	 */
	private $_indexes;

	/**
	 * @var SearchCriteria
	 */
	private $_criteria;

	/**
	 * Query constructor.
	 * 
	 * @param string $text search phrase
	 * @param string $indexes list of indexes
	 * @param SearchCriteria|array $criteria search criteria
	 */
    public function __construct($text, $indexes = "*", $criteria = null)
	{
		$this->_text = (string)$text;

		if ($criteria instanceof SearchCriteria) {
	        $this->_criteria = $criteria;
        } else if(is_array($criteria)) {
			$this->_criteria = new SearchCriteria($criteria);
        } else {
            $this->_criteria = new SearchCriteria;
        }

	    if (is_array($indexes)) {
            $this->_indexes = implode(" ", $indexes);
        } else {
            $this->_indexes = (string)$indexes;
        }
	}

	/**
	 * Get search query
     *
	 * @return string
	 */
	public function getText()
	{
		return $this->_text;
	}

	/**
	 * Get list indexes as string
     *
	 * @return string
	 */
	public function getIndexes()
	{
		return $this->_indexes;
	}

	/**
	 * Get search criteria
     *
	 * @return SearchCriteria
	 */
	public function getCriteria()
	{
		return $this->_criteria;
	}
}
