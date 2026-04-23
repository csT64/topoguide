<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_ACTIVE   = 10;
    const STATUS_DISABLED = 0;

    public static function tableName(): string
    {
        return 'users';
    }

    public function rules(): array
    {
        return [
            [['username', 'password', 'email', 'auth_key'], 'required'],
            [['username'], 'string', 'max' => 64],
            [['email'], 'string', 'max' => 128],
            [['password'], 'string', 'max' => 255],
            [['auth_key'], 'string', 'max' => 32],
            [['access_token'], 'string', 'max' => 64],
            [['status'], 'integer'],
            [['username'], 'unique'],
            [['email'], 'unique'],
            [['email'], 'email'],
        ];
    }

    public static function findIdentity($id): ?self
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findIdentityByAccessToken($token, $type = null): ?self
    {
        return static::findOne(['access_token' => $token, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findByUsername(string $username): ?self
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAuthKey(): string
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey): bool
    {
        return $this->auth_key === $authKey;
    }

    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password);
    }

    public function setPassword(string $password): void
    {
        $this->password = Yii::$app->security->generatePasswordHash($password);
    }

    public function generateAuthKey(): void
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }
}
