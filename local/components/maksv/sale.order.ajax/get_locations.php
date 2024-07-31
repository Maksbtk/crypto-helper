<?php
define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", true);
define("NO_AGENT_CHECK", true);
define("NOT_CHECK_PERMISSIONS", true);

use Bitrix\Main;
use Bitrix\Main\Loader;

require_once($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/main/include/prolog_before.php');

Loader::includeModule('sale');

require_once($_SERVER["DOCUMENT_ROOT"].'/bitrix/components/bitrix/sale.location.selector.search/class.php');

$result = true;
$errors = array();
$data = array();

try
{
    CUtil::JSPostUnescape();

    $request = Main\Context::getCurrent()->getRequest()->getPostList();
//    if($request['version'] == '2')
//        $data = CBitrixLocationSelectorSearchComponent::processSearchRequestV2($_REQUEST);
//    else
//        $data = CBitrixLocationSelectorSearchComponent::processSearchRequest();
    $phrase = $_REQUEST['filter']['=PHRASE'] ?? null;
    //$phrase = str_replace('ё', 'е', $phrase);

    $countryId = intval($_REQUEST['filter']['=PARENTS.ID'] ?? null);
    if(is_string($phrase) and strlen(trim($phrase)) > 0 and $countryId > 0){
        $types = array();
        $res = \Bitrix\Sale\Location\TypeTable::getList(array('select' => array('ID', 'CODE')));
        while ($item = $res->fetch()) {
            $types[$item['CODE']] = $item['ID'];
        }

        $locationRes = locationFind($phrase, $countryId, $types['COUNTRY']);
        $data['ITEMS'] = $locationRes['ITEMS'];

        if (!$data['ITEMS'] && strpos($phrase, 'ё') !== false)
        {
            $phrase = str_replace('ё', 'е', $phrase);
            $locationRes = locationFind($phrase, $countryId, $types['COUNTRY']);
            $data['ITEMS'] = $locationRes['ITEMS'];
        }
        
    }


}
catch(Main\SystemException $e)
{
    $result = false;
    $errors[] = $e->getMessage();
}

function locationFind($phrase, $countryId, $countryType)
{
    $filters = [
        'select' => [
            '1' => 'CODE',
            '2' => 'TYPE_ID',
            'VALUE' => 'ID',
            'DISPLAY' => 'NAME.NAME',
        ],
        'additionals' => ['PATH'],
        'filter' => [
            '=NAME.LANGUAGE_ID' => 'ru',
            '=PHRASE' => trim($phrase),
            '=PARENTS.ID' => $countryId,
            '=PARENTS.TYPE_ID' => $countryType,
        ],
        'version' => 2,
        'PAGE_SIZE' => 20,
        'PAGE' => 0,
    ];
    $list = \Bitrix\Sale\Location\Search\Finder::find($filters);
    $items = [];
    while ($item = $list->Fetch()){
        $devList[] = $item;
        #Проверка точного вхождения
        if(
            (intval($item["TYPE_ID"]) == 5 && $item["DISPLAY"] == $phrase)
            || (intval($item["TYPE_ID"]) == 6 && ($item["DISPLAY"] == $phrase || stripos($item["DISPLAY"], $phrase) !== false))
        ) {
            $items['ITEMS'][] = $item;
        }
    }

    $items['devList'] = $devList;
    return $items;
}

header('Content-Type: application/json; charset='.LANG_CHARSET);
echo json_encode([
    'result' => $result,
    'errors' => $errors,
    'data' => $data,
    //'dev' => $phrase,
    //'dev1' => $locationRes['devList'],
]);
