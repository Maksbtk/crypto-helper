<?php
global $USER;

$arParams['USER_AVATAR'] = '';
$arParams['USER_NAME'] = 'Клиент';
if($USER->IsAuthorized()) {
    $avatar = false;
    $filter = array("ID" => $USER->GetID());
    $rsUsers = CUser::GetList(($by = "ID"), ($order = "desc"), $filter);
    while ($arUser = $rsUsers->Fetch()) {

        if (!empty((int)$arUser["PERSONAL_PHOTO"]))
            $arParams['USER_AVATAR'] = CFile::ResizeImageGet($arUser["PERSONAL_PHOTO"], ["width" => 100, "height" => 100], BX_RESIZE_IMAGE_EXACT, true);

        if ($arUser["NAME"])
            $arParams['USER_NAME'] = $arUser['NAME'];

        if ($arUser["ID"])
            $arParams['USER_ID'] = $arUser['ID'];

    }
}