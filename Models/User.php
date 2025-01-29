<?php

namespace Models;

use Core\Helper;
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

    public function __construct(int $id, string $email, string $passwordEncrypted, string $role, int $age = null, string $gender = null)
    {
        $this->__set('id', $id);
        $this->__set('email', $email);
        $this->__set('passwordEncrypted', $passwordEncrypted);
        $this->__set('role', $role);
        $this->__set('age', $age);
        $this->__set('gender', $gender);
    }

    public static function isValidId(int $id): bool
    {
        return is_int($id) && $id > 0;
    }

    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) && mb_strlen($email, 'UTF-8') <= 150;
    }

    public static function isValidPassword(string $password): bool
    {
        return mb_strlen($password, 'UTF-8') <= 255;
    }

    public static function isValidRole(string $role): bool
    {
        return in_array($role, self::ALLOWED_ROLE, true);
    }

    public static function isValidAge(int|null $age): bool
    {
        return is_int($age) && $age >= 0 || $age === null;
    }

    public static function isValidGender(string|null $gender): bool
    {
        return in_array($gender, ['male', 'female'], true) || $gender === null;
    }

    private function setId(int $id): void
    {
        if (!self::isValidId($id)) {
            $textError = 'Поле id должно быть целое число больше 0.';
            Helper::writeLog(get_class($this) . ': ' . $textError);

            throw new Exception($textError);
        }

        $this->id = $id;
    }

    private function setEmail(string $email): void
    {
        if (!self::isValidEmail($email)) {
            $textError = 'Поле email должно быть корректным email-адресом и длинной не более 150 символов.';
            Helper::writeLog(get_class($this) . ': ' . $textError);

            throw new Exception($textError);
        }

        $this->email = $email;
    }

    private function setPasswordEncrypted(string $password): void
    {
        if (!self::isValidPassword($password)) {
            $textError = 'Поле password должно быть не более 255 символов.';
            Helper::writeLog(get_class($this) . ': ' . $textError);

            throw new Exception($textError);
        }

        $this->passwordEncrypted = $password;
    }

    private function setRole(string $role): void
    {
        if (!self::isValidRole($role)) {
            $textError = 'Поле role должно быть user или admin.';
            Helper::writeLog(get_class($this) . ': ' . $textError);

            throw new Exception($textError);
        }

        $this->role = $role;
    }

    private function setAge(int|null $age): void
    {
        if (!self::isValidAge($age)) {
            $textError = 'Поле age должно быть целое число больше 0.';
            Helper::writeLog(get_class($this) . ': ' . $textError);

            throw new Exception($textError);
        }

        $this->age = $age;
    }

    private function setGender(string|null $gender): void
    {
        if (!self::isValidGender($gender)) {
            $textError = 'Поле gender должно быть male или female.';
            Helper::writeLog(get_class($this) . ': ' . $textError);

            throw new Exception($textError);
        }

        $this->gender = $gender;
    }

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

    public function __get(string $name): string|int|null
    {
        return $this->$name;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
