<?php

namespace Core;

use DirectoryIterator;
use PDOException;

/**
 * Plugin Loader
 *
 * @package  core
 * @author   Frederic Guillot
 */
class PluginLoader extends Base
{
    /**
     * Schema version table for plugins
     *
     * @var string
     */
    const TABLE_SCHEMA = 'plugin_schema_versions';

    /**
     * Scan plugin folder and load plugins
     *
     * @access public
     */
    public function scan()
    {
        if (file_exists(__DIR__.'/../../plugins')) {
            $dir = new DirectoryIterator(__DIR__.'/../../plugins');

            foreach ($dir as $fileinfo) {
                if (! $fileinfo->isDot() && $fileinfo->isDir()) {
                    $plugin = $fileinfo->getFilename();
                    $this->loadSchema($plugin);
                    $this->load($plugin);
                }
            }
        }
    }

    /**
     * Load plugin
     *
     * @access public
     */
    public function load($plugin)
    {
        $class = '\Plugin\\'.$plugin.'\\Plugin';
        $instance = new $class($this->container);

        Tool::buildDic($this->container, $instance->getClasses());

        $instance->initialize();
    }

    /**
     * Load plugin schema
     *
     * @access public
     * @param  string  $plugin
     */
    public function loadSchema($plugin)
    {
        $filename = __DIR__.'/../../plugins/'.$plugin.'/Schema/'.ucfirst(DB_DRIVER).'.php';

        if (file_exists($filename)) {
            require($filename);
            $this->migrateSchema($plugin);
        }
    }

    /**
     * Execute plugin schema migrations
     *
     * @access public
     * @param  string  $plugin
     */
    public function migrateSchema($plugin)
    {
        $last_version = constant('\Plugin\\'.$plugin.'\Schema\VERSION');
        $current_version = $this->getSchemaVersion($plugin);

        try {

            $this->db->startTransaction();
            $this->db->getDriver()->disableForeignKeys();

            for ($i = $current_version + 1; $i <= $last_version; $i++) {
                $function_name = '\Plugin\\'.$plugin.'\Schema\version_'.$i;

                if (function_exists($function_name)) {
                    call_user_func($function_name, $this->db->getConnection());
                }
            }

            $this->db->getDriver()->enableForeignKeys();
            $this->db->closeTransaction();
            $this->setSchemaVersion($plugin, $i - 1);
        }
        catch (PDOException $e) {
            $this->db->cancelTransaction();
            $this->db->getDriver()->enableForeignKeys();
            die('Unable to migrate schema for the plugin: '.$plugin.' => '.$e->getMessage());
        }
    }

    /**
     * Get current plugin schema version
     *
     * @access public
     * @param  string  $plugin
     * @return integer
     */
    public function getSchemaVersion($plugin)
    {
        return (int) $this->db->table(self::TABLE_SCHEMA)->eq('plugin', strtolower($plugin))->findOneColumn('version');
    }

    /**
     * Save last plugin schema version
     *
     * @access public
     * @param  string   $plugin
     * @param  integer  $version
     * @return boolean
     */
    public function setSchemaVersion($plugin, $version)
    {
        $dictionary = array(
            strtolower($plugin) => $version
        );

        return $this->db->getDriver()->upsert(self::TABLE_SCHEMA, 'plugin', 'version', $dictionary);
    }
}
