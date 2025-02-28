<?php

namespace Models;

use Core\App;
use Core\Config;
use Core\Db;
use Core\Helper;
use Exception;
use PDOException;

class User
{
    private const DB_NAME = 'user';
    private const ALLOWED_ROLE = ['user', 'admin'];

    private int $id;
    private string $email;
    private string $passwordEncrypted;
    private string $role;
    private ?int $age;
    private ?string $gender;

    /**
     * Проверяет, является ли указанный ID допустимым
     *
     * @param mixed $id Идентификатор пользователя
     *
     * @return bool True, если ID допустим, иначе false
     */
    public function isValidId(mixed $id): bool
    {
        return is_int($id) && $id > 0;
    }

    /**
     * Проверяет, является ли указанный email допустимым
     *
     * @param mixed $email Адрес электронной почты для проверки
     *
     * @return bool True, если email допустим, иначе false
     */
    public function isValidEmail(mixed $email): bool
    {
        return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) && mb_strlen($email, 'UTF-8') <= 150;
    }

    /**
     * Проверяет, является ли указанный пароль допустимым
     *
     * @param mixed $password Пароль для проверки
     *
     * @return bool True, если пароль допустим, иначе false
     */
    public function isValidPassword(mixed $password): bool
    {
        return is_string($password) && mb_strlen($password, 'UTF-8') <= 255;
    }

    /**
     * Проверяет, является ли указанная роль допустимой
     *
     * @param mixed $role Роль для проверки
     *
     * @return bool True, если роль допустима, иначе false
     */
    public function isValidRole(mixed $role): bool
    {
        return is_string($role) && in_array($role, self::ALLOWED_ROLE, true);
    }

    /**
     * Проверяет, является ли указанное значение возраста допустимым
     *
     * @param mixed $age Возраст для проверки
     *
     * @return bool|null True, если возраст допустим, null если возраст не указан
     */
    public function isValidAge(mixed $age): ?bool
    {
        return is_int($age) && $age >= 0 || $age === null;
    }

    /**
     * Проверяет, является ли указанное значение пола допустимым
     *
     * @param mixed $gender Пол для проверки
     *
     * @return bool|null True, если пол допустим, null если пол не указан
     */
    public function isValidGender(mixed $gender): ?bool
    {
        return is_string($gender) && in_array($gender, ['male', 'female'], true) || $gender === null;
    }

    /**
     * Устанавливает значение поля id
     *
     * @param int $id Значение для установки
     *
     * @throws Exception Если значение недопустимо
     */
    private function setId(int $id): void
    {
        if (!self::isValidId($id)) {
            $textError = 'Поле id должно быть целое число больше 0.';

            throw new Exception($textError);
        }

        $this->id = $id;
    }

    /**
     * Устанавливает значение поля email
     *
     * @param string $email Значение для установки
     *
     * @throws Exception Если значение недопустимо
     */
    private function setEmail(string $email): void
    {
        if (!self::isValidEmail($email)) {
            $textError = 'Поле email должно быть корректным email-адресом и длинной не более 150 символов.';

            throw new Exception($textError);
        }

        $this->email = $email;
    }

    /**
     * Устанавливает значение поля passwordEncrypted
     *
     * @param string $password Значение для установки
     *
     * @throws Exception Если значение недопустимо
     */
    private function setPasswordEncrypted(string $password): void
    {
        if (!self::isValidPassword($password)) {
            $textError = 'Поле password должно быть не более 255 символов.';

            throw new Exception($textError);
        }

        $this->passwordEncrypted = $password;
    }

    /**
     * Устанавливает значение поля role
     *
     * @param string $role Значение для установки
     *
     * @throws Exception Если значение недопустимо
     */
    private function setRole(string $role): void
    {
        if (!self::isValidRole($role)) {
            $textError = 'Поле role должно быть user или admin.';

            throw new Exception($textError);
        }

        $this->role = $role;
    }

    /**
     * Устанавливает значение поля age
     *
     * @param int|null $age Значение для установки
     *
     * @throws Exception Если значение недопустимо
     */
    private function setAge(int|null $age): void
    {
        if (!self::isValidAge($age)) {
            $textError = 'Поле age должно быть целое число больше 0.';

            throw new Exception($textError);
        }

        $this->age = $age;
    }

    /**
     * Устанавливает значение поля gender
     *
     * @param string|null $gender Значение для установки
     *
     * @throws Exception Если значение недопустимо
     */
    private function setGender(string|null $gender): void
    {
        if (!self::isValidGender($gender)) {
            $textError = 'Поле gender должно быть male или female.';

            throw new Exception($textError);
        }

        $this->gender = $gender;
    }

    /**
     * Магический метод для установки значения свойства.
     *
     * @param string $name Имя свойства для установки.
     * @param string|int|null $value Значение для установки свойства.
     *
     * @return void
     */
    public function __set(string $name, string|int|null $value): void
    {
        switch ($name) {
            case 'id':
                $this->setId($value);
                break;

            case 'email':
                $this->setEmail($value);
                break;

            case 'passwordEncrypted':
                $this->setPasswordEncrypted($value);
                break;

            case 'role':
                $this->setRole($value);
                break;

            case 'age':
                $this->setAge($value);
                break;

            case 'gender':
                $this->setGender($value);
                break;

            default:
                if (isset($this->$name)) {
                    $this->$name = $value;
                }
        }
    }

    /**
     * Магический метод для получения значения свойства объекта.
     *
     * @param string $name Имя свойства, значение которого нужно получить.
     * @return string|int|null Значение свойства, если оно существует, или null, если свойства не существует.
     */
    public function __get(string $name): string|int|null
    {
        return $this->$name;
    }

    /**
     * Возвращает список пользователей с указанными колонками.
     *
     * @param string ...$columns Перечисление колонок, которые необходимо выбрать.
     * @return array<int, array<string, mixed>> Массив пользователей, где каждый пользователь представлен в виде ассоциативного массива.
     */
    public function list(...$columns): array
    {
        $data = Db::findBy(self::DB_NAME, $columns, Config::getConfig('database.dbColumns.user'));
        $userSystem = Config::getConfig('app.idUserSystem');

        if (isset($data[$userSystem])) {
            unset($data[$userSystem]);
            $data = array_values($data);
        }

        return $data;
    }

    /**
     * Получает данные пользователя из базы данных по заданным параметрам.
     *
     * @param array<string, mixed> $params Ассоциативный массив параметров для поиска пользователя.
     * 
     * @return bool Возвращает true, если пользователь найден и данные успешно загружены, иначе false.
     */
    public function get(array $params): bool
    {
        $data = Db::findOneBy(self::DB_NAME, $params, Config::getConfig('database.dbColumns.user'));

        if ($data === null || $data['id'] === Config::getConfig('app.idUserSystem')) {
            return false;
        }

        foreach ($data as $key => $value) {
            $this->__set($key, $value);
        }

        return true;
    }

    /**
     * Создает нового пользователя в базе данных и создает домашнюю папку для пользователя.
     *
     * @return array<string, string|int> Возвращает массив с информацией о статусе операции, идентификаторе пользователя и идентификаторе папки,
     * или массив с ошибкой.
     * @throws Exception Если произошла ошибка при выполнении запроса к базе данных.
     */
    public function create(): array
    {
        $setParams = [
            'email' => $this->email,
            'passwordEncrypted' => $this->passwordEncrypted,
            'role' => $this->role,
            'age' => $this->age,
            'gender' => $this->gender,
        ];

        $connection = Db::$connection;
        $connection->beginTransaction();

        try {
            $id = Db::insert(self::DB_NAME, $setParams, Config::getConfig('database.dbColumns.user'));
            $folder = App::getService('folder');
            $folder->create((int) $id, Config::getConfig('app.idUserSystem'), 'home');

            $connection->commit();
        } catch (PDOException $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            $errorCode = $e->getCode();

            if ($errorCode === '23000') {
                $error = Helper::showError();
                $error['code'] = $errorCode;

                return $error;
            }

            throw new Exception(__CLASS__ . ': ' . $e->getMessage());
        }

        return [
            'status' => 'ok',
            'id' => $id,
            'folderId' => $folder->id,
        ];
    }

    /**
     * Обновляет данные пользователя в базе данных.
     *
     * @return array Ассоциативный массив с результатом обновления.
     */
    public function update(): array
    {
        $setParams = [
            'email' => $this->email,
            'passwordEncrypted' => $this->passwordEncrypted,
            'role' => $this->role,
            'age' => $this->age,
            'gender' => $this->gender,
        ];

        return Db::updateOneBy(self::DB_NAME, $setParams, ['id' => $this->id], Config::getConfig('database.dbColumns.user'));
    }

    /**
     * Удаляет пользователя и связанные с ним файлы и папки из базы данных.
     *
     * Метод выполняет следующие действия:
     * 1. Получает список файлов, связанных с пользователем.
     * 2. Начинает транзакцию.
     * 3. Удаляет записи о файлах, папках и пользователе из базы данных.
     * 4. Фиксирует транзакцию, если все операции прошли успешно.
     * 5. В случае ошибки откатывает транзакцию и выбрасывает исключение.
     * 6. Удаляет файлы из хранилища, если список файлов не пуст.
     *
     * @throws Exception Если произошла ошибка при удалении данных из базы данных.
     */
    public function delete(): void
    {
        $filesList = [];
        $filesList = App::getService('file')->list($this->id);

        $connection = Db::$connection;
        $connection->beginTransaction();

        try {
            Db::deleteOneBy('file', ['userId' => $this->id], Config::getConfig('database.dbColumns.file'));
            Db::deleteOneBy('folder', ['userId' => $this->id], Config::getConfig('database.dbColumns.folder'));
            Db::deleteOneBy(self::DB_NAME, ['id' => $this->id], Config::getConfig('database.dbColumns.user'));

            $connection->commit();
        } catch (Exception $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw new Exception(__CLASS__ . ': ' . $e->getMessage());
        }

        if (count($filesList) === 0) {
            return;
        }

        App::getService('fileStorage')->deleteFiles($filesList);
    }

    /**
     * Выполняет вход пользователя по указанным email и паролю.
     *
     * @param string $email Электронная почта пользователя.
     * @param string $password Пароль пользователя.
     * @return bool Возвращает true, если вход выполнен успешно, иначе false.
     */
    public function login(string $email, string $password): bool
    {
        if (!$this->get(['email' => $email]) || !password_verify($password, $this->passwordEncrypted)) {
            return false;
        }

        if (password_needs_rehash($this->passwordEncrypted, PASSWORD_DEFAULT)) {
            $this->passwordEncrypted = password_hash($password, PASSWORD_DEFAULT);
            $this->updatePasswordById();
        }

        return true;
    }

    /**
     * Метод для валидации параметров пользователя.
     *
     * @param array<string, mixed> $params Ассоциативный массив параметров для валидации.
     * 
     * @return array<int, string> Массив ошибок, если они есть.
     */
    public function allValidation(array $params, bool $isNotSelf = false): array
    {
        $errors = [];

        foreach (Config::getConfig('database.dbColumns.user') as $parameter) {
            switch ($parameter) {
                case 'email':
                    if (!isset($params['email'])) {
                        $errors[] = 'Поле email обязательно для заполнения.';
                        break;
                    }

                    if (!$isNotSelf && $this->role !== 'admin' && $this->email !== $params['email']) {
                        $errors[] = 'Только администратор может изменять email.';
                    }

                    try {
                        $this->__set('email', trim($params['email']));
                    } catch (Exception $e) {
                        $errors[] = $e->getMessage();
                    }
                    break;

                case 'passwordEncrypted':
                    if (!isset($params['password'])) {
                        $errors[] = 'Поле password обязательно для заполнения.';
                        break;
                    }

                    try {
                        $this->__set('passwordEncrypted', password_hash($params['password'], PASSWORD_DEFAULT));
                    } catch (Exception $e) {
                        $errors[] = $e->getMessage();
                    }
                    break;

                case 'role':
                    if (!isset($params['role'])) {
                        $errors[] = 'Поле role обязательно для заполнения.';
                        break;
                    }

                    if (!$isNotSelf && $this->role !== 'admin' && $this->role !== $params['role']) {
                        $errors[] = 'Только администратор может изменять role.';
                        break;
                    }

                    try {
                        $this->__set('role', trim($params['role']));
                    } catch (Exception $e) {
                        $errors[] = $e->getMessage();
                    }
                    break;

                case 'age':
                    if (!isset($params['age']) || $params['age'] === null) {
                        $this->age = null;
                        break;
                    }

                    $age = $params['age'];

                    if (!preg_match('/^\d+$/', $age) || $age < 0) {
                        $errors[] = 'Поле age должно быть целое число больше 0.';
                        break;
                    }

                    try {
                        $this->__set('age', (int) trim($age));
                    } catch (Exception $e) {
                        $errors[] = $e->getMessage();
                    }
                    break;

                case 'gender':
                    if (!isset($params['gender'])) {
                        $this->gender = null;
                        break;
                    }

                    try {
                        $this->__set('gender', trim($params['gender']));
                    } catch (Exception $e) {
                        $errors[] = $e->getMessage();
                    }
                    break;
            }
        }

        return $errors;
    }

    /**
     * Обновляет зашифрованный пароль пользователя в базе данных по его идентификатору.
     *
     * @return void
     */
    private function updatePasswordById(): void
    {
        Db::updateOneBy(self::DB_NAME, ['passwordEncrypted' => $this->passwordEncrypted], ['id' => $this->id], Config::getConfig('database.dbColumns.user'));
    }
}
