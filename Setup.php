<?php

namespace ThemeHouse\Topics;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

/**
 * Class Setup
 * @package ThemeHouse\Topics
 */
class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    /**
     *
     */
    public function installStep1()
    {
        $schemaManager = $this->schemaManager();

        foreach ($this->getTables() as $tableName => $closure) {
            $schemaManager->createTable($tableName, $closure);
        }
    }

    /**
     * @return array
     */
    public function getTables()
    {
        $tables = [];

        $tables['xf_th_topics_topic'] = function (Create $table) {
            $table->addColumn('topic_id', 'int')->autoIncrement();
            $table->addColumn('title', 'varchar', 50)->setDefault('');
            $table->addColumn('description', 'text')->nullable();
            $table->addColumn('background_color', 'varchar', 50)->setDefault('');
            $table->addColumn('extra_class', 'varchar', 50)->setDefault('');
            $table->addColumn('additional_selectable', 'tinyint', 3)->setDefault(1);
            $table->addColumn('node_id', 'int')->setDefault(0);
            $table->addColumn('topic_group_id', 'int')->setDefault(0);
            $table->addColumn('discussion_count', 'int')->setDefault(0);
            $table->addColumn('message_count', 'int')->setDefault(0);
            $table->addColumn('last_post_id', 'int')->setDefault(0);
            $table->addColumn('last_post_date', 'int')->setDefault(0);
            $table->addColumn('last_post_user_id', 'int')->setDefault(0);
            $table->addColumn('last_post_username', 'varchar', 50)->setDefault('');
            $table->addColumn('last_post_date_primary', 'int')->setDefault(0);
            $table->addColumn('last_thread_title', 'varchar', 150)->setDefault('');
            $table->addColumn('last_thread_prefix_id', 'int')->setDefault(0);
        };

        $tables['xf_th_topics_topic_group'] = function (Create $table) {
            $table->addColumn('topic_group_id', 'int')->autoIncrement();
            $table->addColumn('title', 'varchar', 50)->setDefault('');
        };

        $tables['xf_th_topics_topic_watch'] = function (Create $table) {
            $table->addColumn('user_id', 'int');
            $table->addColumn('topic_id', 'int');
            $table->addColumn('notify_on', 'enum')->values(['', 'thread', 'message']);
            $table->addColumn('send_alert', 'tinyint', 3);
            $table->addColumn('send_email', 'tinyint', 3);
            $table->addPrimaryKey(['user_id', 'topic_id']);
            $table->addKey(['topic_id', 'notify_on']);
        };

        $tables['xf_th_topics_topic_read'] = function (Create $table) {
            $table->addColumn('topic_read_id', 'int')->autoIncrement();
            $table->addColumn('user_id', 'int');
            $table->addColumn('topic_id', 'int');
            $table->addColumn('topic_read_date', 'int');
            $table->addUniqueKey(['user_id', 'topic_id']);
            $table->addKey('topic_id');
            $table->addKey('topic_read_date');
        };

        $tables['xf_th_topics_media_gallery_filter'] = function (Create $table) {
            $table->addColumn('media_gallery_filter_id', 'int')->autoIncrement();
            $table->addColumn('display_order', 'int');
            $table->addColumn('active', 'tinyint', 3);
            $table->addColumn('style_id', 'int');
            $table->addColumn('criteria', 'mediumblob');
        };

        return $tables;
    }

    /**
     *
     */
    public function installStep2()
    {
        $schemaManager = $this->schemaManager();

        $schemaManager->alterTable('xf_thread', function (Alter $table) {
            $table->addColumn('topic_id', 'int')
                ->setDefault(0);
            $table->addColumn('additional_topic_ids', 'varbinary', 255)
                ->setDefault('');

            $table->addKey('topic_id');
            $table->addKey('additional_topic_ids');
        });
    }

    /**
     *
     */
    public function installStep3()
    {
        $schemaManager = $this->schemaManager();

        $schemaManager->alterTable('xf_user', function (Alter $table) {
            $table->addColumn('thtopics_topic_filters', 'blob')
                ->nullable();
            $table->addColumn('thtopics_forum_filters', 'blob')
                ->nullable();
        });
    }

    /**
     * @throws \XF\PrintableException
     */
    public function installStep4()
    {
        /** @var \ThemeHouse\Topics\Repository\Topic $repo */
        $repo = \XF::repository('ThemeHouse\Topics:Topic');
        $repo->rebuildTopicCache();
    }

    /**
     *
     */
    public function installStep5()
    {
        foreach ($this->getDefaultWidgetSetup() as $widgetKey => $widgetFn) {
            $widgetFn($widgetKey);
        }
    }

    /**
     * @return array
     */
    protected function getDefaultWidgetSetup()
    {
        return [
            'thtopics_thread_filter' => function ($key) {
                $this->createWidget($key, 'thtopics_thread_filter', [
                    'positions' => [
                        'forum_view_sidebar' => 10,
                        'thtopics_all_threads_sidebar' => 10,
                        'thtopics_find_threads_sidebar' => 10,
                        'thtrending_trending_sidebar' => 10,
                        'forum_new_posts_sidebar' => 10,
                        'thtopics_forum_new_threads_sidebar' => 10,
                        'thtopics_new_posts_sidebar' => 10,
                        'thtopics_new_threads_sidebar' => 10,
                        'thtopics_all_threads_above_threads' => 10,
                        'thtopics_find_threads_above_threads' => 10,
                        'thtopics_forum_new_posts_above_threads' => 10,
                        'thtopics_forum_new_threads_above_threads' => 10,
                        'thtopics_forum_view_above_threads' => 10,
                        'thtopics_new_posts_above_threads' => 10,
                        'thtopics_new_threads_above_threads' => 10,
                        'thtrending_trending_above_threads' => 10,
                        'thtopics_all_threads_below_threads' => 10,
                        'thtopics_find_threads_below_threads' => 10,
                        'thtopics_forum_new_posts_below_threads' => 10,
                        'thtopics_forum_new_threads_below_threads' => 10,
                        'thtopics_forum_view_below_threads' => 10,
                        'thtopics_new_posts_below_threads' => 10,
                        'thtopics_new_threads_below_threads' => 10,
                        'thtrending_trending_below_threads' => 10,
                        'thtopics_all_threads_sidenav' => 10,
                        'thtopics_find_threads_sidenav' => 10,
                        'thtopics_forum_new_posts_sidenav' => 10,
                        'thtopics_forum_new_threads_sidenav' => 10,
                        'thtopics_forum_view_sidenav' => 10,
                        'thtopics_new_posts_sidenav' => 10,
                        'thtopics_new_threads_sidenav' => 10,
                        'thtrending_trending_sidenav' => 10,
                    ],
                ]);
            },
        ];
    }

    /**
     *
     */
    public function upgrade1000071Step1()
    {
        $this->schemaManager()->renameTable('xf_thtopic_topic', 'xf_th_topics_topic');
        $this->schemaManager()->renameTable('xf_thtopic_topic_group', 'xf_th_topics_topic_group');
        $this->schemaManager()->renameTable('xf_thtopic_topic_watch', 'xf_th_topics_topic_watch');
        $this->schemaManager()->renameTable('xf_thtopic_topic_read', 'xf_th_topics_topic_read');
    }

    /**
     * @throws \XF\PrintableException
     */
    public function upgrade1000071Step2()
    {
        $template = \XF::finder('XF:Template')
            ->where('style_id', '=', 0)
            ->where('title', '=', 'th_topicCache_topics.less')
            ->fetchOne();
        if ($template) {
            /** @var \XF\Entity\Template $template */
            $template->delete();
        }
    }

    /**
     * @throws \XF\Db\Exception
     */
    public function upgrade1000132Step1()
    {
        $db = $this->db();

        $db->query("
            UPDATE xf_widget
            SET positions = REPLACE(positions, '\"thtopics_newest_posts\"', '\"thtopics_new_posts_sidebar\"')
            WHERE positions LIKE '%\"thtopics_newest_posts\"%';
        ");

        $db->query("
            UPDATE xf_widget
            SET positions = REPLACE(positions, '\"thtopics_latest_threads\"', '\"thtopics_new_threads_sidebar\"')
            WHERE positions LIKE '%\"thtopics_latest_threads\"%';
        ");

        $db->query("
            UPDATE xf_widget
            SET positions = REPLACE(positions, '\"thtopics_all_threads\"', '\"thtopics_all_threads_sidebar\"')
            WHERE positions LIKE '%\"thtopics_all_threads\"%';
        ");
    }

    /**
     *
     */
    public function upgrade1000152Step1()
    {
        $this->deleteWidget('thtopics_thread_filter_sidebar');
        $this->deleteWidget('thtopics_thread_filter_sidebar_hide_guests');
        $this->deleteWidget('thtopics_thread_filter_above_threads');
        $this->deleteWidget('thtopics_thread_filter_below_threads');
    }

    /**
     *
     */
    public function upgrade1000152Step2()
    {
        foreach ($this->getDefaultWidgetSetup() as $widgetKey => $widgetFn) {
            $widgetFn($widgetKey);
        }
    }

    /**
     *
     */
    public function upgrade1000314Step1()
    {
        $schemaManager = $this->schemaManager();

        $schemaManager->createTable('xf_th_topics_media_gallery_filter', function (Create $table) {
            $table->addColumn('media_gallery_filter_id', 'int')->autoIncrement();
            $table->addColumn('display_order', 'int');
            $table->addColumn('active', 'tinyint', 3);
            $table->addColumn('style_id', 'int');
            $table->addColumn('criteria', 'mediumblob');
        });
    }

    /**
     * @param $previousVersion
     * @param array $stateChanges
     * @throws \XF\PrintableException
     */
    public function postUpgrade($previousVersion, array &$stateChanges)
    {
        /** @var \ThemeHouse\Topics\Repository\Topic $repo */
        $repo = \XF::repository('ThemeHouse\Topics:Topic');
        $repo->rebuildTopicCache();
    }

    /**
     *
     */
    public function uninstallStep1()
    {
        $schemaManager = $this->schemaManager();

        foreach (array_keys($this->getTables()) as $tableName) {
            $schemaManager->dropTable($tableName);
        }

        foreach ($this->getDefaultWidgetSetup() as $widgetKey => $widgetFn) {
            $this->deleteWidget($widgetKey);
        }
    }

    /**
     *
     */
    public function uninstallStep2()
    {
        $schemaManager = $this->schemaManager();

        $schemaManager->alterTable('xf_thread', function (Alter $table) {
            $table->dropColumns([
                'topic_id',
                'additional_topic_ids',
            ]);
        });
    }

    /**
     * @throws \XF\PrintableException
     */
    public function uninstallStep3()
    {
        $phrases = \XF::finder('XF:Phrase')
            ->where('language_id', '=', 0)
            ->where('title', 'LIKE', 'th_topic_%.%');

        foreach ($phrases as $phrase) {
            /** @var \XF\Entity\Phrase $phrase */
            $phrase->delete();
        }
    }

    // ############################# TABLE / DATA DEFINITIONS ##############################

    /**
     * @throws \XF\PrintableException
     */
    public function uninstallStep4()
    {
        $template = \XF::finder('XF:Template')
            ->where('style_id', '=', 0)
            ->where('title', '=', 'thtopics_topic_cache.less')
            ->fetchOne();
        if ($template) {
            /** @var \XF\Entity\Template $template */
            $template->delete();
        }
    }

    /**
     *
     */
    public function uninstallStep5()
    {
        $schemaManager = $this->schemaManager();

        $schemaManager->alterTable('xf_user', function (Alter $table) {
            $table->dropColumns([
                'thtopics_topic_filters',
                'thtopics_forum_filters',
            ]);
        });
    }
}
