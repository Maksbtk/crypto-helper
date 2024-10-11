<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$leftMenuAr = [];
$rightMenuAr = [];
foreach ($arResult as $menuItem)
{
    if ($menuItem['PARAMS']['SIDE'] == 'LEFT')
        $leftMenuAr[] = $menuItem;
    else if ($menuItem['PARAMS']['SIDE'] == 'RIGHT')
        $rightMenuAr[] = $menuItem;
}
?>

<ul class="footer-navigation _short">
    <li class="footer-nav__title">Меню</li>
    <? foreach ($leftMenuAr as $menuItem):?>
        <li class="footer-nav__item "><a class="white-color-font" href="<?=$menuItem['LINK']?>"><?=$menuItem['TEXT']?></a></li>
    <?endforeach;?>
</ul>
<ul class="footer-navigation _short">
    <li class="footer-nav__title">Помощь</li>
    <? foreach ($rightMenuAr as $menuItem):?>
        <li class="footer-nav__item"><a class="white-color-font" href="<?=$menuItem['LINK']?>"><?=$menuItem['TEXT']?></a></li>
    <?endforeach;?>
</ul>

