<?php

namespace Serebro\MongoMigration;

class MigrateTask extends BaseMigrateTask
{

    public $connection_name = 'mongo';

    public $collection_name = 'migration';

    public $template_file = './app/views/template.phtml';

    private $base_migration_ensured = false;


    public function initialize()
    {
        $this->template_file = __DIR__ . DIRECTORY_SEPARATOR . 'template.phtml'; // todo: write path to template
    }

    public function mainAction()
    {
        $this->upAction();
    }

    /**
     * Creates a new migration instance.
     * @param string $class the migration class name
     */
    protected function createMigration($class)
    {
        $file = $this->migration_path . DIRECTORY_SEPARATOR . $class . '.php';
        require_once $file;
        return new $class(['connection_name' => $this->connection_name]);
    }

    protected function getCollection() {
        /** @var \MongoDB $mongoDb */
        $mongoDb = $this->getDI()->get($this->connection_name);
        return $mongoDb->selectCollection($this->collection_name);
    }

    /**
     * Returns the migration history.
     * @param integer $limit the maximum number of records in the history to be returned. `null` for "no limit".
     * @return array the migration history
     */
    protected function getMigrationHistory($limit)
    {
        $this->ensureBaseMigrationHistory();

        $history = self::getCollection()
            ->find([], ['version', 'apply_time'])
            ->sort(['version' => -1])
            ->limit($limit);

        $history = array_filter(iterator_to_array($history), function($row){
            return $row['version'] !== self::BASE_MIGRATION;
        });

        return $history;
    }

    /**
     * Ensures migration history contains at least base migration entry.
     */
    protected function ensureBaseMigrationHistory()
    {
        if (!$this->base_migration_ensured) {
            if (!$this->getCollection()->count(['version' => self::BASE_MIGRATION])) {
                $this->addMigrationHistory(self::BASE_MIGRATION);
            }
            $this->base_migration_ensured = true;
        }
    }

    /**
     * Adds new migration entry to the history.
     * @param string $version migration version name.
     */
    protected function addMigrationHistory($version)
    {
        $this->getCollection()->insert(['version' => $version, 'apply_time' => new \MongoDate()]);
    }

    /**
     * Removes existing migration from the history.
     * @param string $version migration version name.
     */
    protected function removeMigrationHistory($version)
    {
        $this->getCollection()->remove(['version' => $version]);
    }
}
