<?php
/**
 * This file contains the DbFixtureManager class based upon CDbFixtureManger from:
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 *
 * modified for yii2 by
 * @author Philipp Frenzel <philipp@frenzel.net>
 * @link http://www.frenzel.net/
 */

/**
 * DbFixtureManager manages database fixtures during tests.
 *
 * A fixture represents a list of rows for a specific table. For a test method,
 * using a fixture means that at the beginning of the method, the table has and only
 * has the rows that are given in the fixture. Therefore, the table's state is
 * predictable.
 *
 * A fixture is represented as a PHP script whose name (without suffix) is the
 * same as the table name (if schema name is needed, it should be prefixed to
 * the table name). The PHP script returns an array representing a list of table
 * rows. Each row is an associative array of column values indexed by column names.
 *
 * A fixture can be associated with an init script which sits under the same fixture
 * directory and is named as "TableName.init.php". The init script is used to
 * initialize the table before populating the fixture data into the table.
 * If the init script does not exist, the table will be emptied.
 *
 * Fixtures must be stored under the {@link basePath} directory. The directory
 * may contain a file named "init.php" which will be executed once to initialize
 * the database. If this file is not found, all available fixtures will be loaded
 * into the database.
 *
 * @property \yii\db\Connection $dbConnection The database connection.
 * @property array $fixtures The information of the available fixtures (table name => fixture file).
 *
 * @author Philipp Frenzel <philipp@frenzel.net>
 * @package app\components
 * @since 0.0.1
 */

namespace app\common\components;

use Yii;
use \yii\base\Component;
use \yii\console\Exception;
use \yii\db\Connection;
use \yii\db\ActiveRecord;


class DbFixtureManager extends Component
{
    /**
     * @var string the name of the initialization script that would be executed before the whole test set runs.
     * Defaults to 'init.php'. If the script does not exist, every table with a fixture file will be reset.
     */
    public $initScript='init.php';
    /**
     * @var string the suffix for fixture initialization scripts.
     * If a table is associated with such a script whose name is TableName suffixed this property value,
     * then the script will be executed each time before the table is reset.
     */
    public $initScriptSuffix='.init.php';
    /**
     * @var string the base path containing all fixtures. Defaults to null, meaning
     * the path 'protected/tests/fixtures'.
     */
    public $basePath;
    /**
     * @var string the ID of the database connection. Defaults to 'db'.
     * Note, data in this database may be deleted or modified during testing.
     * Make sure you have a backup database.
     */
    public $db='db';
    /**
     * @var array list of database schemas that the test tables may reside in. Defaults to
     * array(''), meaning using the default schema (an empty string refers to the
     * default schema). This property is mainly used when turning on and off integrity checks
     * so that fixture data can be populated into the database without causing problem.
     */
    public $schemas=array('');

    private $_fixtures;
    private $_rows;             // fixture name, row alias => row
    private $_records;          // fixture name, row alias => record (or class name)


    /**
     * Initializes this application component.
     */
    public function init()
    {
        parent::init();
        if($this->basePath===null)
            $this->basePath=Yii::getAlias('@app'); //this is still a todo, as i'm not sure about syntax
        $this->prepare();
    }

    /**
     * Returns the database connection used to load fixtures.
     * @throws Exception if {@link db} application component is invalid
     * @return Connection the database connection
     */
    public function getDbConnection()
    {
        if (is_string($this->db)) {
            $this->db = Yii::$app->getComponent($this->db);
        }
        if (!$this->db instanceof Connection) {
            throw new Exception("The 'db' option must refer to the application component ID of a DB connection.");
        }
        return $this->db;
    }

    /**
     * Prepares the fixtures for the whole test.
     * This method is invoked in {@link init}. It executes the database init script
     * if it exists. Otherwise, it will load all available fixtures.
     */
    public function prepare()
    {
        $initFile=$this->basePath . DIRECTORY_SEPARATOR . $this->initScript;

        $this->checkIntegrity(false); //not supported in sqlite

        if(is_file($initFile))
            require($initFile);
        else
        {
            foreach($this->getFixtures() as $tableName=>$fixturePath)
            {
                $this->resetTable($tableName);
                $this->loadFixture($tableName);
            }
        }
        $this->checkIntegrity(true); //not supported in sqlite
    }

    /**
     * Resets the table to the state that it contains no fixture data.
     * If there is an init script named "tests/fixtures/TableName.init.php",
     * the script will be executed.
     * Otherwise, {@link truncateTable} will be invoked to delete all rows in the table
     * and reset primary key sequence, if any.
     * @param string $tableName the table name
     */
    public function resetTable($tableName)
    {
        $initFile=$this->basePath . DIRECTORY_SEPARATOR . $tableName . $this->initScriptSuffix;
        if(is_file($initFile))
            require($initFile);
        else
            $this->truncateTable($tableName);
    }

    /**
     * Loads the fixture for the specified table.
     * This method will insert rows given in the fixture into the corresponding table.
     * The loaded rows will be returned by this method.
     * If the table has auto-incremental primary key, each row will contain updated primary key value.
     * If the fixture does not exist, this method will return false.
     * Note, you may want to call {@link resetTable} before calling this method
     * so that the table is emptied first.
     * @param string $tableName table name
     * @return array the loaded fixture rows indexed by row aliases (if any).
     * False is returned if the table does not have a fixture.
     */
    public function loadFixture($tableName)
    {
        $fileName=$this->basePath.DIRECTORY_SEPARATOR.$tableName.'.php';
        if(!is_file($fileName))
            return false;

        $rows=array();
        $schema = $this->getDbConnection()->getTableSchema($tableName);
        $globalSchema = $this->getDbConnection()->getSchema();
        $table  = $globalSchema->getRawTableName($tableName);

        foreach(require($fileName) as $alias=>$row)
        {
            $this->getDbConnection()->createCommand()->insert($table,$row)->execute();
            $primaryKey=$schema->primaryKey;
            if($schema->sequenceName!==null)
            {
                if(is_string($primaryKey) && !isset($row[$primaryKey]))
                    $row[$primaryKey]=$this->getDbConnection()->getLastInsertID();
                elseif(is_array($primaryKey))
                {
                    foreach($primaryKey as $pk)
                    {
                        if(!isset($row[$pk]))
                        {
                            $row[$pk]=$globalSchema->getLastInsertID($table);
                            break;
                        }
                    }
                }
            }
            $rows[$alias]=$row;
        }
        return $rows;
    }

    /**
     * Returns the information of the available fixtures.
     * This method will search for all PHP files under {@link basePath}.
     * If a file's name is the same as a table name, it is considered to be the fixture data for that table.
     * @return array the information of the available fixtures (table name => fixture file)
     */
    public function getFixtures()
    {
        if($this->_fixtures===null)
        {
            $this->_fixtures=array();
            $schema=$this->getDbConnection()->getSchema();
            $folder=opendir($this->basePath);
            $suffixLen=strlen($this->initScriptSuffix);
            while($file=readdir($folder))
            {
                if($file==='.' || $file==='..' || $file===$this->initScript)
                    continue;
                $path=$this->basePath.DIRECTORY_SEPARATOR.$file;
                if(substr($file,-4)==='.php' && is_file($path) && substr($file,-$suffixLen)!==$this->initScriptSuffix)
                {
                    $tableName=substr($file,0,-4);
                    if($schema->getRawTableName($tableName)!==null)
                        $this->_fixtures[$tableName]=$path;
                }
            }
            closedir($folder);
        }
        return $this->_fixtures;
    }

    /**
     * Enables or disables database integrity check.
     * This method may be used to temporarily turn off foreign constraints check.
     * @param boolean $check whether to enable database integrity check
     */
    public function checkIntegrity($check)
    {
        $db=$this->getDbConnection();
        foreach($this->schemas as $schema){
            $db->createCommand()->checkIntegrity($check,$schema)->execute();
        }
    }

    /**
     * Removes all rows from the specified table and resets its primary key sequence, if any.
     * You may need to call {@link checkIntegrity} to turn off integrity check temporarily
     * before you call this method.
     * @param string $tableName the table name
     * @throws Exception if given table does not exist
     */
    public function truncateTable($tableName)
    {
        $db=$this->getDbConnection();

        if($tableName!==null)
        {
            $params = array();
            $db->createCommand()->delete($tableName,1)->execute();
            $db->createCommand()->resetSequence($tableName,1)->execute(); //wrong, moved to querybuilder! changed by pf
        }
        else
            throw new Exception("Table '$tableName' does not exist.");
    }

    /**
     * Truncates all tables in the specified schema.
     * You may need to call {@link checkIntegrity} to turn off integrity check temporarily
     * before you call this method.
     * @param string $schema the schema name. Defaults to empty string, meaning the default database schema.
     * @see truncateTable
     */
    public function truncateTables($schema='')
    {
        $tableNames=$this->getDbConnection()->getSchema()->getTableNames($schema);
        foreach($tableNames as $tableName)
            $this->truncateTable($schema->getRawTableName($tableName));
    }

    /**
     * Loads the specified fixtures.
     * For each fixture, the corresponding table will be reset first by calling
     * {@link resetTable} and then be populated with the fixture data.
     * The loaded fixture data may be later retrieved using {@link getRows}
     * and {@link getRecord}.
     * Note, if a table does not have fixture data, {@link resetTable} will still
     * be called to reset the table.
     * @param array $fixtures fixtures to be loaded. The array keys are fixture names,
     * and the array values are either AR class names or table names.
     * If table names, they must begin with a colon character (e.g. 'Post'
     * means an AR class, while ':Post' means a table name).
     */
    public function load($fixtures)
    {
        $schema=$this->getDbConnection()->getSchema();
        $schema->checkIntegrity(false);

        $this->_rows=array();
        $this->_records=array();
        foreach($fixtures as $fixtureName=>$tableName)
        {
            if($tableName[0]===':')
            {
                $tableName=substr($tableName,1);
                unset($modelClass);
            }
            else
            {
                $modelClass=Yii::import($tableName,true);
                $tableName=ActiveRecord::model($modelClass)->tableName();
            }
            if(($prefix=$this->getDbConnection()->tablePrefix)!==null)
                $tableName=preg_replace('/{{(.*?)}}/',$prefix.'\1',$tableName);
            $this->resetTable($tableName);
            $rows=$this->loadFixture($tableName);
            if(is_array($rows) && is_string($fixtureName))
            {
                $this->_rows[$fixtureName]=$rows;
                if(isset($modelClass))
                {
                    foreach(array_keys($rows) as $alias)
                        $this->_records[$fixtureName][$alias]=$modelClass;
                }
            }
        }

        $schema->checkIntegrity(true);
    }

    /**
     * Returns the fixture data rows.
     * The rows will have updated primary key values if the primary key is auto-incremental.
     * @param string $name the fixture name
     * @return array the fixture data rows. False is returned if there is no such fixture data.
     */
    public function getRows($name)
    {
        if(isset($this->_rows[$name]))
            return $this->_rows[$name];
        else
            return false;
    }

    /**
     * Returns the specified ActiveRecord instance in the fixture data.
     * @param string $name the fixture name
     * @param string $alias the alias for the fixture data row
     * @return ActiveRecord the ActiveRecord instance. False is returned if there is no such fixture row.
     */
    public function getRecord($name,$alias)
    {
        if(isset($this->_records[$name][$alias]))
        {
            if(is_string($this->_records[$name][$alias]))
            {
                $row=$this->_rows[$name][$alias];
                $model=ActiveRecord::model($this->_records[$name][$alias]);
                $key=$model->getRawTableNameSchema()->primaryKey;
                if(is_string($key))
                    $pk=$row[$key];
                else
                {
                    foreach($key as $k)
                        $pk[$k]=$row[$k];
                }
                $this->_records[$name][$alias]=$model->find($pk);
            }
            return $this->_records[$name][$alias];
        }
        else
            return false;
    }
}