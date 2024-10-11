<?
use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Об инструменте - Crypto Helper");
$APPLICATION->SetTitle("Об инструменте - Crypto Helper");

Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/page-aboutCH.css");
?>
    <div class="page-loyalty-header">
        <div class="page-loyalty-header-inner">
            <a href="javascript:void(0)" class="page-loyalty-header-logo"> </a>
            <?if($_SESSION["UF_PL_MEMBER"] !== "Y"){?>
                <a href="/user/subscriptions/" class="button button">Купить сигналы</a> <a href="/user/subscriptions/" class="button button-mobile">Купить подписку</a>
            <?}?>
        </div>
    </div>
    <div class="page-loyalty-body">
        <div class="page-loyalty-section page-loyalty-howitworks">
            <h2 class="page-loyalty-subtitle">Как это работает?</h2>

            <div class="howitworks">
                <div class="howitworks__item">
                    <h3 class="howitworks__step">Зарегиструйтесь</h3>
                    <p class="howitworks__details">
                        с помощью электронной почты
                    </p>
                </div>
                <div class="howitworks__item">
                    <h3 class="howitworks__step">Купите подписку<br>
                        подписку</h3>
                    <p class="howitworks__details">
                       можно выбрать любой срок
                    </p>
                </div>
                <div class="howitworks__item">
                    <h3 class="howitworks__step">Получайте <br>
                        сигналы</h3>
                    <p class="howitworks__details">
                        для торговли на бирже
                    </p>
                </div>
            </div>
        </div>
        <div class="page-loyalty-section page-loyalty-statuses">
            <h2 class="page-loyalty-subtitle">Варианты подписок</h2>
            <p class="page-loyalty-intro">
                На данный момент доступны три варианта подписок<br>
                для торговли на бирже bybit
            </p>
            <div class="statuses statuses-slider">
                <div class="status status-1 slide">
                    <div class="status-level-box">
                        <span class="page-loyalty-level-icon"></span>
                    </div>
                    <h4>Сигналы bybit</h4>
                    <div class="status__money">
                        на 30 дней
                    </div>
                </div>
                <div class="status status-2 slide">
                    <div class="status-level-box">
                        <span class="page-loyalty-level-icon"></span> <span class="page-loyalty-level-icon"></span>
                    </div>
                    <h4>Сигналы bybit</h4>
                    <div class="status__money">
                        на 3 месяца
                    </div>
                </div>
                <div class="status status-3 slide">
                    <div class="status-level-box">
                        <span class="page-loyalty-level-icon"></span> <span class="page-loyalty-level-icon"></span> <span class="page-loyalty-level-icon"></span>
                    </div>
                    <h4>Сигналы bybit</h4>
                    <div class="status__money">
                        на половину года
                    </div>
                </div>
                <!--a class="prev" onclick="minusSlide()">❮</a>
               <a class="next" onclick="plusSlide()">❯</a-->
            </div>
            <?if($_SESSION["UF_PL_MEMBER"] !== "Y"){?> <a href="/user/subscriptions/" class="button button">Купить подписку</a>
            <?}?>
        </div>
        <div class="page-loyalty-section page-loyalty-additional">
            <h2 class="page-loyalty-subtitle">Дополнительная информация</h2>
            <div class="additionals-list">
                <ul>
                    <li>Сигналы формируются с помощью технического анализа в автоматическом режиме</li>
                    <li>Сигналы приходят стабильно на протяжении всего дня</li>
                    <li>Количество сигналов не ограничено, все зависит от волатильности рынка</li>
                </ul>
                <ul>
                    <li>Помимо сигналов доступен инструмент для технического анализа любого деривативного контракта</li>
                    <li>В таблице есть возможность узнать различные параметры технического анализа для разных таймфреймов</li>
                </ul>
            </div>
        </div>
        <div class="page-loyalty-section page-loyalty-learnmore">
            <h2 class="page-loyalty-subtitle">Важно!</h2>
            <p class="page-loyalty-intro">
                Для использования инструмента необходимы навыки торговли бирже<br>
            </p>
        </div>
        <div class="page-loyalty-section page-loyalty-learnmore">
            <h2 class="page-loyalty-subtitle">Хотите узнать больше?</h2>
            <p class="page-loyalty-intro">
                Для получения дополнительной информации об инструменте<br>
                перейдите в раздел <a href="/oferta/">оферта</a> или задайте вопрос <a href="/contacts/">в службу поддержки</a>
            </p>
        </div>
    </div><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>