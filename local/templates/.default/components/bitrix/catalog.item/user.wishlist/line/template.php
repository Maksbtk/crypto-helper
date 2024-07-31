<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Localization\Loc;

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $item
 * @var array $actualItem
 * @var array $minOffer
 * @var array $itemIds
 * @var array $price
 * @var array $measureRatio
 * @var bool $haveOffers
 * @var bool $showSubscribe
 * @var array $morePhoto
 * @var bool $showSlider
 * @var bool $itemHasDetailUrl
 * @var string $imgTitle
 * @var string $productTitle
 * @var string $buttonSizeClass
 * @var string $discountPositionClass
 * @var string $labelPositionClass
 * @var CatalogSectionComponent $component
 */

if ($haveOffers)
{


    $showDisplayProps = !empty($item['DISPLAY_PROPERTIES']);
	$showProductProps = $arParams['PRODUCT_DISPLAY_MODE'] === 'Y' && $item['OFFERS_PROPS_DISPLAY'];
	$showPropsBlock = $showDisplayProps || $showProductProps;
	$showSkuBlock = $arParams['PRODUCT_DISPLAY_MODE'] === 'Y' && !empty($item['OFFERS_PROP']);
}
else
{
	$showDisplayProps = !empty($item['DISPLAY_PROPERTIES']);
	$showProductProps = $arParams['ADD_PROPERTIES_TO_BASKET'] === 'Y' && !empty($item['PRODUCT_PROPERTIES']);
	$showPropsBlock = $showDisplayProps || $showProductProps;
	$showSkuBlock = false;
}
?>
<?
$colors = $arParams['SORTED_COLOR'];

if (!$item['PREVIEW_PICTURE_RESIZE']['src']) {
    $item['PREVIEW_PICTURE_RESIZE']['src'] = '/local/templates/belleyou/img/no_photo.png';
}
?>
    <div class="product-media-wrapper <?if(!$actualItem['CAN_BUY']):?>item-not-available<?endif;?>">
        <?// тут картинки?>
        <? if ($itemHasDetailUrl): ?>
            <a class="product-link" href="<?=$item['DETAIL_PAGE_URL']?>" title="<?=$productTitle?>">
        <? endif; ?>
            <div class="product-picture-wrapper">
                <?/*<img  id="<?=$itemIds['PICT']?>" class="product-picture" src="<?=$item['PREVIEW_PICTURE']['SRC']?>" alt="<?=$productTitle?>">*/?>
                <img  id="<?=$itemIds['PICT']?>" class="product-picture" src="<?=$item['PREVIEW_PICTURE_RESIZE']['src']?>" alt="<?=$productTitle?>">
            </div>
            <?if(!$actualItem['CAN_BUY']):?>
                <div class="product-labels">
                    <span class="product-label">Нет в наличии</span>
                </div>
            <?endif;?>
        <? if ($itemHasDetailUrl): ?>
            </a>
        <? endif; ?>

        <a class="button-add-to-favorite <?= $GLOBALS['USER']->isAuthorized() ? ' js-check-wishlist-button' : ' js-call-auth' ?> _added" data-id="<?=$item['ID']?>"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16"><path stroke="#A0BCD2" stroke-linecap="round" stroke-linejoin="round" d="M15.023.53c-2.047-.23-4.25.912-5.024 2.618C9.225 1.442 7 .301 4.976.53 2.28.823.553 3.57 1.099 6.22c.744 3.604 4.801 5.214 8.9 9.281 4.081-4.067 8.156-5.683 8.9-9.282.547-2.65-1.182-5.395-3.876-5.689z"></path></svg></a>

        <?/*<div class="product-labels">
            <span class="product-label">New</span>
        </div>*/?>
    </div>
    <div class="product-info-wrapper">
        <? if ($itemHasDetailUrl): ?>
            <a class="product-link" href="<?=$item['DETAIL_PAGE_URL']?>" title="<?=$item['PROPERTIES']['SAYT_NAIMENOVANIE_DLYA_SAYTA']['VALUE']?>">
        <? endif; ?>
                <div class="h4 product-name"><?=$item['PROPERTIES']['SAYT_NAIMENOVANIE_DLYA_SAYTA']['VALUE']?></div>
        <? if ($itemHasDetailUrl): ?>
            </a>
        <? endif; ?>

        <div class="product-pricebox">
            <?
            // тут цена
            foreach ($arParams['PRODUCT_BLOCKS_ORDER'] as $blockName)
            {
                switch ($blockName)
                {
                    case 'price': ?>
                        <div data-entity="price-block">
                            <?

                            if ($arParams['SHOW_OLD_PRICE'] === 'Y')
                            {
                                ?>
                                <span class="proudct-old-price"  id="<?=$itemIds['PRICE_OLD']?>"
									<?=($price['RATIO_PRICE'] >= $price['RATIO_BASE_PRICE'] ? 'style="display: none;"' : '')?>>
									<?=$price['PRINT_RATIO_BASE_PRICE']?>
								</span>&nbsp;
                                <?
                            }
                            ?>
                            <span class="proudct-current-price" id="<?=$itemIds['PRICE']?>">
								<?

                                if (!empty($price))
                                {
                                    if ($arParams['PRODUCT_DISPLAY_MODE'] === 'N' && $haveOffers)
                                    {
                                        echo Loc::getMessage(
                                            'CT_BCI_TPL_MESS_PRICE_SIMPLE_MODE',
                                            array(
                                                '#PRICE#' => $price['PRINT_RATIO_PRICE'],
                                                '#VALUE#' => $measureRatio,
                                                '#UNIT#' => $minOffer['ITEM_MEASURE']['TITLE']
                                            )
                                        );
                                    }
                                    else
                                    {
                                        echo $price['PRINT_RATIO_PRICE'];
                                    }
                                } else {
                                    //echo '-';
                                }
                                ?>
							</span>
                        </div>
                        <?
                        break;
                }
            }
            ?>
        </div>


        <?//цвета?>

        <?if($actualItem['CAN_BUY']):?>
            <div class="product-colors-sheme">
                <ul class="product-colors-list">
                    <?$colorCnt = 1;
                    foreach($colors as $colorCode => $color){
                        if($colorCnt == 4){break;}?>
                        <li class="product-color product-color__with-border">
                            <a href="<?=$color['DETAIL_PAGE_URL']?>" target="_blank" style="background: url('/upload/colors/<?=$color['COLOR_CODE']?>.jpg') no-repeat;" title="<?=$color['NAME']?>"></a>
                        </li>
                        <?$colorCnt++;
                    }?>
                </ul>
                <?if(count($colors) > 3){
                    $totalColorCnt = count($colors)-3;?>
                    <span class="product-more-colors-label">+<?=num_word($totalColorCnt, array('цвет', 'цвета', 'цветов'))?></span>
                <?}?>
            </div>
        <?endif;?>



        <?
        // вывод ску
        if ($showSkuBlock)
        {
            ?>
            <div>
                <div id="<?=$itemIds['PROP_DIV']?>">
                    <?
                    foreach ($arParams['SKU_PROPS'] as $skuProperty)
                    {

                        $propertyId = $skuProperty['ID'];
                        $skuProperty['NAME'] = htmlspecialcharsbx($skuProperty['NAME']);
                        if (!isset($item['SKU_TREE_VALUES'][$propertyId]))
                            continue;
                        ?>

                            <?if($skuProperty['CODE'] == '_SIZE') :?>
                            <div data-entity="sku-block">

                                    <div class="dropdown dropdown-size" data-entity="sku-line-block">
                                        <div class="dropdown-select"><span>Размер</span></div>
                                        <ul class="dropdown-box" data-type="size">
                                            <?
                                            foreach ($skuProperty['VALUES'] as $value)
                                            {
                                            if (!isset($item['SKU_TREE_VALUES'][$propertyId][$value['ID']]))
                                                continue;

                                            $value['NAME'] = htmlspecialcharsbx($value['NAME']);
                                            ?>

                                                <li class="dropdown-option" data-label="<?=$value['ID']?>" title="<?=$value['NAME']?>"
                                                    data-treevalue="<?=$propertyId?>_<?=$value['ID']?>" data-onevalue="<?=$value['ID']?>">
                                                   <?=$value['NAME']?>
                                                </li>
                                            <?
                                            }
                                            ?>
                                        </ul>
                                    </div>
                                </div>
                            <?else:?>
                                <?// сюда можно бахнуть цвета?>
                                <?/*<div class="product-item-info-container" data-entity="sku-block">
                                    <div class="product-item-scu-container" data-entity="sku-line-block">
                                        <?=$skuProperty['NAME']?>
                                        <div class="product-item-scu-block">
                                            <div class="product-item-scu-list">
                                                <ul class="product-item-scu-item-list">
                                                    <?
                                                    foreach ($skuProperty['VALUES'] as $value)
                                                    {
                                                        if (!isset($item['SKU_TREE_VALUES'][$propertyId][$value['ID']]))
                                                            continue;

                                                        $value['NAME'] = htmlspecialcharsbx($value['NAME']);

                                                        if ($skuProperty['SHOW_MODE'] === 'PICT')
                                                        {
                                                            ?>
                                                            <li class="product-item-scu-item-color-container" title="<?=$value['NAME']?>"
                                                                data-treevalue="<?=$propertyId?>_<?=$value['ID']?>" data-onevalue="<?=$value['ID']?>">
                                                                <div class="product-item-scu-item-color-block">
                                                                    <div class="product-item-scu-item-color" title="<?=$value['NAME']?>"
                                                                         style="background-image: url('<?=$value['PICT']['SRC']?>');">
                                                                    </div>
                                                                </div>
                                                            </li>
                                                            <?
                                                        }
                                                        else
                                                        {
                                                            ?>
                                                            <li class="product-item-scu-item-text-container" title="<?=$value['NAME']?>"
                                                                data-treevalue="<?=$propertyId?>_<?=$value['ID']?>" data-onevalue="<?=$value['ID']?>">
                                                                <div class="product-item-scu-item-text-block">
                                                                    <div class="product-item-scu-item-text"><?=$value['NAME']?></div>
                                                                </div>
                                                            </li>
                                                            <?
                                                        }
                                                    }
                                                    ?>
                                                </ul>
                                                <div style="clear: both;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>*/?>
                            <?endif;?>

                        <?
                    }
                    ?>
                </div>
                <?
                foreach ($arParams['SKU_PROPS'] as $skuProperty)
                {
                    if (!isset($item['OFFERS_PROP'][$skuProperty['CODE']]))
                        continue;

                    $skuProps[] = array(
                        'ID' => $skuProperty['ID'],
                        'SHOW_MODE' => $skuProperty['SHOW_MODE'],
                        'VALUES' => $skuProperty['VALUES'],
                        'VALUES_COUNT' => $skuProperty['VALUES_COUNT']
                    );
                }

                unset($skuProperty, $value);

                if ($item['OFFERS_PROPS_DISPLAY'])
                {
                    foreach ($item['JS_OFFERS'] as $keyOffer => $jsOffer)
                    {
                        $strProps = '';

                        if (!empty($jsOffer['DISPLAY_PROPERTIES']))
                        {
                            foreach ($jsOffer['DISPLAY_PROPERTIES'] as $displayProperty)
                            {
                                $strProps .= '<dt>'.$displayProperty['NAME'].'</dt><dd>'
                                    .(is_array($displayProperty['VALUE'])
                                        ? implode(' / ', $displayProperty['VALUE'])
                                        : $displayProperty['VALUE'])
                                    .'</dd>';
                            }
                        }
                        $item['JS_OFFERS'][$keyOffer]['DISPLAY_PROPERTIES'] = $strProps;
                    }
                    unset($jsOffer, $strProps);
                }
                ?>
            </div>
            <?
        } else {/*
            ?>
            <div data-entity="sku-block">
                <div class="dropdown dropdown-size" data-entity="sku-line-block">
                    <div class="dropdown-select"><span>-</span></div>
                    <ul class="dropdown-box" data-type="size">
                        <li class="dropdown-option selected">
                        -
                        </li>
                    </ul>
                </div>
            </div>
        <?
        */}

        ?>

    </div>

    <div class="product-actions">
            <?
            //вывод кнопок "купить" "нет в наличии" "подробнее"
            foreach ($arParams['PRODUCT_BLOCKS_ORDER'] as $blockName)
            {
                switch ($blockName)
                {
                    case 'buttons':
                        ?>
                        <div class="product-item-info-container" data-entity="buttons-block">
                            <?
                            if (!$haveOffers)
                            {
                                if ($actualItem['CAN_BUY'])
                                {
                                    ?>
                                    <a class="button button-add-to-cart" id="<?=$itemIds['BUY_LINK']?>"
                                       href="javascript:void(0)" rel="nofollow">
                                        <?=($arParams['ADD_TO_BASKET_ACTION'] === 'BUY' ? $arParams['MESS_BTN_BUY'] : $arParams['MESS_BTN_ADD_TO_BASKET'])?>
                                    </a>
                                    <?
                                }
                                else
                                {
                                    /*?>
                                    <a class="button button-add-to-cart disabled" id="<?=$itemIds['NOT_AVAILABLE_MESS']?>"
                                       href="javascript:void(0)" rel="nofollow">
                                        <?=$arParams['MESS_NOT_AVAILABLE']?>
                                    </a>
                                    <?*/
                                }
                            }
                            else
                            {
                                if ($arParams['PRODUCT_DISPLAY_MODE'] === 'Y')
                                {
                                    ?>
                                    <a class="button button-add-to-cart disabled"
                                       id="<?=$itemIds['NOT_AVAILABLE_MESS']?>" href="javascript:void(0)" rel="nofollow"
                                        <?=($actualItem['CAN_BUY'] ? 'style="display: none;"' : '')?>>
                                        <?=$arParams['MESS_NOT_AVAILABLE']?>
                                    </a>
                                    <div id="<?=$itemIds['BASKET_ACTIONS']?>" <?=($actualItem['CAN_BUY'] ? '' : 'style="display: none;"')?>>
                                        <a class="button button-add-to-cart" id="<?=$itemIds['BUY_LINK']?>"
                                           href="javascript:void(0)" rel="nofollow">
                                            <?=($arParams['ADD_TO_BASKET_ACTION'] === 'BUY' ? $arParams['MESS_BTN_BUY'] : $arParams['MESS_BTN_ADD_TO_BASKET'])?>
                                        </a>
                                    </div>
                                    <?
                                }
                                else
                                {
                                    ?>
                                    <a class="button button-add-to-cart" href="<?=$item['DETAIL_PAGE_URL']?>">
                                        <?=$arParams['MESS_BTN_DETAIL']?>
                                    </a>
                                    <?
                                }
                            }
                            ?>
                        </div>
                        <?
                        break;
                }
            }
            ?>
    </div>


