<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

Loader::includeModule('main');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo Json::encode(['error'=>'Метод не разрешён']);
    exit;
}

$payload = Json::decode(file_get_contents('php://input'));
if (empty($payload['trades']) || !is_array($payload['trades'])) {
    http_response_code(400);
    echo Json::encode(['error'=>'Некорректные данные']);
    exit;
}

// Определяем тип анализа по intro
$promptIntro = isset($payload['promptIntro']) ? $payload['promptIntro'] : '';
$isLossAnalysis = mb_stripos($promptIntro, 'убыточных') !== false;

// Формируем список сообщений для OpenAI
$messages = [
    ['role'=>'system', 'content'=>'Ты — эксперт по алгоритмической торговле. Дай подробные рекомендации.'],
];

if ($isLossAnalysis) {
    $messages[] = ['role'=>'user', 'content'=> buildLossPrompt($payload['trades'], $payload['filters'], $promptIntro)];
} else {
    $messages[] = ['role'=>'user', 'content'=> buildCommonPrompt($payload['trades'], $payload['filters'], $promptIntro)];
}

// Инициализируем Request и выполняем запрос
try {
    $api = new \Maksv\Openapi\Request();
    // выбираем модель и доп. параметры (можно расширить UI, чтобы выбирать динамически)
    $body = $api->buildBody('gpt-4', $messages, 0.8);
    //$body = $api->buildBody('gpt-3.5-turbo', $messages, 0.8);
    //$body = $api->buildBody('gpt-4-turbo', $messages, 1);

    $data = $api->httpReq($body , true, 518400);
    $analysis = $data['choices'][0]['message']['content'];
    //$analysis = '';
    echo Json::encode(['analysis'=>$analysis, 'messages' => $messages]);
} catch(\Exception $e) {
    http_response_code(500);
    echo Json::encode(['error'=>$e->getMessage()]);
    exit;
}

/**
 * Формирует текст промпта из массива сделок и фильтров.
 */
function buildCommonPrompt(array $trades, array $filters): string {
    $text = "Отфильтрованные сделки в формате: (контракт|направление|таймфрейм|Количество достигнутых TP|риск%|профит(%/$)\n";
    foreach($trades as $t){
        $text .= "({$t['symbolName']}|{$t['direction']}|{$t['tf']}|{$t['tpCount']}|{$t['risk']}%|{$t['profit_percent']}%/{$t['profit']}$)\n";
    }
    $text .= "\nФильтры: ". Json::encode($filters, JSON_UNESCAPED_UNICODE). "|В tpFilter лежит массив множителей ATR тейк профитов, в tpCountGeneral лежит выбранное количество тейк профитов,выбранных в текущем фильтре, например если tpCountGeneral = 2 то из tpFilter мы берем первые два множителя ATR.direction- это направление long/short, если пустое то значит все. amountInTrade - сумма в сделке,entry = вход в сделку по рынку или по расчитонной рекомендуемой цене (y/n). moveeSLafterReachingTP = значение TP после которого двигаем SL\n";
    $text .= "Проанализируй эти сделки САМ (НЕ ПРЕДЛАГАЙ МНЕ ПОДХОДЫ ДЛЯ АНАЛИЗА), ДАЙ СТРУКТУРИРОВАННЫЙ ОТВЕТ. дай конкретные рекомендации как улучшить стратегию торговли. В каждой сделке лежит сколько TP сделка достигла (Количество достигнутых TP), на основе этой попробуй выдать рекомендации снижать или увеличивать множители у ATR, снижать или увеличивать общее количество TP (tpCountGeneral, если значение стоит например 2, то 2 тейк профит это конечный тейк профит). также, в каждой сделке лежит риск в процентах, тоже проанализируй и скажи обрезать ли сделки по риску или наоборот можно попробовать увеличить риск. обративнимание что я могу передавать направление сделки (могу отдавть только лонги или только шорты или все), порекомендуй мне брать ли и лонги и шорты, или что то одно будет работать лучше. посмотри на все параметры которые есть, попробуй поискать своими силами что можно улучшить, предложения выдвигай только по существу на основе тех данных которые есть, предложи как поменять фильтры чтобы получить больший профит. если будешь выдвигать предложения по анализу распиши конкретные действия что мне нужно сделать чтобы проверить твою теорию. ответ нужен на русском языке.";

    return $text;
}

function buildLossPrompt(array $trades, array $filters, string $intro): string {
    $text = $intro . "\nПроанализируй подробно следующие убыточные сделки (все, которые я передал). Для каждой сделки приведи полный анализ: что было неверно в техническом анализе (allInfo), почему не сработали тейк-профиты или сработал стоп-лосс, и что можно улучшить. Структурируй ответ по сделкам. Ответ нужен на русском языке\n";
    foreach($trades as $t){
        if (isset($t['profit']) && $t['profit'] < 0) {
            $allInfo = Json::encode($t['allInfo'], JSON_UNESCAPED_UNICODE);
            $text .= "Сделка: {$t['symbolName']} | направление: {$t['direction']} | tf: {$t['tf']} | достигнутые TP: {$t['tpCount']} | риск: {$t['risk']}% | профит: {$t['profit_percent']}%/{$t['profit']}$ |\n     allInfo: {$allInfo}\n\n";
        }
    }
    return $text;
}

