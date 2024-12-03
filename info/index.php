<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Информация");
?><?
use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/page-userguide.css");
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/page-returns.css");
Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/jquery-returns-accordeon.js");
?>
<?php /*    <link rel="stylesheet" href="<?=SITE_TEMPLATE_PATH?>/css/page-userguide.css">
    <link rel="stylesheet" href="<?=SITE_TEMPLATE_PATH?>/css/page-returns.css">
    <script defer src="<?=SITE_TEMPLATE_PATH?>/js/jquery-returns-accordeon.js"></script>
*/?>

    <div class="maksv-content-layout">

        <div class="page-userguide-wrapper">
            <?/*<aside class="page-userguide-sidebar">
                <div class="maksv-content__header">
                    <h1>Помощь покупателю</h1>
                </div>
                <nav class="userguide-nav">
                    <ul class="userguide-links">
                        <li class="userguide-item"><a href="/for-customers/payment/">Оплата</a></li>
                        <li class="userguide-item"><a href="/for-customers/delivery/">Доставка</a></li>
                        <li class="userguide-item current"><a href="/for-customers/refund/">Возврат</a></li>
                        <li class="userguide-item"><a href="/for-customers/app/">Установка приложения</a></li>
                    </ul>
                </nav>
            </aside>*/?>

            <section class="page-userguide-section">
                <div class="page-returns-intro">
                    <p>Мы очень хотим, чтобы вы остались довольны нашим инфопродуктом, вот некоторые инструкции по сайту:</p>
                </div>

                <div class="returns-section">
                    <h2 class="returns-title">Получить тестовый доступ</h2>
                    <div class="returns-body">
                        <p>Вы можете получить тестовый доступ к сигналам.</p>
                        <p>Тестовый доступ выдается один раз на один аккаунт на срок 3-5 дней.</p>
                        <p>Вы можете связаться с нами через <a href="/contacts/" target="_blank">форму обратной связи</a> либо через <a href="https://t.me/CryptoHelperSupport" target="_blank" class="" rel="nofollow">телеграм</a></p>

                        <h3>Как получить тестовый доступ?</h3>
                        <ul class="list-number">
                            <li>Обратитесь в службу поддержки через страницу <a href="/contacts/" target="_blank">Контакты</a>. </li>
                            <li>В сообщении предоставьте почту указанную при <a href="/user/profile/?register=yes" target="_blank">регистрации</a> на нашем сайте</li>
                            <li>Дождитесь ответа от службы поддержки, обычно это занимает не больше 1 часа</li>
                            <li>Зарабатывайте на сигналах!</li>
                        </ul>


                        <p>*купить подписку можно <a target="_blank" href="/user/subscriptions/">тут</a></p>
                        <p>*ознакомиться с условиями публичной оферты можно <a target="_blank" href="/oferta/">тут</a></p>
                        <p>*лицо, использующее информационные продукты распространяемые на сайте "Crypto Helper", несет полную ответственность за свои сделки на бирже</p>
                        <p>*для использования информационных продуктов, распространяемых сайтом "Crypto Helper", необходимы навыки торговли на бирже</p>

                    </div>
                </div>

                <div class="returns-section">
                    <h2 class="returns-title">Как формируются сигналы и как работает таблица технического анализа</h2>
                    <div class="returns-body">
                        <p>Сигналы формируются каждые 30 минут на основе индикаторов</p>
                        <p>Для формирования сигналов используются разные таймфреймы</p>
                        <p>Для просмотра более детальной информации по конкретному сигналу необходимо кликнуть по нему два раза либо ввести название контракта в поле ввода</p>

                        <h3>Сигналы формируются за счет следующих индикаторов</h3>
                        <ul class="list-disc">
                            <li>MA (средняя скользящая)
                            <li>EMA (экспоненциальная скользящая средняя)
                            <li>SAR (тренд на основе параболической системе времени/цены)
                            <li>SUPERTRAND (тренд на основе волатильности ATR)
                            <li>OI (открытый интерес)
                        </ul>

                        <p>*купить подписку можно <a target="_blank" href="/user/subscriptions/">тут</a></p>
                        <p>*ознакомиться с условиями публичной оферты можно <a target="_blank" href="/oferta/">тут</a></p>
                        <p>*лицо, использующее информационные продукты распространяемые на сайте "Crypto Helper", несет полную ответственность за свои сделки на бирже</p>
                        <p>*для использования информационных продуктов, распространяемых сайтом "Crypto Helper", необходимы навыки торговли на бирже</p>

                    </div>
                </div>

            </section>
        </div>

    </div>


<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>