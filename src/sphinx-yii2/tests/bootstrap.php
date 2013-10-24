<?php

define('DS', DIRECTORY_SEPARATOR);
defined('YII_ENABLE_EXCEPTION_HANDLER') or define('YII_ENABLE_EXCEPTION_HANDLER', false);
defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', false);
defined('YII_DEBUG') or define('YII_DEBUG', true);

$_SERVER['SCRIPT_NAME']     = '/' . basename(__FILE__);
$_SERVER['SCRIPT_FILENAME'] = __FILE__;


define('ROOT', realpath(dirname(__FILE__).'/..'));

require ROOT.'/../../../../yii2/framework/yii/Yii.php';

require 'DbFixtureManager.php';
require ROOT.'/tests/sphinxapi-2.0.9.php';

require ROOT.'/enum/Group.php';
require ROOT.'/enum/Match.php';
require ROOT.'/enum/Rank.php';
require ROOT.'/enum/Sort.php';

require ROOT.'/Exception.php';
require ROOT.'/SearchCriteria.php';
require ROOT.'/Query.php';
require ROOT.'/Result.php';

require ROOT . '/Connection.php';
require ROOT . '/ApiConnection.php';

$config = require 'config.php';
$local  = require 'config-local.php';

$config = yii\helpers\ArrayHelper::merge($config, $local);

$application = new yii\console\Application($config);
Yii::setAlias('@yiiunit', __DIR__);
