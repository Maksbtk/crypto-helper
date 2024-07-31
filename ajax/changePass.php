<?require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

function changePassword($oldPass, $pass, $confirmPass){
    global  $USER;
    $res['status'] = false;
    $res['message'] = '';

    if ($oldPass != $pass) {
        $arAuthResult = $USER->Login($USER->GetLogin(), $oldPass, "Y");
        if ($arAuthResult == 1) {
            if ($pass == $confirmPass) {
                $user = new CUser;
                $fields = array(
                    "PASSWORD" => $pass,
                    "CONFIRM_PASSWORD" => $confirmPass,
                );
                $user->Update($USER->GetID(), $fields);
                $strError = $user->LAST_ERROR;
                if (empty($strError)) {
                    $res['status'] = true;
                    $res['message'] = 'success';
                } else {
                    $res['message'] = $strError;
                }
            } else {
                $res['message'] = 'Новые пароли не совпадают';
            }
        } else {
            $res['message'] = 'Не верный старый пароль';
        }
    } else {
        $res['message'] = 'Старый пароль не отличается от нового';
    }

    return $res;
}

echo json_encode(changePassword($_POST["old_password"], $_POST["new_password"], $_POST["new_password_confirm"]));
die();
?>