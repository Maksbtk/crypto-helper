<?php

use Bitrix\Main\Routing\RoutingConfigurator;

cmodule::includemodule('ch.api');

return function (RoutingConfigurator $router) {
    /**
     * Методы авторизации
     */
    $router->post('/api/user/authorize/', [\Ch\Api\Controller\Auth::class, 'authorize']);
    $router->get('/api/user/test/', [\Ch\Api\Controller\Auth::class, 'test']);
};
