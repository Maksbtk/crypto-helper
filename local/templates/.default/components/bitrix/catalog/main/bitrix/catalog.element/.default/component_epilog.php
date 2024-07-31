<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;

/**
 * @var array $templateData
 * @var array $arParams
 * @var string $templateFolder
 * @global CMain $APPLICATION
 */

global $APPLICATION;
global $USER;

Asset::getInstance()->addString('<meta property="og:type" content="product" />');
Asset::getInstance()->addString('<meta property="og:site_name" content="Нижнее белье и одежда - Интернет-магазин belle you" />');

if(isset($arResult['PREVIEW_PICTURE']) && !empty($arResult['PREVIEW_PICTURE'])){
    $resize_x2 = CFile::ResizeImageGet($arResult['PREVIEW_PICTURE']['ID'], ["width" => 715, "height" => 1075], BX_RESIZE_IMAGE_EXACT, true);
    
    Asset::getInstance()->addString('<meta property="og:image" content="'.$resize_x2['src'].'" />');
}

if(isset($arResult['CANONICAL_PAGE_URL']) && !empty($arResult['CANONICAL_PAGE_URL']))
    Asset::getInstance()->addString('<meta property="og:url" content="'.$arResult['CANONICAL_PAGE_URL'].'" />');

if(!empty($arResult['IPROPERTY_VALUES']['ELEMENT_META_TITLE']))
    Asset::getInstance()->addString('<meta property="og:title" content="'.$arResult['IPROPERTY_VALUES']['ELEMENT_META_TITLE'].' Интернет-магазин belle you" />');
else
    Asset::getInstance()->addString('<meta property="og:title" content="'.$arResult['NAME'].' Интернет-магазин belle you" />');

if(!empty($arResult['IPROPERTY_VALUES']['ELEMENT_META_DESCRIPTION'])) 
    Asset::getInstance()->addString('<meta property="og:description" content="' . $arResult['IPROPERTY_VALUES']['ELEMENT_META_DESCRIPTION'] . '" />');

if (isset($templateData['TEMPLATE_THEME']))
{
	$APPLICATION->SetAdditionalCSS($templateFolder.'/themes/'.$templateData['TEMPLATE_THEME'].'/style.css');
	$APPLICATION->SetAdditionalCSS('/bitrix/css/main/themes/'.$templateData['TEMPLATE_THEME'].'/style.css', true);
}

if (!empty($templateData['TEMPLATE_LIBRARY']))
{
	$loadCurrency = false;

	if (!empty($templateData['CURRENCIES']))
	{
		$loadCurrency = Loader::includeModule('currency');
	}

	CJSCore::Init($templateData['TEMPLATE_LIBRARY']);
	if ($loadCurrency)
	{
		?>
		<script>
			BX.Currency.setCurrencies(<?=$templateData['CURRENCIES']?>);
		</script>
		<?php
	}
}

if (isset($templateData['JS_OBJ']))
{
	?>
	<script>
		BX.ready(BX.defer(function(){
			if (!!window.<?=$templateData['JS_OBJ']?>)
			{
				window.<?=$templateData['JS_OBJ']?>.allowViewedCount(true);
			}
		}));
	</script>
	<?php
	// check compared state
	if ($arParams['DISPLAY_COMPARE'])
	{
		$compared = false;
		$comparedIds = array();
		$item = $templateData['ITEM'];

		if (!empty($_SESSION[$arParams['COMPARE_NAME']][$item['IBLOCK_ID']]))
		{
			if (!empty($item['JS_OFFERS']) && is_array($item['JS_OFFERS']))
			{
				foreach ($item['JS_OFFERS'] as $key => $offer)
				{
					if (array_key_exists($offer['ID'], $_SESSION[$arParams['COMPARE_NAME']][$item['IBLOCK_ID']]['ITEMS']))
					{
						if ($key == $item['OFFERS_SELECTED'])
						{
							$compared = true;
						}

						$comparedIds[] = $offer['ID'];
					}
				}
			}
			elseif (array_key_exists($item['ID'], $_SESSION[$arParams['COMPARE_NAME']][$item['IBLOCK_ID']]['ITEMS']))
			{
				$compared = true;
			}
		}

		if ($templateData['JS_OBJ'])
		{
			?>
			<script>
				BX.ready(BX.defer(function(){
					if (!!window.<?=$templateData['JS_OBJ']?>)
					{
						window.<?=$templateData['JS_OBJ']?>.setCompared('<?=$compared?>');

						<?php
						if (!empty($comparedIds)):
						?>
						window.<?=$templateData['JS_OBJ']?>.setCompareInfo(<?=CUtil::PhpToJSObject($comparedIds, false, true)?>);
						<?php
						endif;
						?>
					}
				}));
			</script>
			<?php
		}
	}

	// select target offer
	$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();
	$offerNum = false;
	$offerId = (int)$this->request->get('OFFER_ID');
	$offerCode = $this->request->get('OFFER_CODE');

	if ($offerId > 0 && !empty($templateData['OFFER_IDS']) && is_array($templateData['OFFER_IDS']))
	{
		$offerNum = array_search($offerId, $templateData['OFFER_IDS']);
	}
	elseif (!empty($offerCode) && !empty($templateData['OFFER_CODES']) && is_array($templateData['OFFER_CODES']))
	{
		$offerNum = array_search($offerCode, $templateData['OFFER_CODES']);
	}

	if (!empty($offerNum))
	{
		?>
		<script>
			BX.ready(function(){
				if (!!window.<?=$templateData['JS_OBJ']?>)
				{
					window.<?=$templateData['JS_OBJ']?>.setOffer(<?=$offerNum?>);
				}
			});
		</script>
		<?php
	}
}

?>

<?//проверяем находится ли элемент в избранном у пользователя
if($USER->IsAuthorized()):?>

    <? $ufFavorites = getUfFavorites() ?? [];
    if (in_array($arResult['ID'], $ufFavorites)):?>
        <script>
            if($('button.js-check-wishlist-button'))
                $('button.js-check-wishlist-button').addClass('_added');
        </script>
    <?endif;?>

<?endif;?>