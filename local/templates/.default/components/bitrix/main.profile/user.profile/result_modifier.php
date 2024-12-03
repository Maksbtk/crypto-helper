<?php

$userGroupsOb = \CUser::GetUserGroupList($arResult['ID']);
// Обрабатываем существующие группы
$prodGroups = ['Сигналы с биржи bybit' => '6'];
while ($arGroup = $userGroupsOb->Fetch()) {
    if (in_array($arGroup['GROUP_ID'], $prodGroups)) {
        $arGroup['NAME'] = array_search($arGroup['GROUP_ID'],$prodGroups);
        $arResult['ACTIVE_SUBSCRIPTIONS'][] = $arGroup;
    }
}

/*$res = \Bitrix\Main\UserGroupTable::getList(['filter' => ['USER_ID' => $arResult['ID']], 'select' => ['NAME', 'DATE_ACTIVE_FROM', 'DATE_ACTIVE_TO', ' GROUP_ID']]);
while ($row = $res->fetch()) {
    $arResult['USER_GROUPS'][$row['GROUP_ID']] = $row;
}*/