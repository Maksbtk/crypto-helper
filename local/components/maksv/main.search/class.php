<?php
//require_once ($_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/belleyou/autoload.php');

/** @global CMain $APPLICATION */
/** @global CUser $USER */
use Bitrix\Main,
    Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache,
    Bitrix\Main\Application,
    Bitrix\Main\Engine\Contract\Controllerable;

CModule::IncludeModule("search");

class SearchComponent extends CBitrixComponent implements Controllerable
{

    public function __construct($component = null)
    {
        parent::__construct($component);

    }

    public function configureActions(): array
    {
        return [
            'searchSuggestion' => [
                'prefilters' => []
            ],
        ];
    }

    public function onPrepareComponentParams($arParams)
    {
        if(!isset($arParams["PAGE"]) || $arParams["PAGE"] == '')
            $arParams["PAGE"] = "#SITE_DIR#search/index.php";

        if(!($arParams["CACHE_TIME"]))
            $arParams["CACHE_TIME"] = 36000;

        $arParams['CATEGORIES'] = [
            'топ',
            'трусы',
            'толстовка',
            'брюки',
            'колготки',
            'костюм',
            'майка',
            'боди',
            'кроп-топ',
            'лонгслив',
            'велосипедки',
            'комплект белья',
            'пантели',
            'мяч',
            'утяжелители',
            'водолазка',
            'свитшот',
            'блейзер',
            'шорты',
            'юбка',
            'футболка',
            'платье',
            'термобелье',
            'лиф',
            'купальник',
            'носки',
            'гетры',
            'коврик',
            'сумка',
            'чашки',
            'резинки',
            'панама',
            'повязка',
            'халат',
            'рубашка',
            'кардиган',
            'платок',
            'куртка',
            'варежки',
            'шапка',
            'комбинация',
            'легинсы',
            'термо-футболка',
            'термо-легинсы',
            'бразильяно',
            'кюлот',
            'слипы',
            'стринги'
        ];

        return $arParams;
    }

    //самые популярные поисковые запросы, берем из /bitrix/admin/search_phrase_stat.php
    public function getPopularSearch()
    {
        #Что-то не так с кешированием
        /*global $USER;
        global $DB;
        $res = [];
        $cache = Cache::createInstance();
        if ($cache->initCache(36000, 'PopularMainSearch')) {
            $res = $cache->getVars();
            
            devlogs($res, "SEARCH_CACHE");
        } elseif($cache->startDataCache()) {
            $sql = 'select COUNT(RESULT_COUNT), PHRASE from b_search_phrase group by PHRASE ORDER BY COUNT(RESULT_COUNT) DESC LIMIT 4';
            $resultLMInfo = $DB->Query($sql);
            while ($part = $resultLMInfo->fetch()) {
                $res[] = $part['PHRASE'];
            }
            
            devlogs($res, "SEARCH_NON_CACHE");
            
            $cache->endDataCache(array("PopularMainSearch" => $res));
        }*/
        
        $res = [
            0 => "топ",
            1 => "боди",
            2 => "велосипедки",
            3 => "шорты"        
        ];
        
        return $res;
    }

    //последние поисковые запросы, которые хранятся в куках
    public function getLastSearch()
    {
        $res = rawurldecode($_COOKIE['LAST_MAIN_SEARCH']);

        if ($res && $res != '') {
            $res = explode(',', $res);

            if (count($res) > 4) {
                array_shift($res);
                setcookie('LAST_MAIN_SEARCH', urlencode($res), time() + (86400 * 30), "/");
            }
            $res = array_reverse($res);

            return $res;
        } else {
            return [];
        }
    }

    // проверяем грамматику через yandex speller
    public function checkValueSearch($textSearch)
    {
        $textQuery = urlencode($textSearch);
        $url = "https://speller.yandex.net/services/spellservice.json/checkText?text=".$textQuery;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $resultCheck = curl_exec($curl);

        $resultCheck = json_decode($resultCheck, true);
        if($resultCheck) {
            // массив в который будут записываться обработанные значения
            $listCurrentText = array();

            // если у нас только одно ошибочное слово
            if(count($resultCheck) == 1) {
                // записываем вариант ошибки
                $falseText = $resultCheck[0]["word"];

                // преобразуем ассоциативный массив в обычный
                $arrValidWord = array_values($resultCheck[0]["s"]);
                /* удаляем однокоренные слова */
                $validWord = $this->clearBySixFirstLetter($arrValidWord);

                foreach($validWord as $word) {
                    $newTextSearch = str_replace($falseText, $word, $textSearch);
                    array_push($listCurrentText, $newTextSearch);
                }
            }
            // если несколько ошибочных слов
            else if(count($resultCheck) > 1) {
                foreach($resultCheck as $arrWord) {
                    $falseText = $arrWord["word"];
                    $textSearch = str_replace($falseText, $arrWord["s"][0], $textSearch);
                }
                array_push($listCurrentText, $textSearch);
            }
            return $listCurrentText;
        }
    }

    // удаляем однокоренные слова
    public function clearBySixFirstLetter($array)
    {
        // записываем корни слов
        $has = [];
        return array_filter(
            $array,
            function ($word) use (&$has) {
                $sixLetters = mb_substr($word, 0, 6);
                if (!in_array($sixLetters, $has)) {
                    array_push($has, $sixLetters);
                    return true;
                }
                return false;
            }
        );
    }

    public function searchCollectionsDB($coincidence, $nPageSize) {

        $res = [];
        $cache = Cache::createInstance();
        if ($cache->initCache(36000, 'CollectionSearchDB|'.implode('|',$coincidence).'|'.$nPageSize)) {
            $res = $cache->getVars();
        } elseif($cache->startDataCache()) {

            $coincidence = array_map(function($value) {
                return "%" . $value . "%";
            }, $coincidence);

            $res = CIBlockElement::GetList(
                ['sort' => 'asc'],
                ['IBLOCK_ID' => '2', 'SECTION_ID' => '19', 'NAME' => $coincidence, 'ACTIVE'=>'Y' ], // 19 - id раздела "новинки"
                false,
                ['nPageSize' => $nPageSize],
                ['ID', 'NAME', 'PREVIEW_PICTURE', 'CATALOG_PRICE', 'PROPERTY_MINIMUM_PRICE', 'DETAIL_PAGE_URL', 'PROPERTY_CML2_ARTICLE']
            );
            $cache->endDataCache($res);
        }
        return $res;

    }

    //тут делаем запрос точно такой же как в компоненте на /search/index.php
    public function searchCollections($coincidence, $nPageSize = 6) {

        global $USER;
        $userId = $USER->getId();
        $res = [];
        $favoriteProductIds = [];

        //достаем товары из wishlist чтобы далее проверять добавлен ли туда уже товар
        if ($USER->IsAuthorized()) {
            $favoriteProductIds = (getUfFavorites())? getUfFavorites() : [];
        }

        $rsCoincidence = $this->searchCollectionsDB($coincidence, $nPageSize);

        //$arArticle = [];
        $arProdIds = [];
        while($elCoincidence = $rsCoincidence->fetch()) {

            //$arArticle[] = $elCoincidence['PROPERTY_ARTICLE_VALUE'];
            $arProdIds[] = $elCoincidence['ID'];

            $picture = CFile::ResizeImageGet($elCoincidence["PREVIEW_PICTURE"], array('width'=>364, 'height'=>546), BX_RESIZE_IMAGE_EXACT, true)['src'];
            $picture = str_replace('http:', 'https:', $picture);
            $res[] = [
                'NAME' => $elCoincidence['NAME'],
                'ID' => $elCoincidence['ID'],
                //'DETAIL_PAGE_URL' => $elCoincidence['DETAIL_PAGE_URL'],
                'DETAIL_PAGE_URL' =>  CIBlock::ReplaceDetailUrl($elCoincidence["DETAIL_PAGE_URL"], $elCoincidence, true, "E"),
                'PRICE' => number_format($elCoincidence['PROPERTY_MINIMUM_PRICE_VALUE'], 0, '', ' '),
                'PREVIEW_PICTURE' => $picture,
                'WISHLIST' => (in_array($elCoincidence['ID'],$favoriteProductIds))?'Y':'N',
                'ARTICLE' => $elCoincidence['PROPERTY_CML2_ARTICLE_VALUE']
            ];
        }

        $colorAssistOb = new Belleyou\ColorAssistant();
        $sortedColor = $colorAssistOb->getСolorList($arProdIds);

        foreach ($res as &$item) {
            $item['SORTED_COLOR'] = $sortedColor[$item['ARTICLE']];
        }
        unset($item);
        
        return $res;
    }

    //тут делаем запрос точно такой же как в компоненте в стандартном компоненте поиска
    public function searchQueryDB($q, $suggestMaxCount, $suggestShowCount)
    {
        $res = [];
        $arFilter = [
            "SITE_ID" => 's1',
            "QUERY" => $q,
            "TAGS" => false,
        ];

        $aSort = [
            "CUSTOM_RANK" => "DESC",
            "TITLE_RANK" => "DESC",
            "RANK" => "DESC",
            "DATE_CHANGE" => "DESC"
        ];

        $exFILTER = [
            0 => [
                '=MODULE_ID' => 'iblock',
                //'PARAM1' => 'clothes',
                'PARAM2' => 2 // 2 - id инфоблока каталог
            ]
        ];

        $allIDs = [];
        $obSearch = new CSearch();
        $res['suggestion'] = [];
        $obSearch->Search($arFilter, $aSort, $exFILTER);
        if ($obSearch->errorno == 0) {
            $obSearch->NavStart(intval($suggestMaxCount), false);
            while ($ar = $obSearch->GetNext()) {
                $res['suggestion'][] = $ar['TITLE'];
                //$allIDs[$ar['ID']] = $ar['ITEM_ID'];
                $allIDs[] = intval($ar['ITEM_ID']);
            }
        }
        $res['elementsId'] = $allIDs;
        $res['countSuggestion'] = count($res['suggestion']);
        $res['suggestion'] = array_unique($res['suggestion']);
        $res['suggestion'] = array_slice($res['suggestion'], 0, intval($suggestShowCount));
        return $res;
    }

    public function recommendationProductIds()
    {
        $res = [];

        $recommendationsPhraseAr = array_map(function($value) {
            return "%" . $value . "%";
        }, $this->arParams['CATEGORIES']);

        $collectionAr =  $this->searchCollectionsDB($recommendationsPhraseAr,(isset($this->arParams["PAGE_RESULT_COUNT"]) ? $this->arParams["PAGE_RESULT_COUNT"] : 50));

        while($elCoincidence = $collectionAr->fetch()) {
            $res[] = $elCoincidence['ID'];
        }

        return $res;
    }

    public function mainSearch($query, $suggestMaxCount, $suggestShowCount = 4)
    {
        global $USER;

        // сначала ищем по сайту
        $res['search'] = $this->searchQueryDB($query, $suggestMaxCount, $suggestShowCount);

        //если ничего не нашли, то проверяем слово через speller
        if ($res['search']['countSuggestion'] == 0) {
            $res['firstNotFound'] = $query;
            $spellerQueryArr = $this->checkValueSearch($query) ?? [];

            // если у speller есть вариант замены нашего запроса, то ищем по каталогу с предложенной фразой
            if (count($spellerQueryArr) > 0) {
                //$res['dev'] = $spellerQueryArr;
                $res['search'] = $this->searchQueryDB($spellerQueryArr[0], $suggestMaxCount, $suggestShowCount);
                $query = $spellerQueryArr[0];

                if ($res['search']['countSuggestion'] == 0) {
                    $res['secondNotFound'] = $spellerQueryArr[0];
                }
            } else {
                $res['secondNotFound'] = $query;
            }
            $res['search']['speller'] = $spellerQueryArr;

            // если поиск не дал результатов, то предложим рекомендации (для страницы поиска)
            if ($this->arParams['SEARCH_PAGE'] == 'Y')
                $res['recommendationIds'] = $this->recommendationProductIds();

        }

        // достаем товары для подборок из /каталог/новинки/, но перед этим сравниваем запрос и массив из заранее подобранных категорий
        $lowQuery = mb_strtolower($query);
        $lowQueryAr = explode(' ', $lowQuery);

        $coincidence = array_intersect($lowQueryAr, $this->arParams['CATEGORIES']);

        $res['collection'] = [];
        //если мы находим совпадения запроса с категориями, то вытаскиваем товары по запросу из /каталог/новинки/
        if ($coincidence) {
            $res['collection'] = $this->searchCollections($coincidence);
            $res['userIsAuthorized'] = ($USER->isAuthorized())?'Y':'N';
        }

        return $res;
    }

    public function searchSuggestionAction()
    {
        $res = [];
        $res = $this->mainSearch($_REQUEST['query'], $_REQUEST['suggestMaxCount']);
        return $res;
    }

    protected function getResult()
    {
        if ($this->arParams['SEARCH_PAGE'] == 'Y' && $_GET['q'])
            $this->arResult["MAIN_SEARCH"] = $this->mainSearch($_GET['q'], $this->arParams['SUGGEST_MAX_COUNT'], $this->arParams['SUGGEST_SHOW_COUNT']);

        $this->arResult["FORM_ACTION"] = htmlspecialcharsbx(str_replace("#SITE_DIR#", SITE_DIR,  $this->arParams["PAGE"]));
        $this->arResult['POPULAR_SEARCH'] = $this->getPopularSearch();
        $this->arResult['LAST_SEARCH'] = $this->getLastSearch();
    }

    public function executeComponent()
    {
        $this->getResult();
        $this->includeComponentTemplate();
    }
}
