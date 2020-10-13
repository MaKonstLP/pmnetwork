<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%subdomen_filteritem}}`.
 * Has foreign keys to the tables:
 *
 * - `{{%subdomen}}`
 * - `{{%filter_items}}`
 */
class m200924_055741_create_subdomen_filteritem_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%subdomen_filteritem}}', [
            'id' => $this->primaryKey(),
            'subdomen_id' => $this->integer()->notNull(),
            'filter_items_id' => $this->integer()->notNull(),
            'hits' => $this->integer(),
            'is_valid' => $this->tinyInteger(),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // creates index for column `subdomen_id`
        $this->createIndex(
            '{{%idx-subdomen_filteritem-subdomen_id}}',
            '{{%subdomen_filteritem}}',
            'subdomen_id'
        );

        // add foreign key for table `{{%subdomen}}`
        $this->addForeignKey(
            '{{%fk-subdomen_filteritem-subdomen_id}}',
            '{{%subdomen_filteritem}}',
            'subdomen_id',
            '{{%subdomen}}',
            'id',
            'CASCADE'
        );

        // creates index for column `filter_items_id`
        $this->createIndex(
            '{{%idx-subdomen_filteritem-filter_items_id}}',
            '{{%subdomen_filteritem}}',
            'filter_items_id'
        );

        // add foreign key for table `{{%filter_items}}`
        $this->addForeignKey(
            '{{%fk-subdomen_filteritem-filter_items_id}}',
            '{{%subdomen_filteritem}}',
            'filter_items_id',
            '{{%filter_items}}',
            'id',
            'CASCADE'
        );

        $this->createIndex(
            'idx-unique-subdomen_id-filter_items_id',
            'subdomen_filteritem',
            ['subdomen_id', 'filter_items_id'],
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // drops foreign key for table `{{%subdomen}}`
        $this->dropForeignKey(
            '{{%fk-subdomen_filteritem-subdomen_id}}',
            '{{%subdomen_filteritem}}'
        );

        // drops index for column `subdomen_id`
        $this->dropIndex(
            '{{%idx-subdomen_filteritem-subdomen_id}}',
            '{{%subdomen_filteritem}}'
        );

        // drops foreign key for table `{{%filter_items}}`
        $this->dropForeignKey(
            '{{%fk-subdomen_filteritem-filter_items_id}}',
            '{{%subdomen_filteritem}}'
        );

        // drops index for column `filter_items_id`
        $this->dropIndex(
            '{{%idx-subdomen_filteritem-filter_items_id}}',
            '{{%subdomen_filteritem}}'
        );
        $this->dropIndex(
            '{{%idx-unique-subdomen_id-filter_items_id}}',
            '{{%subdomen_filteritem}}'
        );

        $this->dropTable('{{%subdomen_filteritem}}');
    }
}
