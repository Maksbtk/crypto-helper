<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
$oUser = new CUser;

$arFields = [];
$arDelFile = [];
//сначала удаляем старое фото из upload
$idPersonalPhoto = (int) ($oUser->GetByID($_POST['user-id'])->Fetch()['PERSONAL_PHOTO']);
if ($idPersonalPhoto) {
    CFile::Delete($idPersonalPhoto);
    $arDelFile = ['del' => 'Y', 'old_file' => $idPersonalPhoto];
}

//далее смотрим какое действие нас просит сделать юзер
if ($_POST['delete-photo'] == 'n') {  // если просит загрузить
    if (!empty($_FILES["personal-photo"]['name'])) {
        $fileId = CFile::SaveFile($_FILES["personal-photo"], 'avatar');
        $arFile = CFile::MakeFileArray($fileId);
        $arFile = array_merge($arDelFile, $arFile);
        $arFields['PERSONAL_PHOTO'] = $arFile;
    }

    $result = $oUser->Update($_POST['user-id'], $arFields);

    if ($result) {
        $result = [
            'status' => 'success',
            'msg' => 'Profile updated, add img'
        ];
    }

} elseif($_POST['delete-photo'] == 'y') { // если просит удалить фото

    $arFields['PERSONAL_PHOTO'] = $arDelFile;
    $result = $oUser->Update($_POST['user-id'], $arFields);

    if ($result) {
        $result = [
            'status' => 'success',
            'msg' => 'Profile updated, del img',
        ];
    }

}

echo json_encode($result);
//echo json_encode($_FILES);