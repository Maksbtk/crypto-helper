<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?php
global $USER;
?>

<section id="catalogSearch" class="belleyou-searchbar">
    <div class="belleyou-searchbar__backdrop" data-close-searchbar></div>

    <div class="belleyou-searchbar-body">
        <header class="searchbar-header">
            <button class="belleyou-searchbar-сlose" data-close-searchbar>Закрыть</button>
            <div class="h3 catalog-search-title">Поиск</div>
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
                            <div class="h4 catalog-search-box-title">Вы искали</div>
                            <ul class="catalog-search-queries__list">
                                <? foreach ($arResult['LAST_SEARCH'] as $lastItem) :?>
                                    <li class="catalog-search-queries__item"><a class="catalog-search-query"><?=$lastItem?></a></li>
                                <?endforeach;?>
                            </ul>
                            <a class="catalog-search-queries__clear">Очистить</a>
                        </div>
                    <?endif;?>


                    <div class="catalog-search-queries catalog-search-queries__popular">
                        <div class="h4 catalog-search-box-title">Популярные запросы</div>
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
                        <div class="h4 catalog-search-box-title">Подборки</div>
                        <ul class="products-list" id="js-collections__list">
                            <?//сюда залетит подборка товаров?>
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
