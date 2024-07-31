<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
?>
<?php //echo '<pre>'; var_dump($arResult['PROPERTIES']); echo '</pre>';
?>

<div class="footer-brandbox">
    <div class="footer-brandbox__inner">
        <? if ($arResult['PROPERTIES']['SLOGAN']['VALUE']):?>
            <div class="footer-brandbox__slogan"><?=$arResult['PROPERTIES']['SLOGAN']['VALUE']?></div>
        <?endif;?>
        <svg xmlns="http://www.w3.org/2000/svg" width="185" height="50" fill="none" viewBox="0 0 132 36"><path fill="#1F2020" d="M42.228-2.73h-2.984v29.777h2.984V-2.73zM49.705-2.73H46.72v29.777h2.985V-2.73zM128.177 10.981v9.887c0 1.04-.406 2.039-1.13 2.779a3.892 3.892 0 0 1-2.735 1.175 3.896 3.896 0 0 1-2.768-1.158 3.975 3.975 0 0 1-1.146-2.796V10.98h-2.935v9.887c0 1.835.721 3.595 2.006 4.893a6.811 6.811 0 0 0 4.843 2.027 6.813 6.813 0 0 0 4.811-2.044 6.958 6.958 0 0 0 1.989-4.876V10.98h-2.935zM8.356 10.696a8.304 8.304 0 0 0-5.42 1.977V-2.769H0v29.778h2.935v-1.335a8.303 8.303 0 0 0 5.42 1.977 8.35 8.35 0 0 0 3.223-.655 8.42 8.42 0 0 0 2.73-1.852 8.52 8.52 0 0 0 1.819-2.766 8.59 8.59 0 0 0 .634-3.259 8.547 8.547 0 0 0-2.489-5.957 8.37 8.37 0 0 0-5.916-2.466zm0 14.059a5.45 5.45 0 0 1-3.865-1.622 5.565 5.565 0 0 1-1.605-3.905 5.566 5.566 0 0 1 1.605-3.905 5.45 5.45 0 0 1 3.865-1.621 5.45 5.45 0 0 1 3.864 1.621 5.566 5.566 0 0 1 1.605 3.905 5.576 5.576 0 0 1-1.607 3.902 5.461 5.461 0 0 1-3.862 1.625zM106.52 10.734a8.38 8.38 0 0 0-5.937 2.493 8.557 8.557 0 0 0-2.468 6 8.546 8.546 0 0 0 2.465 6.002 8.371 8.371 0 0 0 5.94 2.49 8.37 8.37 0 0 0 5.94-2.49 8.548 8.548 0 0 0 2.465-6.002 8.548 8.548 0 0 0-2.465-6.003 8.37 8.37 0 0 0-5.94-2.49zm0 14.02a5.449 5.449 0 0 1-3.864-1.623 5.56 5.56 0 0 1-1.605-3.904 5.56 5.56 0 0 1 1.605-3.905 5.449 5.449 0 0 1 3.864-1.622c.718 0 1.43.143 2.093.42a5.466 5.466 0 0 1 1.775 1.199 5.575 5.575 0 0 1 1.185 6.023 5.545 5.545 0 0 1-1.185 1.792 5.465 5.465 0 0 1-1.775 1.198 5.418 5.418 0 0 1-2.093.421zM61.053 10.695a8.37 8.37 0 0 0-5.94 2.49 8.547 8.547 0 0 0-2.465 6.002 8.548 8.548 0 0 0 2.465 6.002 8.37 8.37 0 0 0 5.94 2.49 8.354 8.354 0 0 0 4.113-1.095 8.466 8.466 0 0 0 3.06-2.987l-2.858-.99a5.493 5.493 0 0 1-1.913 1.572 5.432 5.432 0 0 1-2.402.564 5.454 5.454 0 0 1-3.394-1.202 5.554 5.554 0 0 1-1.929-3.069h13.698c.036-.658.036-1.318 0-1.977a8.517 8.517 0 0 0-2.678-5.555 8.346 8.346 0 0 0-5.697-2.245zm-5.206 6.812a5.535 5.535 0 0 1 1.984-2.78 5.44 5.44 0 0 1 3.222-1.066 5.48 5.48 0 0 1 3.2 1.057 5.571 5.571 0 0 1 1.996 2.739l-10.401.05zM27.847 10.695a8.372 8.372 0 0 0-5.94 2.49 8.548 8.548 0 0 0-2.465 6.002 8.548 8.548 0 0 0 2.465 6.002 8.371 8.371 0 0 0 5.94 2.49 8.356 8.356 0 0 0 4.113-1.095 8.463 8.463 0 0 0 3.06-2.987l-2.858-.99a5.49 5.49 0 0 1-1.913 1.572 5.432 5.432 0 0 1-2.402.564 5.435 5.435 0 0 1-3.392-1.201 5.534 5.534 0 0 1-1.92-3.07h13.697c.041-.658.041-1.318 0-1.977a8.527 8.527 0 0 0-2.684-5.556 8.358 8.358 0 0 0-5.7-2.244zm-5.195 6.782a5.534 5.534 0 0 1 1.983-2.779 5.442 5.442 0 0 1 3.222-1.067 5.44 5.44 0 0 1 3.205 1.05 5.534 5.534 0 0 1 1.99 2.746l-10.4.05zM95.212 10.981l-5.519 11.696-5.664-11.696H80.76l7.318 15.117-5.645 11.943h3.248L98.46 10.98h-3.248z"/></svg>

        <div class="footer-brandbox__contacts">
            <? if ($arResult['PROPERTIES']['COMPANY_PHONE']['VALUE']):?>
                <a href="tel:<?=$arResult['PROPERTIES']['COMPANY_PHONE']['VALUE']?>" class="footer-contacts__phone"><?=$arResult['PROPERTIES']['COMPANY_PHONE']['VALUE']?></a>
            <?endif;?>
            <? if ($arResult['PROPERTIES']['COMPANY_PHONE_TEXT']['VALUE']):?>
                <p class="footer-contacts__text"><?=$arResult['PROPERTIES']['COMPANY_PHONE_TEXT']['VALUE']?></p>
            <?endif;?>
            <?if ($_SESSION['ispwa'] == 'Y'):?>
                <p class="footer-contacts__text" style="font-size: 8px;">pwa</p>
            <?endif;?>
        </div>
        <div class="footer-brandbox__socials">
            <noindex class="footer-brandbox__socials-inner">
                <? if ($arResult['PROPERTIES']['LINK_VK']['VALUE']):?>
                    <a href="<?=$arResult['PROPERTIES']['LINK_VK']['VALUE']?>" target="_blank" class="footer-social__icn footer-social__icn-vk" rel="nofollow"><svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20.9825 27C13.466 27 9.17864 21.7447 9 13H12.7651C12.8888 19.4184 15.6646 22.1371 17.8632 22.6977V13H21.4085V18.5355C23.5796 18.2973 25.8607 15.7748 26.6302 13H30.1755C29.8855 14.4391 29.3075 15.8017 28.4776 17.0025C27.6477 18.2033 26.5837 19.2166 25.3523 19.979C26.7269 20.6756 27.941 21.6615 28.9145 22.8717C29.888 24.082 30.5988 25.489 31 27H27.0974C26.7373 25.6876 26.0054 24.5128 24.9934 23.6228C23.9814 22.7328 22.7343 22.1673 21.4085 21.997V27H20.9825Z" fill="#1F2020"/></svg></a>
                <?endif;?>
                <? if ($arResult['PROPERTIES']['LINK_TG']['VALUE']):?>
                    <a href="<?=$arResult['PROPERTIES']['LINK_TG']['VALUE']?>" target="_blank" class="footer-social__icn footer-social__icn-tg" rel="nofollow"><svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M28 12.6022L24.9946 28.2923C24.9946 28.2923 24.5741 29.3801 23.4189 28.8584L16.4846 23.3526L16.4524 23.3364C17.3891 22.4654 24.6524 15.7027 24.9698 15.3961C25.4613 14.9214 25.1562 14.6387 24.5856 14.9974L13.8568 22.053L9.71764 20.6108C9.71764 20.6108 9.06626 20.3708 9.00359 19.8491C8.9401 19.3265 9.73908 19.0439 9.73908 19.0439L26.6131 12.1889C26.6131 12.1889 28 11.5579 28 12.6022Z" fill="#1F2020"/></svg></a>
                <?endif;?>
            </noindex>
        </div>
        <ul class="footer-brandbox__payments">
            <li><img src="<?=SITE_TEMPLATE_PATH?>/img/mir.svg"  alt="mir">
            <li><img src="<?=SITE_TEMPLATE_PATH?>/img/visa.svg" alt="visa"></li>
            <li><img src="<?=SITE_TEMPLATE_PATH?>/img/mastercard.svg" alt="mastercard"></li>
            <li><img src="<?=SITE_TEMPLATE_PATH?>/img/union.svg" alt="unionpay"></li>
            <li><img src="<?=SITE_TEMPLATE_PATH?>/img/jcb.svg" alt="jcb"></li>
            <li><img src="<?=SITE_TEMPLATE_PATH?>/img/amex.svg" alt="american express"></li>
        </ul>
    </div>
</div>