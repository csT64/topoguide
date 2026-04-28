<?php

use yii\db\Migration;

class m000001_000000_create_users_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('users', [
            'id'           => $this->primaryKey()->unsigned(),
            'username'     => $this->string(64)->notNull()->unique(),
            'password'     => $this->string(255)->notNull(),
            'email'        => $this->string(128)->notNull()->unique(),
            'auth_key'     => $this->string(32)->notNull(),
            'access_token' => $this->string(64)->null(),
            'status'       => $this->tinyInteger()->notNull()->defaultValue(10)
                              ->comment('10 = actif, 0 = désactivé'),
            'created_at'   => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at'   => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);
    }

    public function safeDown(): void
    {
        $this->dropTable('users');
    }
}
