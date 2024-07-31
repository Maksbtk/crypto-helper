<?php
use Bitrix\Main\Loader;
use \Bitrix\Main,
    \Bitrix\Sale\Internals;
use Bitrix\Sale\DiscountCouponsManager;
use Bitrix\Highloadblock\HighloadBlockTable as HLBT;
use Bitrix\Sale\Payment;
use Bitrix\Sale;
use \Bitrix\Main\Context;
use \Bitrix\Main\EventManager;
$eventManager = EventManager::getInstance();

\CModule::IncludeModule('highloadblock');
\CModule::IncludeModule('iblock');
