<?php

class Mypages_Setup
{

    public static function install($previous)
    {
        $db = XenForo_Application::getDb();
        if (XenForo_Application::$versionId < 1020070)
        {
            // note: this can't be phrased
            throw new XenForo_Exception('هذا المنتج يتطلب إصدار 1.2.0 و أعلى.', true);
        }

        $tables = self::getTables();

        if (!$previous)
        {
            foreach ($tables AS $tableSql)
            {
                try
                {
                    $db->query($tableSql);
                }
                catch (Zend_Db_Exception $e) {}
            }
        }
    }

    public static function uninstall()
    {
        $db = XenForo_Application::get('db');
        foreach (self::getTables() AS $tableName => $tableSql)
        {
            try
            {
                $db->query("DROP TABLE IF EXISTS `$tableName`");

            }
            catch (Zend_Db_Exception $e) {}
        }
    }


    public static function getTables()
    {
        $tables = array();
        $tables['xf_my_pages'] = "
    CREATE TABLE xf_my_pages (
        `page_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `page_name` varchar(50) NOT NULL,
        `display_order` int(10) unsigned NOT NULL DEFAULT '0',
        `callback_class` varchar(75) NOT NULL DEFAULT '',
        `callback_method` varchar(75) NOT NULL DEFAULT '',
        PRIMARY KEY (`page_id`),
        UNIQUE KEY `page_name` (`page_name`),
        KEY `display_order` (`display_order`)
    ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci
    ";

        return $tables;
    }
}