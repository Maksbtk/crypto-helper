<?
$deepSectionCodes = [];
$deepCollectionSectionCodes = [];
foreach ($arResult as &$menuItem)
{
    $trimmedUrl = trim($menuItem['LINK'], '/');
    $partsUrl = explode('/', $trimmedUrl);
    $sectionCode = end($partsUrl);

    if ($menuItem['PARAMS']['deep'] == 'y')
    {
        $deepSectionCodes[] = $sectionCode;

        if ($menuItem['PARAMS']['collections']) {
            $collectionAr = explode(",", $menuItem['PARAMS']['collections']);
            $menuItem['PARAMS']['collectionsAr'] = $collectionAr;
            $deepCollectionSectionCodes = array_merge($deepCollectionSectionCodes, $collectionAr);
            $deepCollectionSectionCodes = array_unique($deepCollectionSectionCodes);
        }
    }

    $menuItem['SECTION_CODE'] = $sectionCode;

}
unset($menuItem);

//формируем трехуровневое меню для нужных символьных кодов
$deepTree = [];
if ($deepSectionCodes)
    $deepTree = getMenuTree($deepSectionCodes);

$deepCollectionTree = [];
if ($deepCollectionSectionCodes)
    $deepCollectionTree = getMenuCollectionTree($deepCollectionSectionCodes);

foreach ($arResult as &$menuItem)
{
    if ($deepTree[$menuItem['SECTION_CODE']]['CHILDREN_LVL_2'])
    {
        $menuItem['IS_PARENT'] = true;
        $menuItem['CHILDREN_LVL_2'] = $deepTree[$menuItem['SECTION_CODE']]['CHILDREN_LVL_2'];
    }

    if ($deepTree[$menuItem['SECTION_CODE']]['ITEM_PICTURE'])
        $menuItem['ITEM_PICTURE'] = $deepTree[$menuItem['SECTION_CODE']]['ITEM_PICTURE'];

    //$menuItem['ITEM_PICTURE'] = \CFile::ResizeImageGet($deepTree[$menuItem['SECTION_CODE']]['PICTURE'], array('width' => 450, 'height' => 675), BX_RESIZE_IMAGE_PROPORTIONAL, true)['src'];

    if ($menuItem['PARAMS']['deep'] == 'y' && $menuItem['PARAMS']['collectionsAr'])
    {
        foreach ($menuItem['PARAMS']['collectionsAr'] as $collectionCode)
        {
            $menuItem['COLLECTIONS'][] =  $deepCollectionTree[$collectionCode];
        }
    }
}
unset($menuItem);

/*echo '<pre>'; var_dump('$arResult'); echo '</pre>';
echo '<pre>'; var_dump($arResult); echo '</pre>';*/

function getMenuCollectionTree ($sectionCodeAr, $cacheTime = 1800) {
    $deepCollectionTree = [];
       $cacheID = md5('menuDeepCollectionTree|' . implode($sectionCodeAr));
       $cache = \Bitrix\Main\Data\Cache::createInstance();
       if ($cache->initCache($cacheTime, $cacheID))
       {
           $deepCollectionTree = $cache->getVars();
       }
       elseif ($cache->startDataCache())
       {
            $selectAr = [/*'LEFT_MARGIN', 'RIGHT_MARGIN',*/ 'CODE', 'DEPTH_LEVEL', 'NAME', 'IBLOCK_ID', 'SECTION_PAGE_URL', 'SORT'];
            $rsSection = \CIBlockSection::GetList(
                [],
                ['IBLOCK_ID' => 2, 'CODE' => $sectionCodeAr, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y',],
                false,
                $selectAr
            );

            while ($arSection = $rsSection->GetNext())
            {
                $deepCollectionTree[$arSection['CODE']] = $arSection;
            }
            $cache->endDataCache($deepCollectionTree);
        }
    return $deepCollectionTree;
}

function getMenuTree ($sectionCodeAr, $cacheTime = 1800) {
    $deepTree = [];
    $cacheID = md5('menuDeepTree|' . implode($sectionCodeAr));
    $cache = \Bitrix\Main\Data\Cache::createInstance();
    if ($cache->initCache($cacheTime, $cacheID))
    {
        $deepTree = $cache->getVars();
    }
    elseif ($cache->startDataCache())
    {
        $selectAr = ['LEFT_MARGIN', 'RIGHT_MARGIN', 'CODE', 'DEPTH_LEVEL', 'NAME', 'IBLOCK_ID', 'SECTION_PAGE_URL', 'ACTIVE', 'ID'];
        $rsParentSection = \CIBlockSection::GetList(
            ['sort' => 'asc'],
            ['IBLOCK_ID' => 2, 'CODE' => $sectionCodeAr, 'ACTIVE' => 'Y', /*'GLOBAL_ACTIVE' => 'Y',*/],
            false,
            array_merge($selectAr, ['PICTURE'])
        );

        //todo: фильтр у гетлиста не работает по свойству ACTIVE
        while ($arParentSection = $rsParentSection->GetNext())
        {
            if (intval($arParentSection['DEPTH_LEVEL']) == 1 && $arParentSection['ACTIVE'] == 'Y')
            {
                //echo $arParentSection['NAME'] . '<br>';
                $rsSectLVL2 = \CIBlockSection::GetList(
                    ['left_margin' => 'asc'],
                    ['IBLOCK_ID' => $arParentSection['IBLOCK_ID'], '>LEFT_MARGIN' => $arParentSection['LEFT_MARGIN'], '<RIGHT_MARGIN' => $arParentSection['RIGHT_MARGIN'], '>DEPTH_LEVEL' => $arParentSection['DEPTH_LEVEL']],
                    false,
                    $selectAr
                );
                while ($arSectLVL2 = $rsSectLVL2->GetNext())
                {
                    if (intval($arSectLVL2['DEPTH_LEVEL']) == 2 && $arSectLVL2['ACTIVE'] == 'Y')
                    {
                        //echo '&#8195;' . $arSectLVL2['NAME']  . ' ACTIVE - ' .  $arSectLVL2['ACTIVE'] . ' ID -' .  $arSectLVL2['ID'] . '<br>';
                        $rsSectLVL3 = \CIBlockSection::GetList(
                            ['left_margin' => 'asc'],
                            ['IBLOCK_ID' => $arSectLVL2['IBLOCK_ID'], '>LEFT_MARGIN' => $arSectLVL2['LEFT_MARGIN'], '<RIGHT_MARGIN' => $arSectLVL2['RIGHT_MARGIN'], '>DEPTH_LEVEL' => $arSectLVL2['DEPTH_LEVEL']],
                            false,
                            $selectAr
                        );
                        while ($arSectLVL3 = $rsSectLVL3->GetNext())
                        {
                            if (intval($arSectLVL3['DEPTH_LEVEL']) == 3 && $arSectLVL3['ACTIVE'] == 'Y')
                            {
                                //echo '&#8195;&#8195;' . $arSectLVL3['NAME'] . ' ACTIVE - ' .  $arSectLVL3['ACTIVE'] . ' ID -' .  $arSectLVL3['ID'] . '<br>';
                                $arSectLVL2['CHILDREN_LVL_3'][$arSectLVL3['CODE']] = $arSectLVL3;
                            }
                        }
                        $arParentSection['CHILDREN_LVL_2'][$arSectLVL2['CODE']] = $arSectLVL2;
                    }

                }

                if ($arParentSection['PICTURE'])
                    $arParentSection['ITEM_PICTURE'] = \CFile::ResizeImageGet($arParentSection['PICTURE'], array('width' => 450, 'height' => 675), BX_RESIZE_IMAGE_PROPORTIONAL, true)['src'];

                $deepTree[$arParentSection['CODE']] = $arParentSection;
            }
        }
        $cache->endDataCache($deepTree);
    }
    return $deepTree;
}
?>