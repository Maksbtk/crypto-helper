<?php

namespace Ch\Api\Controller;


use Bitrix\Main\Engine\ActionFilter\Authentication, Bitrix\Main\Data\Cache;

//use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine;

//use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Request;

class Auth extends \Bitrix\Main\Engine\Controller
{

    protected $request;
    protected $postBody;

    function __construct(Request $request = null)
    {
        $this->request = $request;
        $post = file_get_contents('php://input');
        $this->postBody = json_decode($post, TRUE);

        parent::__construct($request);
    }

    public function getDefaultPreFilters(): array
    {
        return [
            new ActionFilter\HttpMethod(
                [ActionFilter\HttpMethod::METHOD_GET, ActionFilter\HttpMethod::METHOD_POST]
            ),
        ];
    }

    public function configureActions()
    {
        return [
            '*' => [
                '-prefilters' => [
                    Authentication::class,
                    Engine\ActionFilter\Csrf::class,
                ]
            ],
        ];
    }

    protected function prepareParams(): bool
    {
        return parent::prepareParams();
    }

    /**
     * @OA\Post(
     *     path="/api/user/authorize/",
     *     summary="Authorize",
     *     description="Authorizes user",
     *     tags={"Авторизация"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="login",
     *                     type="string",
     *                     description="User login"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string",
     *                     description="User password"
     *                 ),
     *                 example={"login": "john.doe", "password": "secret"}
     *             )
     *         )
     *     ),
     * @OA\Response(
     *     response="200",
     *     description="Successful response",
     *     @OA\JsonContent(
     *         @OA\Property(property="status", type="string", example="success"),
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="authorized", type="boolean", example=true),
     *             @OA\Property(property="token", type="string", example="nrblGLXHUDrUvEberqlTXSBOg7DHHqwx")
     *         ),
     *         @OA\Property(property="errors", type="array",
     *             @OA\Items(type="string")
     *         )
     *     )
     * )
     * )
     */

    public function authorizeAction(array $fields = []): array
    {
        global $USER;
        if (!is_object($USER)) $USER = new CUser;
        $arAuthResult = $USER->Login($this->postBody['login'], $this->postBody['password'], "Y");
        return ['authorized' => $arAuthResult === true, 'token' => session_id()];
    }

    /**
     * @OA\Get(
     *     path="/api/user/test/",
     *     summary="test",
     *     description="test",
     *     tags={"Авторизация"},
     * @OA\Response(
     *     response="200",
     *     description="Successful response",
     *     @OA\JsonContent(
     *         @OA\Property(property="status", type="string", example="success"),
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="test", type="string", example="test"),
     *         ),
     *         @OA\Property(property="errors", type="array",
     *             @OA\Items(type="string")
     *         )
     *     )
     * )
     * )
     */

    public function testAction(array $fields = []): array
    {
        return ['hello world'];
    }
    
}
