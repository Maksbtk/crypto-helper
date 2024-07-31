<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
    "NAME" => GetMessage("COMP_NAME"),
    "DESCRIPTION" => GetMessage("COMP_DESCRIPTION"),
    "ICON" => "/images/cat_list.gif",
    "CACHE_PATH" => "Y",
    "PATH" => array(
        "ID" => "content",
        "CHILD" => array(
            "ID" => "section.slider",
            "NAME" => GetMessage("T_DESC_COMP"),
        ),
    ),
);

?>