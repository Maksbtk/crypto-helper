<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Page\Asset;

$this->setFrameMode(true);

global $minPrice;

$title = false;
$h1 = false;
$description = false;

#REDIRECTS
$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$uri = new \Bitrix\Main\Web\Uri($request->getRequestUri());
$url = $uri->getUri();

if (strpos($url, "/filter/dopolnitelnyy_tip-is") !== false) {
    Asset::getInstance()->addString('<meta name="robots" content="noindex, nofollow">');
}
if (strpos($url, "filter/clear/apply/") !== false) {
    $newUrl = str_replace('filter/clear/apply/', '', $url);
    LocalRedirect($newUrl, false, '301 Moved permanently');
}
if (strpos($url, "/catalog/odezhda/filter/dopolnitelnyy_tip-is-futbolka_s_dlinnym_rukavom/apply/") !== false) {
    LocalRedirect('/catalog/odezhda/futbolki/filter/dopolnitelnyy_tip-is-futbolka_s_dlinnym_rukavom/apply/', false, '301 Moved permanently');
} 
if (strpos($url, "/catalog/leginsy-i-velosipedki/filter/_color-is-goluboy/apply/") !== false) {
    LocalRedirect('/catalog/odezhda/leginsy_i_velosipedki/filter/_color-is-goluboy/apply/', false, '301 Moved permanently');
} 
if (strpos($url, "/catalog/bodi/filter/_color-is-lazurno_goluboy/apply/") !== false) {
    LocalRedirect('/catalog/odezhda/bodi/filter/_color-is-lazurno_goluboy/apply/', false, '301 Moved permanently');
}   
if (strpos($url, "/catalog/bodi/filter/_color-is-belyy_6/apply/") !== false) {
    LocalRedirect('/catalog/odezhda/bodi/filter/_color-is-belyy_6/apply/', false, '301 Moved permanently');
}
if (strpos($url, "/catalog/leginsy-i-velosipedki/filter/_color-is-belyy_6/apply/") !== false) {
    LocalRedirect('/catalog/odezhda/leginsy_i_velosipedki/filter/_color-is-belyy_6/apply/', false, '301 Moved permanently');
}
if (strpos($url, "/catalog/bodi/filter/dopolnitelnyy_tip-is-bodi_s_dlinnym_rukavom/apply/") !== false) {
    LocalRedirect('/catalog/odezhda/bodi/filter/dopolnitelnyy_tip-is-bodi_s_dlinnym_rukavom/apply/', false, '301 Moved permanently');
}
if (strpos($url, "/catalog/bodi/filter/dopolnitelnyy_tip-is-bodi_na_tonkikh_bretelyakh/apply/") !== false) {
    LocalRedirect('/catalog/odezhda/bodi/filter/dopolnitelnyy_tip-is-bodi_na_tonkikh_bretelyakh/apply/', false, '301 Moved permanently');
}
if (strpos($url, "/catalog/komplekty-belya/filter/_color-is-belyy_6/apply/") !== false) {
    LocalRedirect('/catalog/nizhnee_bele/filter/_color-is-belyy_6/apply/', false, '301 Moved permanently');
}
if (strpos($url, "/catalog/leginsy-i-velosipedki/filter/_color-is-zheltyy_4/apply/") !== false) {
    LocalRedirect('/catalog/odezhda/leginsy_i_velosipedki/filter/_color-is-zheltyy_4/apply/', false, '301 Moved permanently');
}
if (strpos($url, "/catalog/leginsy-i-velosipedki/filter/dopolnitelnyy_tip-is-leginsy_s_vysokoy_posadkoy/apply/") !== false) {
    LocalRedirect('/catalog/odezhda/leginsy_i_velosipedki/filter/dopolnitelnyy_tip-is-leginsy_s_vysokoy_posadkoy/apply/', false, '301 Moved permanently');
}
if (strpos($url, "/catalog/bodi/filter/_color-is-rozovyy/apply/") !== false) {
    LocalRedirect('/catalog/odezhda/bodi/filter/_color-is-rozovyy/apply/', false, '301 Moved permanently');
}
if (strpos($url, "/catalog/odezhda/filter/dopolnitelnyy_tip-is-plate_mayka/apply/") !== false) {
    LocalRedirect('/catalog/odezhda/platya/', false, '301 Moved permanently');
}
if (strpos($url, "/catalog/odezhda/filter/dopolnitelnyy_tip-is-plate_bando/apply/") !== false) {
    LocalRedirect('/catalog/odezhda/platya/', false, '301 Moved permanently');
} 
if (strpos($url, "/catalog/sport/filter/dopolnitelnyy_tip-is-mayka_sportivnaya/apply/") !== false) {
    LocalRedirect('/catalog/kollektsii/sport/filter/dopolnitelnyy_tip-is-mayka_sportivnaya/apply/', false, '301 Moved permanently');
}
if (strpos($url, "/catalog/bodi/filter/_color-is-seryy/apply/") !== false) {
    LocalRedirect('/catalog/odezhda/bodi/filter/_color-is-seryy/apply/', false, '301 Moved permanently');
}
if (strpos($url, "/catalog/sport/filter/dopolnitelnyy_tip-is-top_sportivnyy/apply/") !== false) {
    LocalRedirect('/catalog/kollektsii/sport/filter/dopolnitelnyy_tip-is-top_sportivnyy/apply/', false, '301 Moved permanently');
}
if (strpos($url, "/catalog/sport/filter/dopolnitelnyy_tip-is-leginsy_sportivnye/apply/") !== false) {
    LocalRedirect('/catalog/sport/filter/dopolnitelnyy_tip-is-leginsy_sportivnye/apply/', false, '301 Moved permanently');
}
if (strpos($url, "/catalog/termobele/filter/dopolnitelnyy_tip-is-termo_futbolka/apply/") !== false) {
    LocalRedirect('/catalog/odezhda/termobele/filter/dopolnitelnyy_tip-is-termo_futbolka/apply/', false, '301 Moved permanently');
}
if (strpos($url, "/catalog/kombinezony/filter/dopolnitelnyy_tip-is-kombinezon_ukorochennyy/apply/") !== false) {
    LocalRedirect('/catalog/odezhda/kombinezony/filter/dopolnitelnyy_tip-is-kombinezon_ukorochennyy/apply/', false, '301 Moved permanently');
}
if (strpos($url, "/catalog/odezhda/filter/dopolnitelnyy_tip-is-futbolka_s_korotkim_rukavom/apply/") !== false) {
    LocalRedirect('/catalog/odezhda/futbolki/filter/dopolnitelnyy_tip-is-futbolka_s_korotkim_rukavom/apply/', false, '301 Moved permanently');
}
if (strpos($url, "/catalog/komplekty-belya/filter/_color-is-chernyy_1/apply/") !== false) {
    LocalRedirect('/catalog/nizhnee_bele/komplekty-belya/filter/_color-is-chernyy_1/apply/', false, '301 Moved permanently');
}
if (strpos($url, "/catalog/leginsy-i-velosipedki/filter/_color-is-chernyy_2/apply/") !== false) {
    LocalRedirect('/catalog/odezhda/leginsy_i_velosipedki/filter/_color-is-chernyy_2/apply/', false, '301 Moved permanently');
}
if (strpos($url, "/catalog/sport/filter/dopolnitelnyy_tip-is-velosipedki_sportivnye/apply/") !== false) {
    LocalRedirect('/catalog/kollektsii/sport/', false, '301 Moved permanently');
}
if (strpos($url, "/catalog/kombinezony/filter/dopolnitelnyy_tip-is-kombinezon_dlinnyy/apply/") !== false) {
    LocalRedirect('/catalog/odezhda/kombinezony/filter/dopolnitelnyy_tip-is-kombinezon_dlinnyy/apply/', false, '301 Moved permanently');
}
if (strpos($url, "/catalog/leginsy-i-velosipedki/filter/_color-is-zelenyy/apply/") !== false) {
    LocalRedirect('/catalog/odezhda/leginsy_i_velosipedki/filter/_color-is-zelenyy/apply/', false, '301 Moved permanently');
}
#!REDIRECTS

if (!isset($arParams['FILTER_VIEW_MODE']) || (string)$arParams['FILTER_VIEW_MODE'] == '')
	$arParams['FILTER_VIEW_MODE'] = 'VERTICAL';
$arParams['USE_FILTER'] = (isset($arParams['USE_FILTER']) && $arParams['USE_FILTER'] == 'Y' ? 'Y' : 'N');

$isVerticalFilter = ('Y' == $arParams['USE_FILTER'] && $arParams["FILTER_VIEW_MODE"] == "VERTICAL");
$isSidebar = ($arParams["SIDEBAR_SECTION_SHOW"] == "Y" && isset($arParams["SIDEBAR_PATH"]) && !empty($arParams["SIDEBAR_PATH"]));
$isFilter = ($arParams['USE_FILTER'] == 'Y');

if ($isFilter)
{
	$arFilter = array(
		"IBLOCK_ID" => $arParams["IBLOCK_ID"],
		"ACTIVE" => "Y",
		"GLOBAL_ACTIVE" => "Y",
	);
	if (0 < intval($arResult["VARIABLES"]["SECTION_ID"]))
		$arFilter["ID"] = $arResult["VARIABLES"]["SECTION_ID"];
	elseif ('' != $arResult["VARIABLES"]["SECTION_CODE"])
		$arFilter["=CODE"] = $arResult["VARIABLES"]["SECTION_CODE"];

	$obCache = new CPHPCache();
	if ($obCache->InitCache(36000, serialize($arFilter), "/iblock/catalog"))
	{
		$arCurSection = $obCache->GetVars();
	}
	elseif ($obCache->StartDataCache())
	{
		$arCurSection = array();
		if (Loader::includeModule("iblock"))
		{
			$dbRes = CIBlockSection::GetList(array(), $arFilter, false, array("ID"));

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->StartTagCache("/iblock/catalog");

				if ($arCurSection = $dbRes->Fetch())
					$CACHE_MANAGER->RegisterTag("iblock_id_".$arParams["IBLOCK_ID"]);

				$CACHE_MANAGER->EndTagCache();
			}
			else
			{
				if(!$arCurSection = $dbRes->Fetch())
					$arCurSection = array();
			}
		}
		$obCache->EndDataCache($arCurSection);
	}
	if (!isset($arCurSection))
		$arCurSection = array();
}

#FILTER SEO
$curr_page = $APPLICATION->getCurPage(false);

if (strpos($curr_page, 'filter') !== false) {
    $arSelect = Array("ID", "NAME", "PREVIEW_TEXT", "PROPERTY_TITLE", "PROPERTY_H1", "PROPERTY_DESCRIPTION");
    $arFilter = Array("IBLOCK_ID"=> 43, "NAME" => $curr_page);
    $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
    while($ob = $res->GetNextElement())
    {
        $arFields = $ob->GetFields();
        
        $seo_text = $arFields['PREVIEW_TEXT'];
        
        $title = $arFields['PROPERTY_TITLE_VALUE'];
        $h1 = $arFields['PROPERTY_H1_VALUE'];
        $description = $arFields['PROPERTY_DESCRIPTION_VALUE'];
    }
}
#!FILTER SEO 

include($_SERVER["DOCUMENT_ROOT"] . "/" . $this->GetFolder() . "/catalog_section.php");
?>

<style type="text/css">
    .catalog-page__description,
    .catalog_filter_seo {
        font-size: 14px;
        letter-spacing: 0.05em;
        line-height: 18px;
        color: #383838;
        padding: 0 40px 40px 40px;
    }
    .belleyou-filter-title {
        margin: 0 0 40px;
        font-size: 18px;
        line-height: 23px;
        
        font-family: var(--font-family-header);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.02em;    
    }
    .smartfilter-section-title {
        position: relative;
        margin: 0 0 5px;
        
        font-family: var(--font-family-header);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        font-size: 12px;
        line-height: 15px;               
    }        
</style>

<?
if(!empty($seo_text)){
    ?><div class="catalog_filter_seo"><?
        echo htmlspecialchars_decode($seo_text); 
    ?></div><?   
}
if(!empty($title)){
    $APPLICATION->SetTitle($title);
    $APPLICATION->SetPageProperty('title', $title);     
}    
if(!empty($description)){
    $APPLICATION->SetPageProperty('description', $description);     
}
?>

<?php
if(strpos($_SERVER['REQUEST_URI'], '/zakrytaya-rasprodazha/') === false){?>
    <?$vk_content = [
        'category_ids' => $intSectionID,
    ];

    $vk_content = json_encode($vk_content, JSON_UNESCAPED_UNICODE);?>

    <script>
        $(document).ready(function() {
            mindboxViewCategory('<?=$intSectionID?>');
        });     
    </script>
    
    <script type="text/javascript">
        (window["rrApiOnReady"] = window["rrApiOnReady"] || []).push(function() {
            try { rrApi.categoryView(<?=$intSectionID?>); } catch(e) {}
        })
    </script>

    <div class="i-flocktory" data-fl-action="track-category-view" data-fl-category-id="<?=$intSectionID?>"></div>

    <script>
        gtag("event", "pageView", {
            event_category: 'behavior',
            event_label: 'catalog',        
            page_location: location.href,
            page_path: <?="/".$arResult['VARIABLES']['SECTION_CODE']."/"?>,
            page_title: "<?$APPLICATION->ShowViewContent('section_title');?>"
        }); 
        
        ym(24428327,'reachGoal','view_category', {
            page_location: location.href,
            page_path: <?="/".$arResult['VARIABLES']['SECTION_CODE']."/"?>,
            page_title: "<?$APPLICATION->ShowViewContent('section_title');?>" 
        });
        
        setTimeout(function() {            
            VK.Retargeting.ProductEvent(132039, "view_category", <?=$vk_content?>);
        }, 2000);
    </script>

    <?
    /*if (strpos($curr_page, "/filter/") !== false && strpos($curr_page, "/clear/apply/") === false) {
        if(strpos($curr_page, "/_color-is-goryachiy_shokolad/") !== false || strpos($curr_page, "/_size-is-xs-s/") !== false){
            #WEBIT TEST
            $url2 = explode("/catalog", $curr_page)[1];
            
            $title = "";
            $description = "";
            $urlParts = explode("/", $url2);
            
            if (strpos($urlParts[4], "_color") !== false && !empty($_COOKIE["color_filter"])) {
                $colors = explode(",", $_COOKIE["color_filter"]);
                $title .= "Купить женские трусы цвета ";
                $description .= "Женские трусы цвета ";
                foreach ($colors as $color) {
                    if (!empty($color)) {
                        $title .= strtolower($color) . ", ";
                        $description .= strtolower($color) . ", ";
                    }
                }
                $title = substr($title, 0, strlen($title) - 2);
                $description = substr($description, 0, strlen($description) - 2);
                
                $title .= " в Москве по цене от ".min($minPrice)." руб. в интернет-магазине belle you";
                $description .= " купить в Москве. Более ".count($minPrice)." товаров в каталоге. Цены от ".min($minPrice)." руб. Быстрая доставка по России.";
            }

            if ((strpos($urlParts[4], "_size") !== false || strpos($urlParts[5], "_size") !== false) && !empty($_COOKIE["size_filter"])) {
                $sizes = explode(",", $_COOKIE["size_filter"]);
                if (strpos($urlParts[4], "_color") !== false) {
                    $title .= ", ";
                    $description .= ", ";
                }
                $title .= "размер: ";
                $description .= "размер: ";
                foreach ($sizes as $size) {
                    if (!empty($size)) {
                        $title .= $size . ", ";
                        $description .= $size . ", ";
                    }
                }
                $title = substr($title, 0, strlen($title) - 2);
                $description = substr($description, 0, strlen($description) - 2);
            }
            
            if(strpos($curr_page, "/_color-is-goryachiy_shokolad/") !== false){
                $APPLICATION->SetPageProperty('title', "Купить женские трусы цвета горячий шоколад в Москве по цене от ".min($minPrice)." руб. в интернет-магазине belle you");
                $APPLICATION->SetPageProperty('description', "Женские трусы цвета горячий шоколад купить в Москве. Более ".count($minPrice)." товаров в каталоге. Цены от ".min($minPrice)." руб. Быстрая доставка по России.");   
            }
            if(strpos($curr_page, "/_size-is-xs-s/") !== false){
                $APPLICATION->SetPageProperty('title', "Купить женские трусы размер XS/S в Москве по цене от ".min($minPrice)." руб. в интернет-магазине belle you");
                $APPLICATION->SetPageProperty('description', "Женские трусы размер XS/S купить в Москве. Более ".count($minPrice)." товаров в каталоге. Цены от ".min($minPrice)." руб. Быстрая доставка по России.");   
            }            
        }else{
            $url2 = explode("/catalog", $curr_page)[1];

            $hlblMetaTags = "20";
            $hlblockMetaTags = Bitrix\Highloadblock\HighloadBlockTable::getById($hlblMetaTags)->fetch();
            $entityMetaTags = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblockMetaTags);
            $entity_data_class_meta_tags = $entityMetaTags->getDataClass();
            $rsData = $entity_data_class_meta_tags::getList([
                "select" => ["*"],
                "order" => ["ID" => "ASC"],
                "filter" => ["UF_URL" => $url2]
            ]);
            $metaTags = [];
            while ($arData = $rsData->Fetch()) {
                $metaTags[] = $arData;
            }
            
            if (!empty($metaTags)) {
                if(!$title){ 
                    $APPLICATION->SetPageProperty('title', $metaTags[0]["UF_TITLE"]);
                }
                if(!$description){
                    $APPLICATION->SetPageProperty('description', $metaTags[0]["UF_DESCRIPTION"]);
                }
            } else {
                $title = "";
                $description = "";
                $urlParts = explode("/", $url2);
                
                if($urlParts[3] !== "filter"){
                    $section = $urlParts[1];
                    $old_structure = 1;    
                }else{
                    $section = $urlParts[2];
                    $old_structure = 0;    
                }

                $sectionObj = CIBlockSection::GetList(
                    ["SORT" => "ASC"],
                    ["IBLOCK_ID" => 1, "CODE" => $section],
                    ["NAME"]
                );
                if ($arSection = $sectionObj->Fetch()) {
                    $title .= $arSection["NAME"] . ", ";
                    $description .= $arSection["NAME"] . ", ";                  
                }   

                if($old_structure == 1){
                    if (strpos($urlParts[3], "_color") !== false && !empty($_COOKIE["color_filter"])) {
                        $colors = explode(",", $_COOKIE["color_filter"]);
                        $title .= "цвет: ";
                        $description .= "цвет: ";
                        foreach ($colors as $color) {
                            if (!empty($color)) {
                                $title .= strtolower($color) . ", ";
                                $description .= strtolower($color) . ", ";
                            }
                        }
                        $title = substr($title, 0, strlen($title) - 2);
                        $description = substr($description, 0, strlen($description) - 2);
                    }

                    if ((strpos($urlParts[3], "_size") !== false || strpos($urlParts[4], "_size") !== false) && !empty($_COOKIE["size_filter"])) {
                        $sizes = explode(",", $_COOKIE["size_filter"]);
                        if (strpos($urlParts[3], "_color") !== false) {
                            $title .= ", ";
                            $description .= ", ";
                        }
                        $title .= "размер: ";
                        $description .= "размер: ";
                        foreach ($sizes as $size) {
                            if (!empty($size)) {
                                $title .= $size . ", ";
                                $description .= $size . ", ";
                            }
                        }
                        $title = substr($title, 0, strlen($title) - 2);
                        $description = substr($description, 0, strlen($description) - 2);
                    }

                    if ((strpos($urlParts[3], "dopolnitelnyy_tip") !== false || strpos($urlParts[4], "dopolnitelnyy_tip") !== false ||
                        strpos($urlParts[5], "dopolnitelnyy_tip") !== false) && !empty($_COOKIE["type_filter"])) {
                        $types = explode(",", $_COOKIE["type_filter"]);
                        if (strpos($urlParts[3], "_color") !== false || strpos($urlParts[3], "_size") !== false
                            || strpos($urlParts[4], "_size") !== false) {
                            $title .= ", ";
                            $description .= ", ";
                        }
                        $title .= "тип: ";
                        $description .= "тип: ";
                        foreach ($types as $type) {
                            if (!empty($type)) {
                                $title .= strtolower($type) . ", ";
                                $description .= strtolower($type) . ", ";
                            }
                        }
                        $title = substr($title, 0, strlen($title) - 2);
                        $description = substr($description, 0, strlen($description) - 2);
                    }
                }else{
                    if (strpos($urlParts[4], "_color") !== false && !empty($_COOKIE["color_filter"])) {
                        $colors = explode(",", $_COOKIE["color_filter"]);
                        $title .= "цвет: ";
                        $description .= "цвет: ";
                        foreach ($colors as $color) {
                            if (!empty($color)) {
                                $title .= strtolower($color) . ", ";
                                $description .= strtolower($color) . ", ";
                            }
                        }
                        $title = substr($title, 0, strlen($title) - 2);
                        $description = substr($description, 0, strlen($description) - 2);
                    }

                    if ((strpos($urlParts[4], "_size") !== false || strpos($urlParts[5], "_size") !== false) && !empty($_COOKIE["size_filter"])) {
                        $sizes = explode(",", $_COOKIE["size_filter"]);
                        if (strpos($urlParts[4], "_color") !== false) {
                            $title .= ", ";
                            $description .= ", ";
                        }
                        $title .= "размер: ";
                        $description .= "размер: ";
                        foreach ($sizes as $size) {
                            if (!empty($size)) {
                                $title .= $size . ", ";
                                $description .= $size . ", ";
                            }
                        }
                        $title = substr($title, 0, strlen($title) - 2);
                        $description = substr($description, 0, strlen($description) - 2);
                    }

                    if ((strpos($urlParts[4], "dopolnitelnyy_tip") !== false || strpos($urlParts[5], "dopolnitelnyy_tip") !== false ||
                        strpos($urlParts[6], "dopolnitelnyy_tip") !== false) && !empty($_COOKIE["type_filter"])) {
                        $types = explode(",", $_COOKIE["type_filter"]);
                        if (strpos($urlParts[4], "_color") !== false || strpos($urlParts[4], "_size") !== false
                            || strpos($urlParts[5], "_size") !== false) {
                            $title .= ", ";
                            $description .= ", ";
                        }
                        $title .= "тип: ";
                        $description .= "тип: ";
                        foreach ($types as $type) {
                            if (!empty($type)) {
                                $title .= strtolower($type) . ", ";
                                $description .= strtolower($type) . ", ";
                            }
                        }
                        $title = substr($title, 0, strlen($title) - 2);
                        $description = substr($description, 0, strlen($description) - 2);
                    }   
                }
                
                if(!empty($title) && !empty($description)){

                    $title .= " – Интернет-магазин belle you";

                    $buyArray = ["купить", "заказать", "выбрать", "приобрести"];
                    $belleYouArray = ["на сайте belle you.", "в интернет-магазине belle you."];
                    $additionalArray = [
                        "Большой каталог белья и одежды!",
                        "Доставка товаров по всей России!",
                        "Выгодная цена!",
                        "Экологичное производство!",
                        "Закажите онлайн!",
                        "Выбирайте в онлайн-магазине!",
                        "Лаконичный дизайн и качественные материалы!"
                    ];
                    $description .= " - " . $buyArray[rand(0,3)] . " " . $belleYouArray[rand(0,1)] . " " . $additionalArray[rand(0,6)];

                    $APPLICATION->SetPageProperty('title', $title);
                    $APPLICATION->SetPageProperty('description', $description);

                    $data = [
                        "UF_URL" => $url2,
                        "UF_TITLE" => $title,
                        "UF_DESCRIPTION" => $description
                    ];
                    $entity_data_class_meta_tags::add($data);
                    
                }
            }
        }
    }*/
}
?>

<!-- стили страницы каталог -->
<link rel="stylesheet" href="<?=SITE_TEMPLATE_PATH?>/css/page-catalog.css">
<script defer src="<?=SITE_TEMPLATE_PATH?>/js/jquery-pagination.js"></script>
<script defer src="<?=SITE_TEMPLATE_PATH?>/js/jquery-catalog-header.js"></script>

<!-- стили для фильтра -->
<link rel="stylesheet" href="<?=SITE_TEMPLATE_PATH?>/css/belleyou-filter.css">
<script defer src="<?=SITE_TEMPLATE_PATH?>/js/jquery-catalog-filter.js"></script>
