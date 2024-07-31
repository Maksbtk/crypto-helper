<?
use Bitrix\Main\Page\Asset;

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */

/**
 * Bitrix vars
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$APPLICATION->IncludeComponent(
    "bitrix:main.register",
    "",
    Array(
        "AUTH" => "Y",
        "REQUIRED_FIELDS" => array("NAME", "LAST_NAME"),
        "SET_TITLE" => "Y",
        "SHOW_FIELDS" => array("NAME", "LAST_NAME", "PHONE_NUMBER"),
        "SUCCESS_PAGE" => "/user/profile/",
        "USER_PROPERTY" => array("UF_NEWS","UF_PL_MEMBER"),
        "USER_PROPERTY_NAME" => "",
        "USE_BACKURL" => "Y",
        "SHOW_SMS_FIELD" => "N",
        "arResult" => $arResult
    )
);