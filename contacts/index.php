<?
use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Контакты - Crypto Helper");
$APPLICATION->SetTitle("Контакты - Crypto Helper");

Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/page-contacts.css");

?>
    <div class="maksv-content-layout">
        <div class="maksv-content__header">
            <h1>Контакты</h1>
        </div>
        <div class="page-contacts-wrapper">
            <div class="page-contacts-box">
                <div class="page-contacts-customer">
                    <h2 class="page-contacts-subtitle">Служба поддержки</h2>
                    <div class="customer-service-phone">
                        <?/*<a href="tel:+79502675091">+7–950–267–50–91</a>*/?>
                        <p>
                            Мы на связи ежедневно с 7:00 до 20:00 по московскому времени.
                        </p>
                    </div>
                    <div class="customer-service-social">
                        <div class="customer-service-social-line">
                            <?/*<a href="https://vk.com/belle_you" target="_blank" class="" rel="nofollow">
                                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20.9825 27C13.466 27 9.17864 21.7447 9 13H12.7651C12.8888 19.4184 15.6646 22.1371 17.8632 22.6977V13H21.4085V18.5355C23.5796 18.2973 25.8607 15.7748 26.6302 13H30.1755C29.8855 14.4391 29.3075 15.8017 28.4776 17.0025C27.6477 18.2033 26.5837 19.2166 25.3523 19.979C26.7269 20.6756 27.941 21.6615 28.9145 22.8717C29.888 24.082 30.5988 25.489 31 27H27.0974C26.7373 25.6876 26.0054 24.5128 24.9934 23.6228C23.9814 22.7328 22.7343 22.1673 21.4085 21.997V27H20.9825Z" fill="#A0BCD2"></path></svg>
                            </a>*/?>
                            <a href="https://t.me/CryptoHelperSupport" target="_blank" class="" rel="nofollow">
                                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M28 12.6022L24.9946 28.2923C24.9946 28.2923 24.5741 29.3801 23.4189 28.8584L16.4846 23.3526L16.4524 23.3364C17.3891 22.4654 24.6524 15.7027 24.9698 15.3961C25.4613 14.9214 25.1562 14.6387 24.5856 14.9974L13.8568 22.053L9.71764 20.6108C9.71764 20.6108 9.06626 20.3708 9.00359 19.8491C8.9401 19.3265 9.73908 19.0439 9.73908 19.0439L26.6131 12.1889C26.6131 12.1889 28 11.5579 28 12.6022Z" fill="#A0BCD2"></path></svg>
                            </a>
                            <?/*<a href="https://wa.me/79502675091" target="_blank" class="" rel="nofollow"><svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M27.9268 12.0625C25.9512 10.0937 23.3171 9 20.5366 9C14.7561 9 10.0732 13.6667 10.0732 19.4271C10.0732 21.25 10.5854 23.0729 11.4634 24.6042L10 30L15.561 28.5417C17.0976 29.3438 18.7805 29.7812 20.5366 29.7812C26.3171 29.7812 31 25.1146 31 19.3542C30.9268 16.6563 29.9024 14.0312 27.9268 12.0625ZM25.5854 23.1458C25.3659 23.7292 24.3415 24.3125 23.8293 24.3854C23.3902 24.4583 22.8049 24.4583 22.2195 24.3125C21.8537 24.1667 21.3415 24.0208 20.7561 23.7292C18.122 22.6354 16.439 20.0104 16.2927 19.7917C16.1463 19.6458 15.1951 18.4062 15.1951 17.0937C15.1951 15.7812 15.8537 15.1979 16.0732 14.9062C16.2927 14.6146 16.5854 14.6146 16.8049 14.6146C16.9512 14.6146 17.1707 14.6146 17.3171 14.6146C17.4634 14.6146 17.6829 14.5417 17.9024 15.0521C18.122 15.5625 18.6341 16.875 18.7073 16.9479C18.7805 17.0938 18.7805 17.2396 18.7073 17.3854C18.6341 17.5312 18.561 17.6771 18.4146 17.8229C18.2683 17.9687 18.1219 18.1875 18.0488 18.2604C17.9024 18.4062 17.7561 18.5521 17.9024 18.7708C18.0488 19.0625 18.561 19.8646 19.3659 20.5937C20.3902 21.4687 21.1951 21.7604 21.4878 21.9063C21.7805 22.0521 21.9268 21.9792 22.0732 21.8333C22.2195 21.6875 22.7317 21.1042 22.878 20.8125C23.0244 20.5208 23.2439 20.5938 23.4634 20.6667C23.6829 20.7396 25 21.3958 25.2195 21.5417C25.5122 21.6875 25.6585 21.7604 25.7317 21.8333C25.8049 22.0521 25.8049 22.5625 25.5854 23.1458Z" fill="#A0BCD2"></path></svg></a>*/?>

                        </div>
                        <p>Будем рады ответить на ваши вопросы в Telegram</p>

                    </div>
                </div>
                <?/*<div class="page-contacts-writeus">
                    <h2 class="page-contacts-subtitle">Напишите нам</h2>
                    <ul class="writeus-list">
                        <li class="writeus-item"> <a href="mailto:maksvasa1998@yandex.ru">maksvasa1998@yandex.ru</a>
                            По любым вопросам</li>
                    </ul>
                </div>*/?>
            </div>
            <?
            $APPLICATION->IncludeComponent(
                "maksv:main.feedback",
                "contacts",
                array(
                    "COMPONENT_TEMPLATE" => "contacts",
                    "USE_CAPTCHA" => "Y",
                    "SUBJECT_FROM" => array(
                        0 => "Получить тестовый доступ",
                        1 => "Сотрудничество",
                        2 => "Проблемы с подпиской",
                        3 => "Предложение/Пожелания",
                    ),
                    "EMAIL_SEND" => "Y",
                    "EMAIL_TO" => "maksvasa1998@yandex.ru",
                    //"EMAIL_TO" => "davletovaam@yandex.ru",
                    //"EMAIL_TO" => "aniuta.davletova@yandex.ru",
                    //"EMAIL_TO" => "davletovaanna60@gmail.com",
                    "EVENT_NAME" => "FEEDBACK_FORM",
                ),
                false
            );
            ?>
        </div>
    </div>


<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php")?>