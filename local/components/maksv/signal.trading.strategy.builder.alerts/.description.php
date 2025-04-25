<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
    "NAME" => GetMessage("SEARCH_NAME"),
    "DESCRIPTION" => GetMessage("SEARCH_DESCRIPTION"),
    "ICON" => "/images/cat_list.gif",
    "CACHE_PATH" => "Y",
    "PATH" => array(
        "ID" => "content",
        "CHILD" => array(
            "ID" => "search",
            "NAME" => GetMessage("T_DESC_SEARCH"),
            "CHILD" => array("ID" => "site_search",),
        ),
    ),
);

?>