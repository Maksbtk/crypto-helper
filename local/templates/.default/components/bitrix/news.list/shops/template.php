<?
use Bitrix\Main\Page\Asset;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
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
$this->setFrameMode(true);

Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/page-shops.css");
$curPage = $APPLICATION->GetCurPage(false);
?>

<div class="page-shops-layout">
    <aside class="shops-list-bar">
        <h1 class="page-shops-title">Магазины</h1>
        <div class="shops-navigation">
            <div class="dropdown dropdown-shops-city">
                <div class="dropdown-select">
                    <?=$arResult['CITYS'][$arParams['CURRENT_CITY_ID']]?>
                </div>
                <ul class="dropdown-box">
                    <? foreach ($arResult['CITYS'] as $id => $name):?>
                        <li class="dropdown-option"
                            <?/*data-label="1"*/?>
                            data-id="<?=$id?>"
                            data-direction="<?=$curPage?>?cityId=<?=$id?>"
                        >
                            <?=$name?>
                        </li>
                    <?endforeach;?>
                </ul>
            </div>
            <!--<div class="shops-menu-mobile">
                <div class="shops-menu-mobile-item item-list active">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M19 8H7m12-6H7m12 12H7M3 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm0-6a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm0 12a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>
                </div>
                <div class="shops-menu-mobile-item item-map">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M5 14.286c-1.851.817-3 1.955-3 3.214C2 19.985 6.477 22 12 22s10-2.015 10-4.5c0-1.259-1.149-2.397-3-3.214M18 8c0 4.064-4.5 6-6 9-1.5-3-6-4.936-6-9a6 6 0 1 1 12 0zm-5 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>
                </div>
            </div>-->
        </div>
        <?
        $defSize = $arParams["DEF_MAP_SIZE"];
        $detailSize = $arParams["DETAIL_MAP_SIZE"];
        ?>
        <ul class="shops-content shops-list">
            <?foreach($arResult["ITEMS"] as $key => $arItem):?>
                <?
                $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
                $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));

                $arTmp = explode(',', $arItem['PROPERTIES']['COORDINATES']['VALUE']);
                $latitude = $arTmp[0];
                $longitude = $arTmp[1];
                ?>
                <li class="shop-item">
                <a href="javascript:void(0)"
                   class="shop-link"
                   data-latitude="<?=$latitude?>"
                   data-longitude ="<?=$longitude?>"
                   data-size="<?=$detailSize?>"
                   id="<?=$this->GetEditAreaId($arItem['ID']);?>"
                >
                    <h3 class="shop-name"><?=$arItem['NAME']?></h3>
                    <p class="shop-phone">
                        <?=$arItem['PROPERTIES']['PHONE']['VALUE']?>
                    </p>
                    <div class="shop-address">
                        <p>
                            <?=$arItem['PROPERTIES']['ADDRESS']['VALUE']?>
                        </p>
                    </div>
                    <div class="shop-open-hours">
                        <p>
                            <?=html_entity_decode($arItem['PROPERTIES']['WORK_TIME']['VALUE'])?>
                        </p>
                    </div>
                </a>
            </li>
            <?endforeach;?>

            <?/*if($arParams["DISPLAY_BOTTOM_PAGER"]):?>
                <br /><?=$arResult["NAV_STRING"]?>
            <?endif;*/?>
        </ul>
        <div class="shop-open">
            <a href="javascript:void(0)"
               data-latitude="<?=$latitude?>"
               data-longitude ="<?=$longitude?>"
               data-size="<?=$defSize?>"
               class="link-back-to-shopslist">
                Вернуться к списку
            </a>
            <div class="shop-open-body">
            </div>
        </div>
    </aside>
    <section class="shops-content shops-map-bar">
        <div class="sticky-map" >

            <?$APPLICATION->IncludeComponent("bitrix:map.yandex.view", "", Array(
                "API_KEY" => "",	// Ключ API
                "COMPONENT_TEMPLATE" => "",
                "CONTROLS" => array(	// Элементы управления
                    0 => "ZOOM",
                    1 => "MINIMAP",
                    2 => "TYPECONTROL",
                    3 => "SCALELINE",
                ),
                "INIT_MAP_TYPE" => "MAP",	// Стартовый тип карты
                "MAP_DATA" => serialize($arResult['POSITION']),
                /*"MAP_DATA" => "a:4:{s:10:\"yandex_lat\";d:55.74994362148292;s:10:\"yandex_lon\";d:37.551869992818176;s:12:\"yandex_scale\";i:10;s:10:\"PLACEMARKS\";a:2:{i:0;a:3:{s:3:\"LON\";d:37.596499119234;s:3:\"LAT\";d:55.755351826228;s:4:\"TEXT\";s:6:\"йцу\";}i:1;a:3:{s:3:\"LON\";d:37.61499623505;s:3:\"LAT\";d:55.75879433907;s:4:\"TEXT\";s:6:\"уцй\";}}}",	*/// Данные, выводимые на карте
                "MAP_HEIGHT" => "100%", // Высота карты
                "MAP_WIDTH" => "100%", // Ширина карты
                "MAP_ID" => "SHOPS_MAP", // Идентификатор карты
                "OPTIONS" => array(	// Настройки
                    0 => "ENABLE_SCROLL_ZOOM",
                    1 => "ENABLE_DBLCLICK_ZOOM",
                    2 => "ENABLE_DRAGGING",
                )
            ),
                false
            );?>
            <?/*<img width="1233" alt="map" src="/local/templates/belleyou/demo-pics/map.jpg">*/?>

        </div>
    </section>
</div>

