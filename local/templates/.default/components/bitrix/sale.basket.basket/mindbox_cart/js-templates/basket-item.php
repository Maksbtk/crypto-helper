<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

/**
 * @var array $mobileColumns
 * @var array $arParams
 * @var string $templateFolder
 */

$usePriceInAdditionalColumn = in_array('PRICE', $arParams['COLUMNS_LIST']) && $arParams['PRICE_DISPLAY_MODE'] === 'Y';
$useSumColumn = in_array('SUM', $arParams['COLUMNS_LIST']);
$useActionColumn = in_array('DELETE', $arParams['COLUMNS_LIST']);

$restoreColSpan = 2 + $usePriceInAdditionalColumn + $useSumColumn + $useActionColumn;

$positionClassMap = array(
    'left' => 'basket-item-label-left',
    'center' => 'basket-item-label-center',
    'right' => 'basket-item-label-right',
    'bottom' => 'basket-item-label-bottom',
    'middle' => 'basket-item-label-middle',
    'top' => 'basket-item-label-top'
);

$discountPositionClass = '';
if ($arParams['SHOW_DISCOUNT_PERCENT'] === 'Y' && !empty($arParams['DISCOUNT_PERCENT_POSITION']))
{
    foreach (explode('-', $arParams['DISCOUNT_PERCENT_POSITION']) as $pos)
    {
        $discountPositionClass .= isset($positionClassMap[$pos]) ? ' '.$positionClassMap[$pos] : '';
    }
}

$labelPositionClass = '';
if (!empty($arParams['LABEL_PROP_POSITION']))
{
    foreach (explode('-', $arParams['LABEL_PROP_POSITION']) as $pos)
    {
        $labelPositionClass .= isset($positionClassMap[$pos]) ? ' '.$positionClassMap[$pos] : '';
    }
}
?>
<script id="basket-item-template" type="text/html">
    <tr class="basket-item basket-items-list-item-container{{#SHOW_RESTORE}} basket-items-list-item-container-expend{{/SHOW_RESTORE}}{{#NOT_AVAILABLE}} item-not-available{{/NOT_AVAILABLE}}" id="basket-item-{{ID}}" data-entity="basket-item" data-id="{{ID}}">
        {{#SHOW_RESTORE}}
            <!--<td class="basket-items-list-item-notification" colspan="<?=$restoreColSpan?>">
                <div class="basket-items-list-item-notification-inner basket-items-list-item-notification-removed" id="basket-item-height-aligner-{{ID}}">
                    {{#SHOW_LOADING}}
                        <div class="basket-items-list-item-overlay"></div>
                    {{/SHOW_LOADING}}
                    <div class="basket-items-list-item-removed-container">
                        <div>
                            <?=Loc::getMessage('SBB_GOOD_CAP')?> <strong>{{NAME}}</strong> <?=Loc::getMessage('SBB_BASKET_ITEM_DELETED')?>.
                        </div>
                        <div class="basket-items-list-item-removed-block">
                            <a href="javascript:void(0)" data-entity="basket-item-restore-button">
                                <?=Loc::getMessage('SBB_BASKET_ITEM_RESTORE')?>
                            </a>
                            <span class="basket-items-list-item-clear-btn" data-entity="basket-item-close-restore-button"></span>
                        </div>
                    </div>
                </div>
            </td>->
        {{/SHOW_RESTORE}}    
        {{^SHOW_RESTORE}}
            <td class="basket-items-list-item-descriptions">
                {{#DETAIL_PAGE_URL}}
                    <a href="{{DETAIL_PAGE_URL}}" style="text-decoration:none">
                {{/DETAIL_PAGE_URL}}
                    <div class="basket-item-img" style="background-image: url({{{IMAGE_URL}}}{{^IMAGE_URL}}<?=$templateFolder?>/images/no_photo.png{{/IMAGE_URL}}"></div>
                    
                    <div class="h3 basket-item-name">{{NAME}}</div>
                    <p class="basket-item-art">арт. {{ARTICLE}}</p>
                    <p class="basket-item-color">Цвет: {{COLOR}}</p>
                    
                    <div class="basket-item-price">
                        {{#SHOW_DISCOUNT_PRICE}}
                            <span class="old-price">{{{SUM_FULL_PRICE_FORMATED}}}</span>
                        {{/SHOW_DISCOUNT_PRICE}}  
                        <span>{{^NOT_AVAILABLE}}{{{SUM_PRICE_FORMATED}}}{{/NOT_AVAILABLE}}</span>
                    </div>
                {{#DETAIL_PAGE_URL}}
                    </a>
                {{/DETAIL_PAGE_URL}}
                
                {{#IS_BOX}}
                <div class="basket-item-options">
                    <?
                    if (!empty($arParams['PRODUCT_BLOCKS_ORDER']))
                    {
                        foreach ($arParams['PRODUCT_BLOCKS_ORDER'] as $blockName)
                        {
                            switch (trim((string)$blockName))
                            {                    
                                case 'sku':?>
                                    {{#SKU_BLOCK_LIST}}
                                        {{^IS_IMAGE}}
                                            <div class="basket-item-options">
                                                <div class="dropdown dropdown-size" data-entity="basket-item-sku-block">
                                                    <div class="dropdown-select">{{#SKU_VALUES_LIST}}{{#SELECTED}}{{NAME}}{{/SELECTED}}{{/SKU_VALUES_LIST}}</div>
                                                    <ul class="dropdown-box">
                                                        {{#SKU_VALUES_LIST}}
                                                            <li class="dropdown-option{{#SELECTED}} selected{{/SELECTED}}{{#NOT_AVAILABLE_OFFER}} not-available{{/NOT_AVAILABLE_OFFER}}"
                                                                title="{{NAME}}"
                                                                data-entity="basket-item-sku-field"
                                                                data-initial="{{#SELECTED}}true{{/SELECTED}}{{^SELECTED}}false{{/SELECTED}}"
                                                                data-value-id="{{VALUE_ID}}"
                                                                data-sku-name="{{NAME}}"
                                                                data-property="{{PROP_CODE}}"                                                                        
                                                                data-label="{{VALUE_ID}}">
                                                                    {{NAME}}
                                                                </li>
                                                        {{/SKU_VALUES_LIST}}
                                                    </ul>
                                                </div>
                                            </div>
                                        {{/IS_IMAGE}}
                                    {{/SKU_BLOCK_LIST}}
                                <?break;
                            }
                        }
                    }?>
                                          
                    <div class="basket-item-amount basket-items-list-item-amount{{#NOT_AVAILABLE}} disabled{{/NOT_AVAILABLE}}" data-entity="basket-item-quantity-block">
                        <div class="amount-wrapper js-quantity">
                            <span class="button-minus" data-entity="basket-item-quantity-minus">-</span>
                            <input type="text" class="js-item-quantity" value="{{QUANTITY}}" {{#NOT_AVAILABLE}} disabled="disabled"{{/NOT_AVAILABLE}} data-value="{{QUANTITY}}" data-entity="basket-item-quantity-field" id="basket-item-quantity-{{BID}}">                    
                            <span class="button-plus" data-entity="basket-item-quantity-plus">+</span>
                        </div>
                    </div>
                    
                </div>
                {{/IS_BOX}}
                
                <td class="basket-items-list-item-remove" style="margin-top: auto">
                    <div class="basket-item-block-actions">
                        <button class="basket-item-actions-remove basket-item-delete" data-entity="basket-item-delete" data-mode="delete-item">Удалить</button>
                    </div>
                </td>
            </td>
        {{/SHOW_RESTORE}}
    </tr>
</script>