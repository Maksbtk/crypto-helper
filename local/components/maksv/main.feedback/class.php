<?php
/** @global CMain $APPLICATION */
/** @global CUser $USER */
use Bitrix\Main,
    Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache,
    Bitrix\Main\Mail\Event,
    Bitrix\Main\Engine\Contract\Controllerable;

CModule::IncludeModule("iblock");

class MainFeedback extends CBitrixComponent implements Controllerable
{

    public function __construct($component = null)
    {
        parent::__construct($component);
    }

    public function onPrepareComponentParams($arParams)
    {

        if(!($arParams["CACHE_TYPE"]))
            $arParams["CACHE_TYPE"] = 'N';

        if(!($arParams["CACHE_TIME"]))
            $arParams["CACHE_TIME"] = 36000;

        if(!($arParams["USE_CAPTCHA"]))
            $arParams["USE_CAPTCHA"] = 'N';

        if(!($arParams["SUBJECT_FROM"]))
            $arParams["SUBJECT_FROM"] = 'N';

        if(!($arParams["EMAIL_SEND"]))
            $arParams["EMAIL_SEND"] = 'N';

        if(!($arParams["EMAIL_TO"]))
            $arParams["EMAIL_TO"] = 'N';

        if(!($arParams["EVENT_NAME"]))
            $arParams["EVENT_NAME"] = 'N';

        return $arParams;
    }

    public function configureActions(): array
    {
        return [
            'sendAndSave' => [
                'prefilters' => []
            ],
        ];
    }

    // ajax запрос из шаблона
    public function sendAndSaveAction()
    {
        $res = [];
        $res['status_iblock'] = false;
        $res['status_email'] = false;
        $arFields = [];
        $elementProperty = [];

        // смотрим какая форма и заполняем поля
        switch ($_REQUEST['FORM_TYPE']) {
            case 'contacts-form':
                if ($_REQUEST['EVENT_NAME'] && $_REQUEST['FORM_TYPE'] && $_REQUEST['EMAIL_SEND'] &&
                    $_REQUEST['EMAIL_TO'] && $_REQUEST['SUBJECT'] && $_REQUEST['NAME'] &&
                    $_REQUEST['NUMBER'] && $_REQUEST['EMAIL'] && $_REQUEST['MASSAGE']) {
                    $arFields = [
                        "EVENT_NAME" => $_REQUEST['EVENT_NAME'],
                        "FORM_TYPE" => $_REQUEST['FORM_TYPE'],
                        "EMAIL_SEND" => $_REQUEST['EMAIL_SEND'],
                        "EMAIL_TO" => $_REQUEST['EMAIL_TO'],
                        "ACTIVE" => 'Y',
                        "IBLOCK_ID" => 5, // 5 - id инфоблока "Контакты - Форма обратной связи"
                        "SUBJECT" => $_REQUEST['SUBJECT'],
                        "NAME" => $_REQUEST['NAME'],
                        "NUMBER" => $_REQUEST['NUMBER'],
                        "EMAIL" => $_REQUEST['EMAIL'],
                        "MASSAGE" => $_REQUEST['MASSAGE'],
                    ];
                } else {
                    $arFields = 'Не все поля заполнены!';
                }
                break;
            case 'feedback-form':
                if ($_REQUEST['EVENT_NAME'] && $_REQUEST['FORM_TYPE'] && $_REQUEST['EMAIL_SEND'] &&
                    $_REQUEST['EMAIL_TO'] && $_REQUEST['RATE'] && $_REQUEST['NAME'] &&
                    $_REQUEST['CITY'] && $_REQUEST['MASSAGE']) {
                    $arFields = [
                        "EVENT_NAME" => $_REQUEST['EVENT_NAME'],
                        "FORM_TYPE" => $_REQUEST['FORM_TYPE'],
                        "EMAIL_SEND" => $_REQUEST['EMAIL_SEND'],
                        "EMAIL_TO" => $_REQUEST['EMAIL_TO'],
                        "ACTIVE" => 'N',
                        "IBLOCK_ID" => 18, // 17 - id инфоблока "Отзывы - Форма оставить отзыв"
                        "RATE" => $_REQUEST['RATE'],
                        "NAME" => $_REQUEST['NAME'],
                        "CITY" => $_REQUEST['CITY'],
                        "MASSAGE" => $_REQUEST['MASSAGE'],
                    ];

                    // так как свойство RATING это список, достаем enum_id
                    $arRates = [];
                    $ratesOb = CIBlockPropertyEnum::GetList([],[
                        "IBLOCK_ID" => 18,
                        "PROPERTY_CODE" => 'RATING', // CODE = "RATING"
                    ]);
                    while ($rate = $ratesOb->Fetch()){
                        $arRates[strval($rate['VALUE'])] = $rate["ID"];
                    }

                    $elementProperty = [
                        'RATING' => ["VALUE" => $arRates[strval($_REQUEST['RATE'])]], // далее подставляем нужное enum_id
                        'USER_NAME' => $_REQUEST['NAME'],
                        'USER_CITY' => $_REQUEST['CITY'],
                        'USER_FEEDBACK' => $_REQUEST['MASSAGE'],
                    ];

                } else {
                    $arFields = 'Не все поля заполнены!';
                }
                break;
            default:
                break;
        }

        if ($arFields) {

            $resSaveToIblock = $this->saveToIblock($arFields, $elementProperty);
            if ($resSaveToIblock['status']) { // если получилось сохранить в инфоблок

                $res['status_iblock'] = true;
                $res['arFields'] = $arFields;
                $res['elementProperty'] = $elementProperty;

                if ($arFields['EMAIL_SEND'] == 'Y' && $arFields['EMAIL_TO'] != 'N'  && $arFields['EVENT_NAME'] != 'N') { // если стоит параметр отправить email и существует параметр email и существует параметр event

                    $resSendEmail = $this->sendEmail($arFields);
                    if ($resSendEmail) { // если отправили email
                        $res['status_email'] = true;
                    } else {
                        $res['data'] = 'Не удалось отправить email';
                    }

                } else {
                    $res['data'] = 'Не удалось отправить email, ошибка параметров';
                }

            } else {
                $res['data'] = $resSaveToIblock['data'];
                //$res['data'] = 'Не удалось сохранить в инфоблок';
            }

        }
        return $res;
    }

    protected function saveToIblock($arFields, $elementProperty)
    {
        global $USER;
        $modifiedBy = 1;
        if ($USER->IsAuthorized()){
            $modifiedBy = $USER->GetID();
        }

        $previewText = '';

        $stopNameFieldAr = [ 'FORM_TYPE', 'IBLOCK_ID', 'EMAIL_SEND', 'EMAIL_TO', 'EVENT_NAME', 'ACTIVE'];
        foreach ($arFields as $nameField => $valueField) {
            //if ($nameField != 'FORM_TYPE' && $nameField != 'IBLOCK_ID' && $nameField != 'EMAIL_SEND' && $nameField != 'EMAIL_TO' && $nameField != 'EVENT_NAME')
            if (!in_array($nameField, $stopNameFieldAr))
                $previewText.= $nameField . ' : ' . $valueField . ' //  ';
        }

        $el = new CIBlockElement;
        $arLoadElementArray = Array(
            "MODIFIED_BY"    => $modifiedBy,
            "IBLOCK_SECTION_ID" => false, // элемент лежит в корне раздела
            "IBLOCK_ID"      => $arFields['IBLOCK_ID'],
            "NAME"           => 'от '. $arFields['NAME'] . ' \ Дата - ' . date("d.m.y G:i:s") ,
            "ACTIVE"         => $arFields['ACTIVE'],
            "PREVIEW_TEXT"   => $previewText,
            "PROPERTY_VALUES"=> $elementProperty,
        );

        if ($elementId = $el->Add($arLoadElementArray))
            return ['status' => true,'data' => $elementId];
        else
            return ['status' => false,'data' => $el->LAST_ERROR];

    }

    protected function sendEmail($arFields)
    {
        //$arFields['DEFAULT_EMAIL_FROM'] = 'ch@infocrypto-helper.ru';
        $arFields['DEFAULT_EMAIL_FROM'] = 'support@infocrypto-helper.ru';
        $arFields['SITE_NAME'] = 'Crypto Helper';
        $resSendEmail = Event::send(array(
            "EVENT_NAME" => $arFields['EVENT_NAME'],
            "LID" => "s1",
            "C_FIELDS" => $arFields,
            "LANGUAGE_ID" => "ru"
        ));

        if ($resSendEmail)
            return true;
        else
            return false;
    }

    public function executeComponent()
    {
        $this->includeComponentTemplate();
    }

}
