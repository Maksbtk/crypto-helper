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
//echo '<pre>'; var_dump($actualItem); echo '</pre>';

/*$actualItem['CAN_BUY'] = false;
if ($actualItem['PRODUCT']['AVAILABLE'] == 'Y')
    $actualItem['CAN_BUY'] = true;*/

?>
<?

if (!$item['PREVIEW_PICTURE_RESIZE']['src']) {
    $item['PREVIEW_PICTURE_RESIZE']['src'] = '/local/templates/maksv/img/no_photo.png';
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
                    <span class="product-label">Недоступно</span>
                </div>
            <?endif;?>
        <? if ($itemHasDetailUrl): ?>
            </a>
        <? endif; ?>

    </div>
    <div class="product-info-wrapper">
        <? if ($itemHasDetailUrl): ?>
            <a class="product-link" href="<?=$item['DETAIL_PAGE_URL']?>" title="<?=$productTitle?>">
        <? endif; ?>
                <div class="h4 product-name"><?=$productTitle?></div>
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
                                    <a class="button white-color-fontbutton-add-to-cart disabled"
                                       id="<?=$itemIds['NOT_AVAILABLE_MESS']?>" href="javascript:void(0)" rel="nofollow"
                                        <?=($actualItem['CAN_BUY'] ? 'style="display: none;"' : '')?>>
                                        <?=$arParams['MESS_NOT_AVAILABLE']?>
                                    </a>
                                    <div id="<?=$itemIds['BASKET_ACTIONS']?>" <?=($actualItem['CAN_BUY'] ? '' : 'style="display: none;"')?>>
                                        <a class="button white-color-font  button-add-to-cart" id="<?=$itemIds['BUY_LINK']?>"
                                           href="javascript:void(0)" rel="nofollow">
                                            <?=($arParams['ADD_TO_BASKET_ACTION'] === 'BUY' ? $arParams['MESS_BTN_BUY'] : $arParams['MESS_BTN_ADD_TO_BASKET'])?>
                                        </a>
                                    </div>

                                   <?/* <a class="button  white-color-font button-add-to-cart" id="<?=$itemIds['BUY_LINK']?>"
                                       href="javascript:void(0)" rel="nofollow">
                                        <?=($arParams['ADD_TO_BASKET_ACTION'] === 'BUY' ? $arParams['MESS_BTN_BUY'] : $arParams['MESS_BTN_ADD_TO_BASKET'])?>
                                    </a>*/?>
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


