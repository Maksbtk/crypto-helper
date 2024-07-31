<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\PriceMaths,
    kb\coupons\CouponManager;

/**
 *
 * This file modifies result for every request (including AJAX).
 * Use it to edit output result for "{{ mustache }}" templates.
 *
 * @var array $result
 */

$this->arParams['BRAND_PROPERTY'] ??= '';

$mobileColumns = $this->arParams['COLUMNS_LIST_MOBILE'] ?? $this->arParams['COLUMNS_LIST'];
$mobileColumns = array_fill_keys($mobileColumns, true);

$result['BASKET_ITEM_RENDER_DATA'] = array();

// вытаскиваем id товара по id торгового предложения и складываем в массив
$basketProductIds = [];
foreach ($this->basketItems as $basketIem)
{
    $podInfo = CCatalogSku::GetProductInfo($basketIem['PRODUCT_ID']);
    if ($podInfo)
        $basketProductIds[$basketIem['PRODUCT_ID']] = $podInfo['ID'];
}

// вытаскиваем свойства для dataLayer
$contextProperties = [];
if ($basketProductIds)
{
    $dbItems = CIBlockElement::GetList(array(), array('IBLOCK_ID' => 2, "ID" => $basketProductIds), false, false, array('ID', 'IBLOCK_SECTION_ID', 'NAME', 'PROPERTY_SAYT_NAIMENOVANIE_DLYA_SAYTA'));
    while ($item = $dbItems->Fetch())
    {
        $contextProperties[$item['ID']] = [
            'NAME' => $item['NAME'],
            'NAME_FOR_SITE' => $item['PROPERTY_SAYT_NAIMENOVANIE_DLYA_SAYTA_VALUE'],
        ];
    }
}

foreach ($this->basketItems as $row)
{
    $itemProps = [];
    foreach($row['PROPS'] as $key => $prop){
        if($prop["CODE"] == "_COLOR"){
            $prop["NAME"] = "Цвет:";
            $prop["~NAME"] = "Цвет:";
            $itemProps[] = $prop;
            
            $color = $prop["VALUE"];
        }
    }    
    
    foreach($row['SKU_DATA'] as $key => $sku){
        if($sku["CODE"] == "_COLOR"){
            unset($row['SKU_DATA'][$key]);    
        }
    }
    
    $name = explode("(",$row['NAME']);

    $is_box = true;
    if($row['PRODUCT_ID'] == 76243){
        $is_box = false;
    }
    
    $rowData = array(
        'IS_BOX' => $is_box,
        'REAL_PRODUCT_ID' => $basketProductIds[$row['PRODUCT_ID']],
        'CONTEXT_PROP' => $contextProperties[$basketProductIds[$row['PRODUCT_ID']]],
        'BID' => $row['ID'],
		'ID' => $row['ID'],
        'PRODUCT_ID' => $row['PRODUCT_ID'],
		'ARTICLE' => $row['PROPERTY_CML2_ARTICLE_VALUE'],
		'NAME' => $name[0] ?? $row['NAME'],
		'QUANTITY' => $row['QUANTITY'],
        'COLOR' => $color,
		'PROPS' => $itemProps,
		'PROPS_ALL' => $row['PROPS_ALL'],
		'HASH' => $row['HASH'],
		'SORT' => $row['SORT'],
		'DETAIL_PAGE_URL' => $row['DETAIL_PAGE_URL'],
		'CURRENCY' => $row['CURRENCY'],
		'DISCOUNT_PRICE_PERCENT' => $row['DISCOUNT_PRICE_PERCENT'],
		'DISCOUNT_PRICE_PERCENT_FORMATED' => $row['DISCOUNT_PRICE_PERCENT_FORMATED'],
		'SHOW_DISCOUNT_PRICE' => (float)$row['DISCOUNT_PRICE'] > 0,
		'PRICE' => $row['PRICE'],
		'PRICE_FORMATED' => $row['PRICE_FORMATED'],
		'FULL_PRICE' => $row['FULL_PRICE'],
		'FULL_PRICE_FORMATED' => $row['FULL_PRICE_FORMATED'],
		'DISCOUNT_PRICE' => $row['DISCOUNT_PRICE'],
		'DISCOUNT_PRICE_FORMATED' => $row['DISCOUNT_PRICE_FORMATED'],
		'SUM_PRICE' => $row['SUM_VALUE'],
		'SUM_PRICE_FORMATED' => $row['SUM'],
		'SUM_FULL_PRICE' => $row['SUM_FULL_PRICE'],
		'SUM_FULL_PRICE_FORMATED' => $row['SUM_FULL_PRICE_FORMATED'],
		'SUM_DISCOUNT_PRICE' => $row['SUM_DISCOUNT_PRICE'],
		'SUM_DISCOUNT_PRICE_FORMATED' => $row['SUM_DISCOUNT_PRICE_FORMATED'],
		'MEASURE_RATIO' => $row['MEASURE_RATIO'] ?? 1,
		'MEASURE_TEXT' => $row['MEASURE_TEXT'],
		'AVAILABLE_QUANTITY' => $row['AVAILABLE_QUANTITY'] ?? '0',
		'CHECK_MAX_QUANTITY' => $row['CHECK_MAX_QUANTITY'] ?? 'N',
		'MODULE' => $row['MODULE'],
		'PRODUCT_PROVIDER_CLASS' => $row['PRODUCT_PROVIDER_CLASS'],
		'NOT_AVAILABLE' => isset($row['NOT_AVAILABLE']) && $row['NOT_AVAILABLE'] === true,
		'DELAYED' => $row['DELAY'] === 'Y',
		'SKU_BLOCK_LIST' => array(),
		'COLUMN_LIST' => array(),
		'SHOW_LABEL' => false,
		'LABEL_VALUES' => array(),
		'BRAND' => $row[$this->arParams['BRAND_PROPERTY'] . '_VALUE'] ?? '',
	);

	// show price including ratio
	if ($rowData['MEASURE_RATIO'] != 1)
	{
		$price = PriceMaths::roundPrecision($rowData['PRICE'] * $rowData['MEASURE_RATIO']);
		if ($price != $rowData['PRICE'])
		{
			$rowData['PRICE'] = $price;
			$rowData['PRICE_FORMATED'] = CCurrencyLang::CurrencyFormat($price, $rowData['CURRENCY'], true);
		}

		$fullPrice = PriceMaths::roundPrecision($rowData['FULL_PRICE'] * $rowData['MEASURE_RATIO']);
		if ($fullPrice != $rowData['FULL_PRICE'])
		{
			$rowData['FULL_PRICE'] = $fullPrice;
			$rowData['FULL_PRICE_FORMATED'] = CCurrencyLang::CurrencyFormat($fullPrice, $rowData['CURRENCY'], true);
		}

		$discountPrice = PriceMaths::roundPrecision($rowData['DISCOUNT_PRICE'] * $rowData['MEASURE_RATIO']);
		if ($discountPrice != $rowData['DISCOUNT_PRICE'])
		{
			$rowData['DISCOUNT_PRICE'] = $discountPrice;
			$rowData['DISCOUNT_PRICE_FORMATED'] = CCurrencyLang::CurrencyFormat($discountPrice, $rowData['CURRENCY'], true);
		}
	}

	$rowData['SHOW_PRICE_FOR'] = (float)$rowData['QUANTITY'] !== (float)$rowData['MEASURE_RATIO'];

	$hideDetailPicture = false;

	if (!empty($row['DETAIL_PICTURE_SRC_ORIGINAL']))
	{
		$rowData['IMAGE_URL'] = $row['DETAIL_PICTURE_SRC_ORIGINAL'];
	}
	elseif (!empty($row['PREVIEW_PICTURE_SRC_ORIGINAL']))
	{
		$hideDetailPicture = true;
		$rowData['IMAGE_URL'] = $row['PREVIEW_PICTURE_SRC_ORIGINAL'];
	}

	if (!empty($row['SKU_DATA']))
	{
		$propMap = array();

		foreach($row['PROPS'] as $prop)
		{
			$propMap[$prop['CODE']] = !empty($prop['~VALUE']) ? $prop['~VALUE'] : $prop['VALUE'];
		}

		$notSelectable = true;

		foreach ($row['SKU_DATA'] as $skuBlock)
		{
			$skuBlockData = array(
				'ID' => $skuBlock['ID'],
				'CODE' => $skuBlock['CODE'],
				'NAME' => $skuBlock['NAME']
			);

			$isSkuSelected = false;
			$isImageProperty = false;

			if (count($skuBlock['VALUES']) > 1)
			{
				$notSelectable = false;
			}

			foreach ($skuBlock['VALUES'] as $skuItem)
			{
				if ($skuBlock['TYPE'] === 'S' && $skuBlock['USER_TYPE'] === 'directory')
				{
					$valueId = $skuItem['XML_ID'];
				}
				elseif ($skuBlock['TYPE'] === 'E')
				{
					$valueId = $skuItem['ID'];
				}
				else
				{
					$valueId = $skuItem['NAME'];
				}

				$skuValue = array(
					'ID' => $skuItem['ID'],
					'NAME' => $skuItem['NAME'],
					'SORT' => $skuItem['SORT'],
					'PICT' => !empty($skuItem['PICT']) ? $skuItem['PICT']['SRC'] : false,
					'XML_ID' => !empty($skuItem['XML_ID']) ? $skuItem['XML_ID'] : false,
					'VALUE_ID' => $valueId,
					'PROP_ID' => $skuBlock['ID'],
					'PROP_CODE' => $skuBlock['CODE']
				);

				if (
					!empty($propMap[$skuBlockData['CODE']])
					&& ($propMap[$skuBlockData['CODE']] == $skuItem['NAME'] || $propMap[$skuBlockData['CODE']] == $skuItem['XML_ID'])
				)
				{
					$skuValue['SELECTED'] = true;
					$isSkuSelected = true;
				}

				$skuBlockData['SKU_VALUES_LIST'][] = $skuValue;
				$isImageProperty = $isImageProperty || !empty($skuItem['PICT']);
			}

			if (!$isSkuSelected && !empty($skuBlockData['SKU_VALUES_LIST'][0]))
			{
				$skuBlockData['SKU_VALUES_LIST'][0]['SELECTED'] = true;
			}

			$skuBlockData['IS_IMAGE'] = $isImageProperty;

			$rowData['SKU_BLOCK_LIST'][] = $skuBlockData;
		}
	}

	if ($row['NOT_AVAILABLE'])
	{
		foreach ($rowData['SKU_BLOCK_LIST'] as $blockKey => $skuBlock)
		{
			if (!empty($skuBlock['SKU_VALUES_LIST']))
			{
				if ($notSelectable)
				{
					foreach ($skuBlock['SKU_VALUES_LIST'] as $valueKey => $skuValue)
					{
						$rowData['SKU_BLOCK_LIST'][$blockKey]['SKU_VALUES_LIST'][0]['NOT_AVAILABLE_OFFER'] = true;
					}
				}
				elseif (!isset($rowData['SKU_BLOCK_LIST'][$blockKey + 1]))
				{
					foreach ($skuBlock['SKU_VALUES_LIST'] as $valueKey => $skuValue)
					{
						if ($skuValue['SELECTED'])
						{
							$rowData['SKU_BLOCK_LIST'][$blockKey]['SKU_VALUES_LIST'][$valueKey]['NOT_AVAILABLE_OFFER'] = true;
						}
					}
				}
			}
		}
	}

	if (!empty($result['GRID']['HEADERS']) && is_array($result['GRID']['HEADERS']))
	{
		$skipHeaders = [
			'NAME' => true,
			'QUANTITY' => true,
			'PRICE' => true,
			'PREVIEW_PICTURE' => true,
			'SUM' => true,
			'PROPS' => true,
			'DELETE' => true,
			'DELAY' => true,
		];

		foreach ($result['GRID']['HEADERS'] as &$value)
		{
			if (
				empty($value['id'])
				|| isset($skipHeaders[$value['id']])
				|| ($hideDetailPicture && $value['id'] === 'DETAIL_PICTURE'))
			{
				continue;
			}

			if ($value['id'] === 'DETAIL_PICTURE')
			{
				$value['name'] = Loc::getMessage('SBB_DETAIL_PICTURE_NAME');

				if (!empty($row['DETAIL_PICTURE_SRC']))
				{
					$rowData['COLUMN_LIST'][] = array(
						'CODE' => $value['id'],
						'NAME' => $value['name'],
						'VALUE' => array(
							array(
								'IMAGE_SRC' => $row['DETAIL_PICTURE_SRC'],
								'IMAGE_SRC_2X' => $row['DETAIL_PICTURE_SRC_2X'],
								'IMAGE_SRC_ORIGINAL' => $row['DETAIL_PICTURE_SRC_ORIGINAL'],
								'INDEX' => 0
							)
						),
						'IS_IMAGE' => true,
						'HIDE_MOBILE' => !isset($mobileColumns[$value['id']])
					);
				}
			}
			elseif ($value['id'] === 'PREVIEW_TEXT')
			{
				$value['name'] = Loc::getMessage('SBB_PREVIEW_TEXT_NAME');

				if ($row['PREVIEW_TEXT_TYPE'] === 'text' && !empty($row['PREVIEW_TEXT']))
				{
					$rowData['COLUMN_LIST'][] = array(
						'CODE' => $value['id'],
						'NAME' => $value['name'],
						'VALUE' => $row['PREVIEW_TEXT'],
						'IS_TEXT' => true,
						'HIDE_MOBILE' => !isset($mobileColumns[$value['id']])
					);
				}
			}
			elseif ($value['id'] === 'TYPE')
			{
				$value['name'] = Loc::getMessage('SBB_PRICE_TYPE_NAME');

				if (!empty($row['NOTES']))
				{
					$rowData['COLUMN_LIST'][] = array(
						'CODE' => $value['id'],
						'NAME' => $value['name'],
						'VALUE' => $row['~NOTES'] ?? $row['NOTES'],
						'IS_TEXT' => true,
						'HIDE_MOBILE' => !isset($mobileColumns[$value['id']])
					);
				}
			}
			elseif ($value['id'] === 'DISCOUNT')
			{
				$value['name'] = Loc::getMessage('SBB_DISCOUNT_NAME');

				if ($row['DISCOUNT_PRICE_PERCENT'] > 0 && !empty($row['DISCOUNT_PRICE_PERCENT_FORMATED']))
				{
					$rowData['COLUMN_LIST'][] = array(
						'CODE' => $value['id'],
						'NAME' => $value['name'],
						'VALUE' => $row['DISCOUNT_PRICE_PERCENT_FORMATED'],
						'IS_TEXT' => true,
						'HIDE_MOBILE' => !isset($mobileColumns[$value['id']])
					);
				}
			}
			elseif ($value['id'] === 'WEIGHT')
			{
				$value['name'] = Loc::getMessage('SBB_WEIGHT_NAME');

				if (!empty($row['WEIGHT_FORMATED']))
				{
					$rowData['COLUMN_LIST'][] = array(
						'CODE' => $value['id'],
						'NAME' => $value['name'],
						'VALUE' => $row['WEIGHT_FORMATED'],
						'IS_TEXT' => true,
						'HIDE_MOBILE' => !isset($mobileColumns[$value['id']])
					);
				}
			}
			elseif (!empty($row[$value['id'].'_SRC']))
			{
				$i = 0;

				foreach ($row[$value['id'].'_SRC'] as &$image)
				{
					$image['INDEX'] = $i++;
				}

				$rowData['COLUMN_LIST'][] = array(
					'CODE' => $value['id'],
					'NAME' => $value['name'],
					'VALUE' => $row[$value['id'].'_SRC'],
					'IS_IMAGE' => true,
					'HIDE_MOBILE' => !isset($mobileColumns[$value['id']])
				);
			}
			elseif (!empty($row[$value['id'].'_DISPLAY']))
			{
				$rowData['COLUMN_LIST'][] = array(
					'CODE' => $value['id'],
					'NAME' => $value['name'],
					'VALUE' => $row[$value['id'].'_DISPLAY'],
					'IS_TEXT' => true,
					'HIDE_MOBILE' => !isset($mobileColumns[$value['id']])
				);
			}
			elseif (!empty($row[$value['id'].'_LINK']))
			{
				$linkValues = array();

				foreach ($row[$value['id'].'_LINK'] as $index => $link)
				{
					$linkValues[] = array(
						'LINK' => $link,
						'IS_LAST' => !isset($row[$value['id'].'_LINK'][$index + 1])
					);
				}

				$rowData['COLUMN_LIST'][] = array(
					'CODE' => $value['id'],
					'NAME' => $value['name'],
					'VALUE' => $linkValues,
					'IS_LINK' => true,
					'HIDE_MOBILE' => !isset($mobileColumns[$value['id']])
				);
			}
			elseif (!empty($row[$value['id']]))
			{
				$rawValue = $row['~' . $value['id']] ?? $row[$value['id']];
				$isHtml = !empty($row[$value['id'].'_HTML']);

				$rowData['COLUMN_LIST'][] = array(
					'CODE' => $value['id'],
					'NAME' => $value['name'],
					'VALUE' => $rawValue,
					'IS_TEXT' => !$isHtml,
					'IS_HTML' => $isHtml,
					'HIDE_MOBILE' => !isset($mobileColumns[$value['id']])
				);
			}
		}

		unset($value);
	}

	if (!empty($row['LABEL_ARRAY_VALUE']))
	{
		$labels = array();

		foreach ($row['LABEL_ARRAY_VALUE'] as $code => $value)
		{
			$labels[] = array(
				'NAME' => $value,
				'HIDE_MOBILE' => !isset($this->arParams['LABEL_PROP_MOBILE'][$code])
			);
		}

		$rowData['SHOW_LABEL'] = true;
		$rowData['LABEL_VALUES'] = $labels;
	}

	$result['BASKET_ITEM_RENDER_DATA'][] = $rowData;
}

$totalData = array(
	'DISABLE_CHECKOUT' => (int)$result['ORDERABLE_BASKET_ITEMS_COUNT'] === 0,
	'PRICE' => $result['allSum'],
	'PRICE_FORMATED' => $result['allSum_FORMATED'],
	'PRICE_WITHOUT_DISCOUNT_FORMATED' => $result['PRICE_WITHOUT_DISCOUNT'],
	'CURRENCY' => $result['CURRENCY']
);

if ($result['DISCOUNT_PRICE_ALL'] > 0)
{
	$totalData['DISCOUNT_PRICE_FORMATED'] = $result['DISCOUNT_PRICE_FORMATED'];
}

#Бонусы Mindbox
if($_SESSION['PAY_BONUSES'] > 0){
    $totalData['PAY_BONUSES'] = $_SESSION['PAY_BONUSES'];
}

if($_SESSION["UF_PL_MEMBER"] == "Y" && $_SESSION['PAY_BONUSES'] <= 0 && $_SESSION["PROMO_CODE_AMOUNT"] <= 0){
    $level = $_SESSION["MB_USER_LEVEL"];
    
    $ex = 1;
    if(!empty($_SESSION["BONUSES_EX"]))
        $ex = $_SESSION["BONUSES_EX"];
    
    if($level == 1){
        $xs = 5*$ex;    
    }elseif($level == 2){
        $xs = 10*$ex;      
    }elseif($level == 3){
        $xs = 15*$ex;     
    }
    
    $totalData['GET_BONUSES'] = $_SESSION["TOTAL_EARNED_BONUSES"];
        
    global $USER;
    if ($USER->IsAuthorized())
    {    
        if(!$_SESSION["LEVEL_SET"]){
            #LocalRedirect("/cart/?setpoints=y");
        }
    }
}
#!Бонусы Mindbox

if ($result['allWeight'] > 0)
{
	$totalData['WEIGHT_FORMATED'] = $result['allWeight_FORMATED'];
}

if ($this->priceVatShowValue === 'Y')
{
	$totalData['SHOW_VAT'] = true;
	$totalData['VAT_SUM_FORMATED'] = $result['allVATSum_FORMATED'];
	$totalData['SUM_WITHOUT_VAT_FORMATED'] = $result['allSum_wVAT_FORMATED'];
}

/*if ($this->hideCoupon !== 'Y' && !empty($result['COUPON_LIST']))
{
	$totalData['COUPON_LIST'] = $result['COUPON_LIST'];

	foreach ($totalData['COUPON_LIST'] as &$coupon)
	{
		if ($coupon['JS_STATUS'] === 'ENTERED')
		{
			$coupon['CLASS'] = 'danger';
		}
		elseif ($coupon['JS_STATUS'] === 'APPLYED')
		{
			$coupon['CLASS'] = 'muted';
		}
		else
		{
			$coupon['CLASS'] = 'danger';
		}
	}
}*/
#Сертификаты
if ($this->hideCoupon !== 'Y')
{
    global $USER;
    if (!$USER->IsAuthorized())
    {
        $totalData['COUPON_USER_NEED_AUTH'] = true;

        $result['COUPON_LIST'] = array(); // не показывать ???
        $totalData['COUPON_LIST'] = $result['COUPON_LIST'];
    }
    else
    {
        $couponManager = CouponManager::getInstance();

        // получить код текущего "custom" купона, хранится в $_SESSION['CUSTOM_COUPON']
        $couponCode = $couponManager->getPromocode();

        if (!empty($result['COUPON_LIST']))
        {
            /*
                форма ввода купона на шаблоне не модифицировалась,
                поэтому "custom" купон попадёт в список "стандартных" - $result['COUPON_LIST']
            */
            $totalData['COUPON_LIST'] = $result['COUPON_LIST'];
            $totalData['COUPON_BLOCK_IS_OPEN'] = true;

            // полагаем, что купон всегда один !!!
            foreach ($totalData['COUPON_LIST'] as &$coupon)
            {
                $couponCode = $coupon['COUPON'];
                
                $not_cert = true;
                $certificatesManager = \kb\Container::get('CertificatesManager');
                if(!$certificate = $certificatesManager->findByCode($couponCode)){
                    $not_cert = false;
                }
                
                $couponManager->rememberPromocode($couponCode);

                $couponManager->initCoupon($couponCode);
                $obCoupon = $couponManager->getCoupon();

                if (!empty($obCoupon) && $not_cert)
                {
                    if ($obCoupon->hasCoupon() && !$obCoupon->hasErrors())
                    {
                        // ограничения по корзине
                        $basketPrice = $basketSumWithoutSaleItems = 0;

                        $basketItems = $this->basketItems;
                        foreach ($basketItems as $arItem)
                        {
                            if ($arItem['CAN_BUY'] != 'Y')
                                continue;

                            $basketPrice += $arItem['SUM_VALUE'];

                            if ($arItem['SUM_DISCOUNT_PRICE'] <= 0)
                            {
                                $basketSumWithoutSaleItems += $arItem['SUM_VALUE'];
                            }
                        }

                        $obCoupon->calcByBasketMb(/*$basketPrice, $basketSumWithoutSaleItems*/);
                    }

                    if ($obCoupon->hasErrors())
                    {
                        $coupon['JS_STATUS'] = 'BAD';
                        $coupon['JS_CHECK_CODE'] = $obCoupon->getError();
                        $coupon['JS_CHECK_CODE'] = str_ireplace("Промокод","Сертификат", $coupon['JS_CHECK_CODE']);
                        $coupon['CLASS'] = ' _has-error';
                    }
                    else
                    {
                        $coupon['JS_STATUS'] = 'APPLYED';
                        $coupon['JS_CHECK_CODE'] =  str_ireplace("Промокод","Сертификат", $obCoupon->getStatusText());

                        $saleValue = $obCoupon->getSaleValue();
                        
                        if ($saleValue > 0)
                        {
                            $totalDiscount = $saleValue + $result['DISCOUNT_PRICE_ALL'];
                            
                            $totalData['DISCOUNT_PRICE_FORMATED'] = CCurrencyLang::CurrencyFormat(
                                $totalDiscount,
                                $result['CURRENCY'],
                                true
                            );

                            $totalPrice = $result['allSum'] - $saleValue;
                            if($totalPrice < 0){
                                $totalPrice = 0;
                            }
                            
                            $totalData['PRICE_FORMATED'] = CCurrencyLang::CurrencyFormat(
                                $totalPrice,
                                $result['CURRENCY'],
                                true
                            );
                        }
                    }
                }
                else
                {
                    $coupon['JS_STATUS'] = 'BAD';
                    $coupon['JS_CHECK_CODE'] = 'Сертификат не найден или не активен';
                    $coupon['CLASS'] = ' _has-errorr alert alert-danger';
                }
            }
        
            $totalData['COUPON_BLOCK_SHOW_FIELD'] = true;
        }
        else
        {
            $totalData['COUPON_BLOCK_SHOW_FIELD'] = true;

            if (!empty($couponCode))
            {
                $couponManager->forgetPromocode();
            }
        }
    }
}

#Расчет бесплатной доставки
$limit = 5000;
$summ = (int)$result['allSum'];

if($summ >= $limit){
    $totalData['FREE_DELIVERY_PERCENTS'] = 100;    
    $totalData['FREE_DELIVERY_TEXT'] = "Доступна бесплатная доставка по РФ";    
}else{
    $diff = $limit-$summ;
    $percent = round($summ/50);
    
    $totalData['FREE_DELIVERY_PERCENTS'] = $percent;
    $totalData['FREE_DELIVERY_TEXT'] = "Осталось ".$diff." ₽ до бесплатной доставки по РФ";
}

$result['TOTAL_RENDER_DATA'] = $totalData;