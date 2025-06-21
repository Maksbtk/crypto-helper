<?php

class ch_api extends CModule
{
    var $MODULE_ID = 'ch.api';
    var $MODULE_NAME = 'ch api';
    var $MODULE_DESCRIPTION = "Модуль для сайта crypto helper";
    var $MODULE_VERSION = "1.0";
    var $MODULE_VERSION_DATE = "2023-04-09 12:00:00";
    var $PARTNER_NAME = 'VASILII MAKSIMOV';
    var $PARTNER_URI = 'https://infocrypto-helper.ru/';

    public function DoInstall()
    {
        \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
    }

    public function DoUninstall()
    {
        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
    }
}
