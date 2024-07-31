<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
//$APPLICATION->SetTitle("Личный кабинет");
LocalRedirect('/user/profile/', false, '301 Moved permanently');
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>