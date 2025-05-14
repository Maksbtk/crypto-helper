<?php
//require_once ($_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/belleyou/autoload.php');
use Bitrix\Main\Loader;
use \Bitrix\Main,
    \Bitrix\Sale\Internals;
use Bitrix\Main\UserTable;    
use Bitrix\Sale\DiscountCouponsManager;
use Bitrix\Highloadblock\HighloadBlockTable as HLBT;
use Bitrix\Sale\Payment;
use Bitrix\Sale;
use \Bitrix\Main\Context;

if ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/wsrubi.smtp/classes/general/wsrubismtp.php")
    include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/wsrubi.smtp/classes/general/wsrubismtp.php");

\CModule::IncludeModule('highloadblock');
\CModule::IncludeModule('iblock');

$request = Context::getCurrent()->getRequest();
#$eventManager = EventManager::getInstance();

//maksv
if (file_exists(Loader::getLocal('php_interface/include/maksv/autoload.php')))
    require_once(Loader::getLocal('php_interface/include/maksv/autoload.php'));

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'))
    require_once($_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php');

//все функции
if (file_exists(Loader::getLocal('php_interface/functions.php')))
    require_once(Loader::getLocal('php_interface/functions.php'));

//события
if (file_exists(Loader::getLocal('php_interface/eventHandlers.php')))
    require_once(Loader::getLocal('php_interface/eventHandlers.php'));

//email события
if (file_exists(Loader::getLocal('php_interface/emailEventHandlers.php')))
    require_once(Loader::getLocal('php_interface/emailEventHandlers.php')); 

