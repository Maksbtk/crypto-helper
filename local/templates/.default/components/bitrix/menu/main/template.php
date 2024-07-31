<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?php
if (!$arResult)
    die();


$mainMenuAr = [];
$separateMenuAr = [];
foreach ($arResult as $menuItem)
{
    if($arParams["SALE"] !== true && $menuItem['TEXT'] == "ЛЕТНИЙ SALE") continue;
    
    if ($menuItem['PARAMS']['separate'] == 'y')
        $separateMenuAr[] = $menuItem;
    else
        $mainMenuAr[] = $menuItem;
}
?>

<div class="belleyou-sidebar-menu__content">
    <?if ($mainMenuAr):?>
        <ul class="belleyou-sidebar-list">
            <?foreach ($mainMenuAr as $menuItem):?>
                <?if($menuItem['PARAMS']['deep'] == 'y'):?>
                <li class="belleyou-sidebar-item belleyou-sidebar-item__with-panel<?if($menuItem['PARAMS']['marked'] == 'y'):?> belleyou-sidebar-item__marked<?endif;?>">
                    <a class="belleyou-sidebar-item__link button-submenu-open" href="javascript:void(0)"><?=$menuItem['TEXT']?></a>
                    <div class="belleyou-sidebar-panel">
                        <header class="belleyou-sidebar-panel__header-mobile">
                            <button class="button-back-panel__mobile" data-back-panel></button>
                            <div class="h4 sidebar-panel__title"><?=$menuItem['TEXT']?></div>
                            <button class="button-close-sidebar__mobile" data-close-sidebar></button>
                        </header>
                        <div class="belleyou-sidebar-panel__wrapper">
                            <ul class="sidebar-panel-list">
                                <li class="sidebar-panel-item sidebar-panel-item--name"><div class="h4">Категории</div></li>
                                <li class="sidebar-panel-item sidebar-panel-item--all"><a href="<?=$menuItem['LINK']?>">Смотреть все</a></li>
                                <?if($menuItem['CHILDREN_LVL_2']):?>
                                    <? foreach ($menuItem['CHILDREN_LVL_2'] as $menuItemLvl2):?>

                                        <?if($menuItemLvl2['CHILDREN_LVL_3']):?>
                                            <li class="sidebar-panel-item sidebar-panel-item--with-list">
                                                <a href="<?=$menuItemLvl2['SECTION_PAGE_URL']?>"><?=$menuItemLvl2['NAME']?></a>
                                                <ul class="sidebar-panel-innerlist">
                                                    <?foreach ($menuItemLvl2['CHILDREN_LVL_3'] as $menuItemLvl3):?>
                                                        <li class="sidebar-panel-item"><a href="<?=$menuItemLvl3['SECTION_PAGE_URL']?>"><?=$menuItemLvl3['NAME']?></a></li>
                                                    <?endforeach;?>
                                                </ul>
                                            </li>
                                        <?else:?>
                                            <li class="sidebar-panel-item"><a href="<?=$menuItemLvl2['SECTION_PAGE_URL']?>"><?=$menuItemLvl2['NAME']?></a></li>
                                        <?endif;?>

                                    <?endforeach;?>
                                <?endif;?>
                            </ul>

                            <?if ($menuItem['COLLECTIONS']):?>
                                <ul class="sidebar-panel-list">
                                    <li class="sidebar-panel-item sidebar-panel-item--name"><div class="h4">капсулы</div></li>
                                    <?foreach ($menuItem['COLLECTIONS'] as $collection):?>
                                        <li class="sidebar-panel-item"><a href="<?=$collection['SECTION_PAGE_URL']?>"><?=$collection['NAME']?></a></li>
                                    <?endforeach;?>
                                </ul>
                            <?endif;?>

                            <?if ($menuItem['ITEM_PICTURE']):?>
                                <div class="sidebar-panel-banner">
                                    <img src="<?=$menuItem['ITEM_PICTURE']?>" alt="<?=$menuItem['TEXT']?>">
                                </div>
                            <?endif;?>

                        </div>
                    </div>
                </li>
                <?else:?>
                <li class="belleyou-sidebar-item <?if($menuItem['PARAMS']['marked'] == 'y'):?> belleyou-sidebar-item__marked<?endif;?>"><a class="belleyou-sidebar-item__link" href="<?=$menuItem['LINK']?>"><?=$menuItem['TEXT']?></a></li>
                <?endif;?>

            <?endforeach;?>

        </ul>
    <?endif;?>
    <?if ($separateMenuAr):?>
        <ul class="belleyou-sidebar-list">
            <?foreach ($separateMenuAr as $menuItem):?>
                <li class="belleyou-sidebar-item<?if($menuItem['PARAMS']['marked'] == 'y'):?> belleyou-sidebar-item__marked<?endif;?>">
                    <a class="belleyou-sidebar-item__link" href="<?=$menuItem['LINK']?>">
                        <?=$menuItem['TEXT']?>
                    </a>
                </li>
            <?endforeach;?>
        </ul>
    <?endif;?>
</div>


