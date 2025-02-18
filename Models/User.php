<?php

namespace Models;

use Exception;

class User
{
    private const ALLOWED_ROLE = ['user', 'admin'];

    private int $id;
    private string $email;
    private string $passwordEncrypted;
    private string $role;
    private ?int $age;
    private ?string $gender;

    /**
     * Конструктор класса User.
     *
     * @param int $id Идентификатор пользователя.
     * @param string $email Электронная почта пользователя.
     * @param string $passwordEncrypted Зашифрованный пароль пользователя.
     * @param string $role Роль пользователя (по умолчанию 'user').
     * @param int|null $age Возраст пользователя (по умолчанию null).
     * @param string|null $gender Пол пользователя (по умолчанию null).
     */
    public function __construct(int $id, string $email, string $passwordEncrypted, string $role = 'user', int $age = null, string $gender = null)
    {
        $this->__set('id', $id);
        $this->__set('email', $email);
        $this->__set('passwordEncrypted', $passwordEncrypted);
        $this->__set('role', $role);
        $this->__set('age', $age);
        $this->__set('gender', $gender);
    }

    /**
     * Проверяет, является ли указанный ID допустимым
     *
     * @param int $id Идентификатор пользователя
     *
     * @return bool True, если ID допустим, иначе false
     */
    public static function isValidId(int $id): bool
    {
        return is_int($id) && $id > 0;
    }

    /**
     * Проверяет, является ли указанный email допустимым
     *
     * @param string $email Адрес электронной почты для проверки
     *
     * @return bool True, если email допустим, иначе false
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) && mb_strlen($email, 'UTF-8') <= 150;
    }

    /**
     * Проверяет, является ли указанный пароль допустимым
     *
     * @param string $password Пароль для проверки
     *
     * @return bool True, если пароль допустим, иначе false
     */
    public static function isValidPassword(string $password): bool
    {
        return mb_strlen($password, 'UTF-8') <= 255;
    }

    /**
     * Проверяет, является ли указанная роль допустимой
     *
     * @param string $role Роль для проверки
     *
     * @return bool True, если роль допустима, иначе false
     */
    public static function isValidRole(string $role): bool
    {
        return in_array($role, self::ALLOWED_ROLE, true);
    }

    /**
     * Проверяет, является ли указанное значение возраста допустимым
     *
     * @param int|null $age Возраст для проверки
     *
     * @return bool|null True, если возраст допустим, null если возраст не указан
     */
    public static function isValidAge(int|null $age): ?bool
    {
        return is_int($age) && $age >= 0 || $age === null;
    }

    /**
     * Проверяет, является ли указанное значение пола допустимым
     *
     * @param string|null $gender Пол для проверки
     *
     * @return bool|null True, если пол допустим, null если пол не указан
     */
    public static function isValidGender(string|null $gender): ?bool
    {
        return in_array($gender, ['male', 'female'], true) || $gender === null;
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
}
