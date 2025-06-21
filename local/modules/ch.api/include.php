<?php

$module_folder = \Bitrix\Main\Application::getDocumentRoot() . '/local/modules/ch.api';

\Bitrix\Main\Loader::registerNamespace('Ch\Api\Controller', $module_folder . '/controller');