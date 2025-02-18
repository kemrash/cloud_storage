<?php

namespace Controllers;

use Core\App;
use Core\Helper;
use Core\Request;
use Core\Response;
use Core\Response\AccessDeniedResponse;
use Core\Response\JSONResponse;
use Core\Response\PageNotFoundResponse;
use Models\User;
use Traits\UserTrait;

class UserController
{
    use UserTrait;

    /**
     * Возвращает список пользователей в формате JSON.
     *
     * @return JSONResponse Ответ с данными в формате JSON.
     */
    public function list(): Response
    {
        $data = App::getService('userService')->getUsersList();
        $response = new JSONResponse($data);

        return $response;
    }

    /**
     * Получает данные пользователя по его идентификатору.
     *
     * @param array{0: string} $params Массив параметров, где первый элемент - идентификатор пользователя.
     * @return Response Ответ в формате JSON с данными пользователя или ответ с ошибкой "Страница не найдена".
     */
    public function get(array $params): Response
    {
        $data = App::getService('userService')->getUserById($params[0]);

        if ($data === null) {
            return new PageNotFoundResponse();
        }

        return new JSONResponse($data);
    }

    /**
     * Обновляет данные пользователя.
     *
     * @param Request $request Объект запроса, содержащий данные для обновления.
     * 
     * @return Response Ответ, указывающий на результат операции.
     */
    public function update(Request $request): Response
    {
        if (!isset($_SESSION['id']) || isset($request->getData()['PUT']['id']) && $_SESSION['id'] !== (int) $request->getData()['PUT']['id']) {
            return new AccessDeniedResponse();
        }

        return App::getService('userService')->updateUser($request->getData()['PUT'], (int) $_SESSION['id'], $_SESSION['role']);
    }

    /**
     * Метод для авторизации пользователя.
     *
     * @param Request $request Объект запроса, содержащий данные для авторизации.
     * 
     * @return Response JSON-ответ с результатом авторизации.
     * 
     * Метод проверяет наличие и корректность обязательных полей 'email' и 'password' в запросе.
     * Если одно из полей отсутствует или некорректно, возвращается ошибка с кодом 400.
     * Если данные корректны, вызывается метод loginUser сервиса userService.
     * В случае успешной авторизации, данные пользователя сохраняются в сессии.
     * Если авторизация не удалась, возвращается ошибка с кодом 401.
     */
    public function login(Request $request): Response
    {
        $email = null;
        $password = null;
        $requestParams = $request->getData()['POST'];

        if (isset($requestParams['email']) && User::isValidEmail($requestParams['email'])) {
            $email = $requestParams['email'];
        }

        if (isset($requestParams['password']) && User::isValidPassword($requestParams['password'])) {
            $password = $requestParams['password'];
        }

        if ($email === null || $password === null) {
            return new JSONResponse(Helper::showError('Не все обязательные поля заполнены, или их значения не корректны'), 400);
        }

        App::getService('userService')->loginUser($email, $password);

        if (!App::issetClass('user')) {
            return new JSONResponse(Helper::showError('Неправильный логин или пароль'), 401);
        }

        $user = App::getService('user');

        $_SESSION['id'] = $user->id;
        $_SESSION['role'] = $user->role;

        return new JSONResponse();
    }

    /**
     * Завершает текущую сессию пользователя и возвращает JSON-ответ.
     *
     * @return JSONResponse JSON-ответ, подтверждающий завершение сессии.
     */
    public function logout(): Response
    {
        App::getService('session')->destroySession();

        return new JSONResponse();
    }

    /**
     * Выполняет поиск пользователя по email.
     *
     * @param array{0: string} $params Массив параметров, где первый элемент - email пользователя.
     * @return Response Возвращает объект ответа.
     */
    public function searchByEmail(array $params): Response
    {
        if ($response = $this->checkUserAuthorization()) {
            return $response;
        }

        return App::getService('userService')->searchUserByEmail($params[0]);
    }
}
