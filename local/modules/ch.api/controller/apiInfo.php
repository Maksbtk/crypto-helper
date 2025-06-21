<?php

namespace Ch\Api\Controller;
/**
 * @OA\Info(
 *     title="Crypto Helper backend API",
 *     version="1.0",
 *     description="Описание API сайта ...<br /><br />Внимание! Для закрытых методов требуется передача заголовка X-Jwt-Auth с токеном, полученным из авторизации.",
 * ),
 * @OA\SecurityScheme(
 *      securityScheme="jwt",
 *      type="apiKey",
 *      in="header",
 *      name="X-Jwt-Auth",
 *      bearerFormat="JWT",
 * ),
 */
class ApiInfo extends \Bitrix\Main\Engine\Controller{}