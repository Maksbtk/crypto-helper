<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?php
global $USER;
?>

<section id="catalogSearch" class="belleyou-searchbar">
    <div class="belleyou-searchbar__backdrop" data-close-searchbar></div>

    <div class="belleyou-searchbar-body">
        <header class="searchbar-header">
            <button class="belleyou-searchbar-сlose" data-close-searchbar>Закрыть</button>
            <h3 class="catalog-search-title">Поиск</h3>
            <?//форма для мобильной версии в header.php (.mobile-search)?>
            <form action="<?=$arResult["FORM_ACTION"]?>" method="get" class="js-search-form">
                <div class="form-catalog-search-wrapper">
                    <div class="form-catalog-search">

                        <input id="searchInputBig" name="q" type="text" class="form-catalog-search__input _inputSearch" placeholder="Поиск товаров" autocomplete="off">
                        <input type="hidden" value="<?=$arParams['SUGGEST_MAX_COUNT']?>" id="suggest-max-count">
                        <input type="hidden"  value="<?=$arParams['SUGGEST_SHOW_COUNT']?>" id="suggest-show-count">
                        <input type="hidden"  value="<?=$arParams['SUGGEST_SHOW_COUNT_MOBILE']?>" id="suggest-show-count-mobile">

                        <input class="form-catalog-search__button" type="submit" value="">
                        <buttton class="form-catalog-search__clear js-clear-search">x</buttton>
                        <svg class="search-spinner" viewBox="0 0 48 48"><circle class="path" cx="24" cy="24" r="20" fill="none" stroke-width="3"></circle></svg>
                    </div>
                    <button class="form-catalog-search__cancel js-search__close" data-close-searchbar>Отменить</button>
                </div>
            </form>
        </header>
        <div class="catalog-search-content">
            <div class="catalog-search-content__inner">

                <div class="catalog-search-intro">
                    <?if($arResult['LAST_SEARCH']):?>
                        <div class="catalog-search-queries catalog-search-queries__recent js-search-history">
                            <h4 class="catalog-search-box-title">Вы искали</h4>
                            <ul class="catalog-search-queries__list">
                                <? foreach ($arResult['LAST_SEARCH'] as $lastItem) :?>
                                    <li class="catalog-search-queries__item"><a class="catalog-search-query"><?=$lastItem?></a></li>
                                <?endforeach;?>
                            </ul>
                            <a class="catalog-search-queries__clear">Очистить</a>
                        </div>
                    <?endif;?>


                    <div class="catalog-search-queries catalog-search-queries__popular">
                        <h4 class="catalog-search-box-title">Популярные запросы</h4>
                        <ul class="catalog-search-queries__list">
                            <? foreach ($arResult['POPULAR_SEARCH'] as $popularItem) :?>
                                <li class="catalog-search-queries__item"><a class="catalog-search-query"><?=$popularItem?></a></li>
                            <?endforeach;?>
                        </ul>
                    </div>
                </div>

                <div class="catalog-search-result">
                    <div class="catalog-search-queries catalog-search-queries__current">
                        <ul class="catalog-search-queries__list" id="js-search-queries__list">
                            <?//сюда залетят варианты поиска?>
                        </ul>
                    </div>

                    <div class="catalog-search-preview" id="searchCollection">
                        <h4 class="catalog-search-box-title">Подборки</h4>
                        <ul class="products-list" id="js-collections__list">
                            <?//сюда залетит подборка товаров?>
                            <li class="product-item">
                                <div class="product-media-wrapper">
                                    <a class="product-link" href="product.html">
                                        <div class="product-picture-wrapper">
                                            <img class="product-picture" src="demo-pics/product1-451.jpg" alt="Топ на тонких бретелях">
                                        </div>
                                    </a>
                                    <a class="button-add-to-favorite"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16"><path stroke="#A0BCD2" stroke-linecap="round" stroke-linejoin="round" d="M15.023.53c-2.047-.23-4.25.912-5.024 2.618C9.225 1.442 7 .301 4.976.53 2.28.823.553 3.57 1.099 6.22c.744 3.604 4.801 5.214 8.9 9.281 4.081-4.067 8.156-5.683 8.9-9.282.547-2.65-1.182-5.395-3.876-5.689z"></path></svg></a>
                                    <div class="product-labels">
                                        <span class="product-label">New</span>
                                    </div>
                                </div>
                                <div class="product-info-wrapper">
                                    <a class="product-link" href="product.html"><h4 class="product-name">Топ на тонких бретелях</h4></a>
                                    <div class="product-pricebox">
                                        <span class="proudct-old-price">989 ₽</span>
                                        <span class="proudct-current-price">989 ₽</span>
                                    </div>
                                    <div class="product-colors-sheme">
                                        <ul class="product-colors-list">
                                            <li class="product-color product-color__with-border">
                                                <a style="background-image: url(https://belleyou.ru/upload/iblock/79e/79e96c531741ee2c701afdd9957d19d4.jpeg);"></a>
                                            </li>
                                            <li class="product-color">
                                                <a style="background-image: url(https://belleyou.ru/upload/iblock/1f0/1f0aeea049dfac0a3d9d8f464e4381ab.jpeg);"></a>
                                            </li>
                                            <li class="product-color">
                                                <a style="background-color: #000000;"></a>
                                            </li>
                                        </ul>
                                        <span class="product-more-colors-label">+2 цвета</span>
                                    </div>
                                </div>
                            </li>
                        </ul>

                    </div>

                    <footer class="searchbar-footer">
                        <a class="button js-showResult" href="">все результаты (<span class="js-countSuggest">0</span>)</a>
                    </footer>
                </div>

            </div>
        </div>

    </div>
</section>

<?php /*
<section class="belleyou-catalog-search hidden" <?if ($USER->IsAdmin()):?>style="background-color: #ffffff!important;"<?endif;?>>
    <div class="belleyou-catalog-search__inner">
        <button id="catalogSearchHideButton" class="belleyou-catalog-search__close">Отменить</button>

        <div class="catalog-search-box">
            <form action="<?=$arResult["FORM_ACTION"]?>" method="get" class="catalog-search-form">
                <div class="form-input-wrapper">
                    <input id="searchInputBig" name="q" type="text" class="catalog-serach__input _inputSearch" placeholder="Название, цвет или артикул" autocomplete="off">
                </div>
                <input class="button catalog-search__button" type="submit" class="submit" value="Найти">
                <input type="hidden" value="<?=$arParams['SUGGEST_MAX_COUNT']?>" id="suggest-max-count">
                <input type="hidden"  value="<?=$arParams['SUGGEST_SHOW_COUNT']?>" id="suggest-show-count">
                <input type="hidden"  value="<?=$arParams['SUGGEST_SHOW_COUNT_MOBILE']?>" id="suggest-show-count-mobile">
            </form>

            <div class="catalog-search-queries">
                <div class="catalog-search-queries__inner">
                    <div class="catalog-search-queries__popular">
                        <h3 class="catalog-search-queries__title">Популярные запросы</h3>
                        <ul class="catalog-search-queries__list">
                            <? foreach ($arResult['POPULAR_SEARCH'] as $popularItem) :?>
                                <li class="catalog-search-queries__item"><a class="catalog-search-queries__link"><?=$popularItem?></a></li>
                            <?endforeach;?>
                        </ul>
                    </div>
                    <?if($arResult['LAST_SEARCH']):?>
                        <div class="catalog-search-queries__recent <?if(!$arResult['LAST_SEARCH']):?>hidden<?endif;?>">
                            <h3 class="catalog-search-queries__title">Вы искали</h3>
                            <ul class="catalog-search-queries__list">
                                <? foreach ($arResult['LAST_SEARCH'] as $lastItem) :?>
                                    <li class="catalog-search-queries__item"><a class="catalog-search-queries__link"><?=$lastItem?></a></li>
                                <?endforeach;?>
                            </ul>
                            <a class="catalog-search-queries__clear">Очистить</a>
                        </div>
                    <?endif;?>
                </div>
            </div>

            <div class="catalog-search-results-preview hidden">
                <ul class="catalog-search-queries__list" id="js-search-queries__list"></ul>
                <div class="catalog-search-results-previews__list hidden">
                    <h3 class="catalog-search-queries__title">Подборки</h3>
                    <ul class="search-reuslts-previews-list short-catalog" id="js-collections__list"></ul>
                </div>
                <a href="" class="catalog-search-results__show-results hidden">Смотреть результаты (<span id="countSuggest">0</span>)</a>
            </div>
       </div>
    </div>
</section>
 */?>