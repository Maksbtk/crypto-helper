<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Page\Asset; ?>

<?$db_props = CSaleOrderPropsValue::GetOrderProps($arResult['ID']);
while ($arProps = $db_props->Fetch())
{
    if ($arProps["NAME"]=="Промокод")
   {
      $promo = $arProps["VALUE"];
   }
   if ($arProps["NAME"]=="Скидка по купону")
   {
      $promo_sale = $arProps["VALUE"];
   }
}
//echo '<pre>'; var_dump($arResult); echo '</pre>';
?>


<table class="pastorder-contains__table ri-pastorder-contains__table">
    <thead>
        <tr>
            <th></th>
            <th>Наименование товара</th>
            <th>Цвет</th>
            <th>Размер</th>
            <th>Цена</th>
            <th>Количество</th>
            <th>Сумма</th>
        </tr>
    </thead>
    <tbody>                    
        <?foreach($arResult["BASKET"] as $key => $prod) {
            if(!in_array($prod['ID'], $_SESSION['return_prods'])){
                continue;   
            }
            $color = $arResult['COLORS'][$prod['PRODUCT_ID']];?> 
            <tr class="prods_list">
                <td>                        
                    <?$file = CFile::ResizeImageGet($prod['PREVIEW_PICTURE'], array('width'=>82, 'height'=>74), BX_RESIZE_IMAGE_EXACT, true);?>
                    <span class="pastorder-item-image" style="background-image: url(<?=$file["src"]?>); width:82px; height:74px;"></span>
                </td>
                <td>                        
                    <span class="pastorder-item-art">
                        <?foreach($prod['PROPS'] as $prop){
                            if($prop['CODE'] == "CML2_ARTICLE"){
                                $art = $prop['VALUE'];
                                echo "арт. ".$prop['VALUE'];
                            }
                        }
                        
                        foreach($prod['PROPS'] as $prop){
                            if($prop['CODE'] == "_SIZE"){
                                $size = $prop['VALUE'];
                            }
                        }?>                                                                
                    </span>
                    
                    <?$name = str_replace($art,'',$prod["NAME"]);?>
                    <?$name = str_replace($size,'',$name);?>

                    <div class="pastorder-item-name pastorder-item-name-prod">
                        <a href="<?=$prod["DETAIL_PAGE_URL"]?>" data-prod="<?=$prod["NAME"]?>" target="_blank" class="prod_name"><?=$name?></a>
                    </div> 
                    
                    <input type="hidden" class="prod_full_name" name="prod_full_name[]" value="<?=$art." ".$name." ".$size?>"> 
                </td>
                <td>
                    <? if (!empty($color)) { ?>
                        <span class="pastorder-item-color" style="background-image: url(<?=$color?>)"></span>
                    <? } elseif (!empty($prod["PROPERTY__COLOR_VALUE"])) { ?>
                        <span class="pastorder-item-color"><?=$prod["PROPERTY__COLOR_VALUE"]?></span>
                    <? } ?>                       
                </td>
                <td data-label="Размер: ">
                    <?foreach($prod['PROPS'] as $prop){
                        if($prop['CODE'] == "_SIZE"){
                            echo $prop['VALUE'];
                        }
                    }?>                 
                </td>
                <td data-label="Цена: ">                      
                    <?if(!empty($prod["BASE_PRICE"]) && $prod["BASE_PRICE"] !== $prod["PRICE"]){?><span class="pastorder-item-oldprice"><?=\CCurrencyLang::CurrencyFormat($prod["BASE_PRICE"], 'RUB');?></span><?}?>
                    <span class="pastorder-item-price"><?=\CCurrencyLang::CurrencyFormat($prod["PRICE"], 'RUB');?></span>
                </td>
                <td data-label="Количество: " class="quantity-class"><?=$prod["QUANTITY"]?> шт</td>
                <td data-label="Сумма: ">                        
                    <?if(!empty($prod["BASE_PRICE_FORMATED"]) && $prod["BASE_PRICE_FORMATED"] !== $prod["PRICE_FORMATED"]){?><span class="pastorder-item-oldsum"><?=$prod['BASE_PRICE_FORMATED']?></span><?}?>
                    <span class="pastorder-item-sum"><?=$prod['PRICE_FORMATED']?></span>
                </td>
            </tr>
            <tr>
                <td colspan="7">
                    <div class="ri-select-container">
                        <select class="reason__select" name="reasonselect[]" id="reasonSelect" required="">
                            <option value="0">Выберите причину</option>
                            <option value="1">Не подошло</option>
                            <option value="2">Брак</option>
                            <option value="3">Доставлен не тот товар</option>
                            <option value="4">Заказ не был доставлен</option>
                        </select>
                    </div>
                </td>
            </tr>
        <?}?>                                         
    </tbody>
</table>