<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%blog_post}}`.
 */
class m201025_091318_add_html_column_to_blog_post_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%blog_post}}', 'html', $this->text()->after('featured'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%blog_post}}', 'html');
    }
}
