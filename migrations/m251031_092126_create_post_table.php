<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%post}}`.
 */
class m251031_092126_create_post_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%post}}', [
            'id' => $this->primaryKey(),
            'author' => $this->string(15)->notNull(),
            'email' => $this->string(255)->notNull(),
            'message' => $this->text()->notNull(),
            'ip' => $this->string(45)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull()->defaultValue(0),
            'deleted_at' => $this->integer()->null(),
            'token' => $this->string(64)->notNull()->unique(),
        ]);

        // email index for quick search of author's posts
        $this->createIndex('idx-post-email', '{{%post}}', 'email');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%post}}');
    }
}
