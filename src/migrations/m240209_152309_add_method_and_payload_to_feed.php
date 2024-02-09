<?php

namespace craft\feedme\migrations;

use Craft;
use craft\db\Migration;

/**
 * m240209_152309_add_method_and_payload_to_feed migration.
 */
class m240209_152309_add_method_and_payload_to_feed extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%feedme_feeds}}', 'feedMethod')) {
            $this->addColumn('{{%feedme_feeds}}', 'feedMethod', $this->string()->notNull()->defaultValue('GET')->after('feedType'));
        }

        if (!$this->db->columnExists('{{%feedme_feeds}}', 'feedHeaders')) {
            $this->addColumn('{{%feedme_feeds}}', 'feedHeaders', $this->text()->after('feedMethod'));
        }

        if (!$this->db->columnExists('{{%feedme_feeds}}', 'feedPayload')) {
            $this->addColumn('{{%feedme_feeds}}', 'feedPayload', $this->text()->after('feedHeaders'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        if ($this->db->columnExists('{{%feedme_feeds}}', 'feedMethod')) {
            $this->dropColumn('{{%feedme_feeds}}', 'feedMethod');
        }

        if ($this->db->columnExists('{{%feedme_feeds}}', 'feedHeaders')) {
            $this->dropColumn('{{%feedme_feeds}}', 'feedHeaders');
        }

        if ($this->db->columnExists('{{%feedme_feeds}}', 'feedPayload')) {
            $this->dropColumn('{{%feedme_feeds}}', 'feedPayload');
        }

        return true;
    }
}
