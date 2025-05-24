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
$aiModel = strtolower(trim($payload['aiModel'] ?? 'deepseek')); // 'gpt' или 'deepseek'

// Определяем тип анализа
$promptIntro = $payload['promptIntro'] ?? '';
$isLossAnalysis = mb_stripos($promptIntro, 'убыточных') !== false;

// Формируем сообщения
$messages = [
    ['role' => 'system', 'content' => 'Ты — эксперт по алгоритмической торговле. Дай подробные рекомендации.'],
];

if ($isLossAnalysis) {
    $messages[] = ['role' => 'user', 'content' => buildLossPrompt($payload['trades'], $payload['filters'], $promptIntro)];
} else {
    $messages[] = ['role' => 'user', 'content' => buildCommonPrompt($payload['trades'], $payload['filters'], $promptIntro)];
}

// Обработка по модели
try {
    if ($aiModel === 'deepseek') {
        $api = new \Maksv\DeepSeek\Request();
        $response = $api->chatCompletion($messages);
        $analysis = trim($response['choices'][0]['message']['content'] ?? '');
    } else {
        // GPT по умолчанию
        $api = new \Maksv\Openapi\Request();
        $body = $api->buildBody('gpt-4', $messages, 0.8);
        $data = $api->httpReq($body, true, 518400);
        $analysis = trim($data['choices'][0]['message']['content'] ?? '');
    }

    echo Json::encode(['analysis' => $analysis, 'messages' => $messages]);
} catch (\Exception $e) {
    http_response_code(500);
    echo Json::encode(['error' => $e->getMessage(), 'payload' => $payload]);
    exit;
}

/**
 * Формирует текст запроса для общего анализа сделок.
 */
function buildCommonPrompt(array $trades, array $filters): string {
    // Разбираем список сделок
    $lines = [];
    foreach ($trades as $t) {
        $lines[] = implode('|', [
            $t['symbolName'],
            $t['direction'] ?: 'all',
            $t['tf'],
            $t['tpCount'],
            $t['risk'].'%',
            $t['profit_percent'].'%/'.$t['profit'].'$'
        ]);
    }
    $tradesText = "Список сделок (контракт|направление|таймфрейм|TP достигнуто|риск|профит):\n"
        . implode("\n", array_map(fn($l) => "– {$l}", $lines));

    // Описываем фильтры
    $filtersText = "Текущие фильтры: " . Json::encode($filters, JSON_UNESCAPED_UNICODE);

    // Инструкция для модели
    $instruction ="
        Ты — эксперт по алгоритмической торговле.  
        1) Проанализируй каждый пункт из списка сделок,  
        2) Дай рекомендации по настройкам ATR-множителей (tpFilter и tpCountGeneral),  
        3) Оцени риск-параметр и предложи, стоит ли его уменьшить или увеличить,  
        4) Посмотри на направление (long/short) и скажи, что лучше брать: оба направления или только одно,  
        5) Предложи, как скорректировать фильтры для увеличения профита.  
        
        Если предлагаешь вариант тестирования, опиши шаги, чтобы я мог воспроизвести проверку. Ответь структурировано, по пунктам. RU";

    return implode("\n\n", [$tradesText, $filtersText, $instruction]);
}

/**
 * Формирует текст запроса для анализа убыточных сделок.
 */
function buildLossPrompt(array $trades, array $filters, string $intro): string {
    // Шапка с вводным текстом
    $header = trim($intro) . "\n\nПроанализируй подробно каждую убыточную сделку:\n";

    // Собираем описания только отрицательных
    $details = '';
    foreach ($trades as $t) {
        if (!isset($t['profit']) || $t['profit'] >= 0) {
            continue;
        }
        $allInfo = Json::encode($t['allInfo'], JSON_UNESCAPED_UNICODE);
        $details .= "• {$t['symbolName']} | {$t['direction']} | {$t['tf']} | TP: {$t['tpCount']} | риск: {$t['risk']}% | профит: {$t['profit_percent']}%/{$t['profit']}$\n"
            . "  allInfo: {$allInfo}\n\n";
    }

    // Инструкция
    $instruction = "
        Для каждой сделки:
          а) Опиши, что пошло не так в техническом анализе (на основе allInfo),  
          б) Объясни, почему не сработали тейк-профиты или сработал стоп-лосс,  
          в) Дай конкретные рекомендации по исправлению и тестированию.  
        
        Структурируй ответ по сделкам. RU";

    return $header . $details . $instruction;
}

