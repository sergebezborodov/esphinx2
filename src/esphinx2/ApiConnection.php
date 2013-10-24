<?php

namespace esphinx2;

/**
 * Standart connection to sphinx daemon
 *
 * @property int $connectionTimeout
 * @property int $queryTimeout
 */
class ApiConnection extends Connection
{
    /**
     * Instance of SphinxClient
     *
     * @var \SphinxClient $sphinxClient
     */
    private $sphinxClient;

    /**
     * Flag fot check is client connected
     *
     * @var bool defaults false
     */
    private $isConnected = false;

    private $_queryTimeout = 0;

    private $server;

    public function __construct()
    {
        $this->sphinxClient = new \SphinxClient();
        $this->sphinxClient->SetArrayResult(true);
    }

    /**
     * Set Sphinx server connection parameters.
     *
     * @param array $parameters list of params, where first item is host, second is port
     * @example 'localhost'
     * @example 'localhost:3314'
     * @example array("localhost", 3386)
     * @link http://sphinxsearch.com/docs/current.html#api-func-setserver
     */
    public function setServer($parameters = null)
    {
        $server = self::DEFAULT_SERVER;
        $port   = self::DEFAULT_PORT;
        if (is_string($parameters)) {
            $parameters = explode(':', $parameters);
        }

        if (isset($parameters[0])) {
            $server = $parameters[0];
        }
        if (isset($parameters[1])) {
            $port = $parameters[1];
        }

        $this->server = array($server, $port);
        $this->sphinxClient->SetServer($server, $port);
    }

    /**
     * @return array
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Open Sphinx persistent connection.
     *
     * @throws Exception if client is already connected.
     * @throws Exception if client has connection error.
     * @link http://sphinxsearch.com/docs/current.html#api-func-open
     */
    public function openConnection()
    {
        if ($this->isConnected) {
            throw new Exception("Sphinx client is already opened");
        }

        $this->sphinxClient->Open();

        if ($this->sphinxClient->IsConnectError()) {
            throw new Exception("Sphinx exception: ".$this->sphinxClient->GetLastError());
        }

        $this->isConnected = true;
    }

    /**
     * Open connection if it doesn't opened
     */
    protected function openConnectionIfNeed()
    {
        if (!$this->isConnected) {
            $this->openConnection();
        }
    }

    /**
     * Close Sphinx persistent connection.
     *
     * @throws Exception if client is not connected.
     * @link http://sphinxsearch.com/docs/current.html#api-func-close
     */
    public function closeConnection()
    {
        if (!$this->isConnected) {
            throw new Exception("Sphinx client is already closed");
        }

        $this->sphinxClient->Close();
        $this->isConnected = false;
    }

    /**
     * Check is client has connection
     *
     * @return boolean
     */
    public function getIsConnected()
    {
        return $this->isConnected;
    }

    /**
     * Sets the time allowed to spend connecting to the server before giving up.
     * Under some circumstances, the server can be delayed in responding, either due to network delays,
     * or a query backlog. In either instance, this allows the client application programmer some degree
     * of control over how their program interacts with searchd when not available, and can ensure
     * that the client application does not fail due to exceeding the script execution limits (especially in PHP).
     * In the event of a failure to connect, an appropriate error code should be returned back to the application
     * in order for application-level error handling to advise the user.
     *
     * @param integer $timeout
     * @link http://sphinxsearch.com/docs/current.html#api-func-setconnecttimeout
     */
    public function setConnectionTimeout($timeout)
    {
        $this->sphinxClient->SetConnectTimeout((int)$timeout);
    }

    /**
     * Sets maximum search query time, in milliseconds.
     * Parameter must be a non-negative integer. Default valus is 0 which means "do not limit".
     * Similar to $cutoff setting from {@link SetLimits}, but limits elapsed query time instead of processed matches count.
     * Local search queries will be stopped once that much time has elapsed. Note that if you're performing a search
     * which queries several local indexes, this limit applies to each index separately.
     *
     * @param integer $timeout
     * @link http://sphinxsearch.com/docs/current.html#api-func-setmaxquerytime
     */
    public function setQueryTimeout($timeout)
    {
        $this->queryTimeout = $timeout;
    }


    /**
     * Prototype: function BuildExcerpts ( $docs, $index, $words, $opts=array() )
     * Excerpts (snippets) builder function. Connects to searchd, asks it to generate excerpts (snippets) from given documents, and returns the results.
     * $docs is a plain array of strings that carry the documents' contents. $index is an index name string.
     * Different settings (such as charset, morphology, wordforms) from given index will be used.
     * $words is a string that contains the keywords to highlight. They will be processed with respect to index settings.
     * For instance, if English stemming is enabled in the index, "shoes" will be highlighted even if keyword is "shoe".
     * Starting with version 0.9.9-rc1, keywords can contain wildcards, that work similarly to star-syntax available in queries.
     * $opts is a hash which contains additional optional highlighting parameters:
     * <ul>
     *   <li>"before_match": A string to insert before a keyword match. Default is "&ltb&gt".</li>
     *   <li>"after_match": A string to insert after a keyword match. Default is "&l/tb&gt".</li>
     *   <li>"chunk_separator": A string to insert between snippet chunks (passages). Default is " ... ".</li>
     *   <li>"limit": Maximum snippet size, in symbols (codepoints). Integer, default is 256.</li>
     *   <li>"around": How much words to pick around each matching keywords block. Integer, default is 5.</li>
     *   <li>"exact_phrase": Whether to highlight exact query phrase matches only instead of individual keywords. Boolean, default is false.</li>
     *   <li>"single_passage": Whether to extract single best passage only. Boolean, default is false.</li>
     *   <li>"weight_order": Whether to sort the extracted passages in order of relevance (decreasing weight), or in order of appearance in the document (increasing position). Boolean, default is false.</li>
     * </ul>
     *
     * @param array $docs
     * @param string $index
     * @param string $words
     * @param array $opts
     * @return array
     * @link http://sphinxsearch.com/docs/current.html#api-func-buildexcerpts
     */
    public function createExcerts(array $docs, $index, $words, array $opts = array())
    {
        return $this->sphinxClient->BuildExcerpts($docs, $index, $words, $opts);
    }


    /**
     * Extracts keywords from query using tokenizer settings for given index, optionally with per-keyword
     * occurrence statistics. Returns an array of hashes with per-keyword information.
     * $query is a query to extract keywords from. $index is a name of the index to get tokenizing settings and
     * keyword occurrence statistics from. $hits is a boolean flag that indicates whether keyword occurrence
     * statistics are required.
     *
     * @param string $query
     * @param string $index
     * @param boolean $hits
     * @return array
     * @link http://sphinxsearch.com/docs/current.html#api-func-buildkeywords
     */
    public function createKeywords($query, $index, $hits = false)
    {
        return $this->sphinxClient->BuildKeywords($query, $index, $hits);
    }

    /**
     * Escapes characters that are treated as special operators by the query language parser. Returns an escaped string.
     * This function might seem redundant because it's trivial to implement in any calling application.
     * However, as the set of special characters might change over time, it makes sense to have an API call that is
     * guaranteed to escape all such characters at all times.
     *
     * @param string $string
     * @return string
     * @link http://sphinxsearch.com/docs/current.html#api-func-escapestring
     */
    public function escape($string)
    {
        return $this->sphinxClient->EscapeString((string)$string);
    }


    /**
     * Instantly updates given attribute values in given documents. Returns number of actually updated documents (0 or more) on success, or -1 on failure.
     *
     * @link http://sphinxsearch.com/docs/2.0.6/api-func-updateatttributes.html
     * @param $index
     * @param array $attrs
     * @param array $values
     * @param bool $mfa
     * @return int
     */
    public function update($index, array $attrs, array $values, $mfa=false)
    {
        return $this->sphinxClient->UpdateAttributes($index, $attrs, $values, $mfa);
    }


    /**
     * Execute single query.
     * @example
     * <code>
     *   $result = $connection->execute(new ESphinxQuery("hello world search"));
     *   var_dump($result); // printed ESphinxResult var dump
     * </code>
     *
     * @param Query $query
     * @return Result
     * @see ESphinxQuery
     * @see ESphinxCriteria
     */
    public function executeQuery(Query $query)
    {
        $this->resetClient();
        $this->applyQuery($query);
        $results = $this->execute();
        return $results[0];
    }


    /**
     * Execute query collection
     * @example
     * <code>
     *   $queries = array(
     *      new ESphinxQuery("hello"),
     *      new ESphinxQuery("world"),
     *   );
     *   $results = $connection->executeQueries($queries);
     *   foreach($results as $result)
     *      var_dump($result); // print ESphinxResult
     * </code>
     * @param Query[] $queries
     * @return Result[]
     */
    public function executeQueries(array $queries)
    {
        foreach ($queries as $query) {
            $this->resetClient();
            $this->applyQuery($query);
        }

        return $this->execute();
    }

    protected function applyQuery(Query $query)
    {
        $this->applyCriteria($query->getCriteria());
        $this->sphinxClient->AddQuery($query->getText(), $query->getIndexes(), $query->criteria->comment);
    }

    protected function applyCriteria(SearchCriteria $criteria)
    {
        $this->applyMatchMode($criteria->matchMode);
        $this->applyRankMode($criteria);

        if ($criteria->sortMode == enum\Sort::EXTENDED) {
            $orders = '';
            if ($orderArray = $criteria->getOrders()) {
                $fields = array();
                foreach ($orderArray as $attr => $type) {
                    $fields[] = $attr . ' ' . $type;
                }
                $orders = implode(', ', $fields);
            }

            $this->applySortMode($criteria->sortMode, $orders);
        } else {
            $this->applySortMode($criteria->sortMode, $criteria->getSortBy());
        }


        // apply select
        if (strlen($criteria->select)) {
            $this->sphinxClient->SetSelect($criteria->select);
        }

        // apply limit
        if($criteria->limit) {
            $this->sphinxClient->SetLimits(
                $criteria->offset,
                $criteria->limit,
                $criteria->maxMatches,
                $criteria->cutOff
            );
        }

        // apply group
        if ($criteria->groupBy) {
            $this->sphinxClient->SetGroupBy($criteria->groupBy, $criteria->groupByFunc, $criteria->groupBySort);
        }

        if ($criteria->groupDistinct) {
            $this->sphinxClient->SetGroupDistinct($criteria->groupDistinct);
        }

        // apply id range
        if($criteria->getIsIdRangeSetted()) {
            $this->sphinxClient->SetIDRange($criteria->getMinId(), $criteria->getMaxId());
        }

        // apply weights
        $this->applyFieldWeights($criteria->getFieldWeights());
        $this->applyIndexWeights($criteria->getIndexWeights());

        $this->applyFilters($criteria->getFilters());
        $this->applyRanges($criteria->getRangeFilters());

        $this->sphinxClient->SetMaxQueryTime($criteria->queryTimeout !== null ? $criteria->queryTimeout : $this->_queryTimeout);

        if (VER_COMMAND_SEARCH >= 0x11D) {
            $this->applyOptions($criteria);
        }
    }


    private function applyOptions(SearchCriteria $queryCriteria)
    {
        if ($queryCriteria->booleanSimplify !== null) {
            $this->sphinxClient->SetQueryFlag('boolean_simplify', $queryCriteria->booleanSimplify);
        }

        if (($revScan = $queryCriteria->getReverseScan()) !== null) {
            $this->sphinxClient->SetQueryFlag('reverse_scan', $revScan ?  1 : 0);
        }

        if (($sortMode = $queryCriteria->getSortMethod()) !== null) {
            $this->sphinxClient->SetQueryFlag('sort_method', $sortMode);
        }

        if ($queryCriteria->globalIdf !== null) {
            $this->sphinxClient->SetQueryFlag('global_idf', $queryCriteria->globalIdf);
        }

        if (($idf = $queryCriteria->getIdf()) !== null) {
            $this->sphinxClient->SetQueryFlag('idf', $idf);
        }
    }

    protected function applyRanges(array $ranges)
    {
        foreach ($ranges as $rangeFilter) {
            $method = $rangeFilter['float'] ? 'SetFilterFloatRange' : 'SetFilterRange';
            $this->sphinxClient->$method($rangeFilter['attribute'],
                $rangeFilter['min'], $rangeFilter['max'],
                $rangeFilter['exclude']
            );
        }
    }

    protected function applyFilters(array $conditions)
    {
        foreach ($conditions as $filter) {
            $this->sphinxClient->SetFilter($filter['attribute'], $filter['value'], $filter['exclude']);
        }
    }

    protected function applyIndexWeights(array $weights)
    {
        foreach( $weights as $index => $weight) {
            $weights[$index] = (int)$weight;
        }

        $this->sphinxClient->SetIndexWeights($weights);
    }

    protected function applyFieldWeights(array $weights)
    {
        foreach($weights as $field => $weight) {
            $weights[$field] = (int)$weight;
        }

        $this->sphinxClient->SetFieldWeights($weights);
    }

    protected function applySortMode($mode, $sortBy = '')
    {
        $mode = (int)$mode;
        if (!enum\Sort::isValid($mode)) {
            throw new Exception("Sort mode {$mode} is undefined");
        }

        $this->sphinxClient->SetSortMode($mode, $sortBy);
    }

    protected function applyMatchMode($mode)
    {
        $mode = (int)$mode;
        if (!enum\Match::isValid($mode)) {
            throw new Exception("Match mode {$mode} is not defined");
        }
        $this->sphinxClient->SetMatchMode($mode);
    }

    protected function applyRankMode(SearchCriteria $criteria)
    {
        if (!$criteria->rankingMode) {
            return;
        }

        if (!enum\Rank::isValid($criteria->rankingMode)) {
            throw new Exception("Rank mode {$criteria->rankingMode} is not defined");
        }

        $this->sphinxClient->SetRankingMode($criteria->rankingMode, $criteria->rankingExpression);
    }

    /**
     * Reset internal state of sphinxClient
     */
    protected function resetClient()
    {
        $this->sphinxClient->ResetFilters();
        $this->sphinxClient->ResetGroupBy();
        $this->sphinxClient->ResetOverrides();
        $this->sphinxClient->SetLimits(0, 20);
        $this->sphinxClient->SetArrayResult(true);
        $this->sphinxClient->SetFieldWeights(array());
        $this->sphinxClient->SetIDRange(0,0);
        $this->sphinxClient->SetIndexWeights(array());
        $this->sphinxClient->SetMatchMode(SPH_MATCH_EXTENDED2);
        $this->sphinxClient->SetRankingMode(SPH_RANK_NONE);
        $this->sphinxClient->SetSortMode(SPH_SORT_RELEVANCE, "");
        $this->sphinxClient->SetSelect("*");
        if (VER_COMMAND_SEARCH >= 0x11D) {
            $this->sphinxClient->ResetQueryFlag();
        }
    }

    protected function execute()
    {
        $sph = $this->sphinxClient->RunQueries();

        if ($error = $this->sphinxClient->GetLastError()) {
            throw new Exception($error);
        }
        if ($error = $this->sphinxClient->GetLastWarning()) {
            throw new Exception($error);
        }
        if (!is_array($sph)) {
            throw new Exception("Sphinx client returns result not array");
        }

        $results = array();
        foreach ($sph as $result) {
            if (!empty($result['error'])) {
                throw new Exception($result['error']);
            }
            $results[] = new Result($result);
        }
        return $results;
    }
}
