<?php
use Bitrix\Main\Loader;
use \Bitrix\Main,
    \Bitrix\Sale\Internals;
use Bitrix\Sale\DiscountCouponsManager;
use Bitrix\Highloadblock\HighloadBlockTable as HLBT;
use Bitrix\Sale\Payment;
use Bitrix\Sale;
use \Bitrix\Main\Context;
use \Bitrix\Main\EventManager;
$eventManager = EventManager::getInstance();

\CModule::IncludeModule('highloadblock');
\CModule::IncludeModule('iblock');

\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleOrderPaid',
    'CHOrderHandlers::onOrderPaid'
);

class CHOrderHandlers
{
    #При оплате заказа
    public static function onOrderPaid(\Bitrix\Main\Event $event)
    {
        devlogs( date("d.m.y H:i:s") . '____________', 'onOrderPaid');

        $order = $event->getParameter("ENTITY");

        // Проверяем, оплачен ли заказ
        if (!$order->isPaid() || $order->isPaid() == false) {
            devlogs($order->getId() . ' заказ не оплачен', 'onOrderPaid');
            return;
        }

        $basket = $order->getBasket();
        //$basketStr = 'Заказ №' .$order->getId() . ' оплачен!<br><br>';
        $prodId = null;
        $basketStr = '';
        // Получаем первый товар из корзины (можно адаптировать под нужды)
        foreach ($basket as $basketItem) {
            $prodId = $basketItem->getProductId();
            $basketStr .= $basketItem->getField('NAME') . '(' . $prodId . ')' . '<br />';
            break; // Используем только первый товар
        }

        devlogs('Заказ ' . $order->getId() . '<br>' . $basketStr, 'onOrderPaid');
        $buyerId = $order->getUserId();

        // Вызываем функцию для назначения прав пользователю
        $dates = self::grantingRightsToBuyer($buyerId, $prodId);
        self::sendSuccessMail($dates, $basketStr, $order);
    }

    public static function grantingRightsToBuyer($buyerId, $prodId)
    {
        $res = [];
        // Подключаем модуль инфоблоков
        \Bitrix\Main\Loader::includeModule('iblock');

        // Получаем товар по ID и его свойства DAYS_COUNT и ID_USER_GROUP
        $rs = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => '4', 'ID' => $prodId],
            false,
            false,
            ['ID', 'PROPERTY_DAYS_COUNT', 'PROPERTY_ID_USER_GROUP', 'NAME']
        );

        // Получаем значение DAYS_COUNT и ID группы
        if ($product = $rs->Fetch()) {
            $daysCount = (int)$product['PROPERTY_DAYS_COUNT_VALUE'];
            $groupId = (int)$product['PROPERTY_ID_USER_GROUP_VALUE'];
        } else {
            devlogs("Не удалось получить информацию о товаре", 'onOrderPaid');
            return;
        }

        if ($daysCount > 0) {
            // Получаем все группы пользователя с датами активности
            $userGroupsOb = \CUser::GetUserGroupList($buyerId);
            $arGroups = [];
            $currentDateTo = null;
            $currentDateFrom = null;

            // Флаг для проверки существования группы
            $groupExists = false;

            // Обрабатываем существующие группы
            while ($arGroup = $userGroupsOb->Fetch()) {
                if ((int)$arGroup['GROUP_ID'] === $groupId) {
                    $groupExists = true;
                    // Если есть установленная дата окончания активности
                    if ($arGroup['DATE_ACTIVE_TO']) {
                        $currentDateTo = $arGroup['DATE_ACTIVE_TO'];
                        $currentDateFrom = $arGroup['DATE_ACTIVE_FROM'];
                    }
                }
                // Добавляем существующие группы в массив
                $arGroups[] = $arGroup;
            }

            $currentDate = date('d.m.Y H:i:s');

            // Если дата окончания активности уже прошла, обновляем с текущей даты
            if ($currentDateTo && strtotime($currentDateTo) < strtotime($currentDate)) {
                $newDateTo = date('d.m.Y H:i:s', strtotime("+{$daysCount} days"));
                $newDateFrom = $currentDate;
            } else {
                // Если дата окончания ещё актуальна, прибавляем к ней $daysCount
                if ($currentDateTo) {
                    $newDateTo = date('d.m.Y H:i:s', strtotime($currentDateTo . " +{$daysCount} days"));
                    $newDateFrom = $currentDateFrom;
                } else {
                    // Если группа не имела установленной даты
                    $newDateTo = date('d.m.Y H:i:s', strtotime("+{$daysCount} days"));
                    $newDateFrom = $currentDate;
                }
            }

            $res['from'] = $newDateFrom;
            $res['to'] = $newDateTo;

            // Обновляем группу с датами активности
            if ($groupExists) {
                foreach ($arGroups as &$group) {
                    if ((int)$group['GROUP_ID'] === $groupId) {
                        $group['DATE_ACTIVE_FROM'] = $newDateFrom;
                        $group['DATE_ACTIVE_TO'] = $newDateTo;
                    }
                }
            } else {
                // Добавляем новую группу
                $arGroups[] = [
                    'GROUP_ID' => $groupId,
                    'DATE_ACTIVE_FROM' => $newDateFrom,
                    'DATE_ACTIVE_TO' => $newDateTo
                ];
            }

            // Обновляем группы пользователя с датами через CUser::SetUserGroup()
            \CUser::SetUserGroup($buyerId, array_map(function($group) {
                return $group['GROUP_ID'];
            }, $arGroups));

            // Обновляем группы с датами через массив
            \CUser::SetUserGroup($buyerId, $arGroups);

            // Логируем результат
            devlogs("Пользователь ID {$buyerId} добавлен/обновлен в группе {$groupId} на {$daysCount} дней до {$newDateTo}", 'onOrderPaid');
        } else {
            devlogs("Не удалось получить DAYS_COUNT для товара ID {$prodId}", 'onOrderPaid');
        }

        return $res;
    }
        #Отправка почты после успешного оформления заказа
        public static function sendSuccessMail($dates, $basketStr, $order)
        {
            $fields = [
                'SALE_EMAIL' => 'maksvasa1998@yandex.ru',
                'BCC' => 'maksvasa1998@yandex.ru',
                'ORDER_ID' => $order->getId(),
                'EMAIL' => $order->getField('USER_EMAIL'),
            ];

            if ($dates['from'] && $dates['to']) {
                $fields['PERIUD'] = 'Подписка действительна с' . $dates['from'] . ' до '  . $dates['to'];
            } else {
                $fields['PERIUD'] = 'К сожалению не удалось активировать подписку по технической причине, свяжитесь со службой поддержки';
            }

            if ($basketStr) {
                $fields['PRODUCT'] = $basketStr;
            }

            devlogs($fields, 'onOrderPaid');

            \Bitrix\Main\Mail\Event::send([  // или sendImmediate
                "EVENT_NAME" => "SALE_ORDER_PAID",
                "LID" => "s1",
                "C_FIELDS" => $fields,
            ]);

        }
}

