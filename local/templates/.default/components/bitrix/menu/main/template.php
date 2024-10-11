<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?php
if (!$arResult)
    die();

?>

<div class="maksv-sidebar-menu__content">
    <?if ($arResult):?>
        <ul class="maksv-sidebar-list">
            <?foreach ($arResult as $menuItem):?>
                <?if($menuItem['PARAMS']['deep'] == 'y'):?>
                    <li class="maksv-sidebar-item maksv-sidebar-item__with-panel<?if($menuItem['PARAMS']['marked'] == 'y'):?> maksv-sidebar-item__marked<?endif;?>">
                        <a class="maksv-sidebar-item__link button-submenu-open" href="javascript:void(0)"><?=$menuItem['TEXT']?></a>
                        <div class="maksv-sidebar-panel">
                            <header class="maksv-sidebar-panel__header-mobile">
                                <button class="button-back-panel__mobile" data-back-panel></button>
                                <div class="h4 sidebar-panel__title"><?=$menuItem['TEXT']?></div>
                                <button class="button-close-sidebar__mobile" data-close-sidebar></button>
                            </header>
                            <div class="maksv-sidebar-panel__wrapper">
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
                    <li class="maksv-sidebar-item <?if($menuItem['PARAMS']['marked'] == 'y'):?> maksv-sidebar-item__marked<?endif;?>"><a class="maksv-sidebar-item__link" href="<?=$menuItem['LINK']?>"><?=$menuItem['TEXT']?></a></li>
                <?endif;?>
                <?if ($menuItem['PARAMS']['offset'] == 'y'):?>
                    <li class="maksv-sidebar-item__empty"></li>
                <?endif;?>
            <?endforeach;?>
        </ul>
    <?endif;?>
</div>


