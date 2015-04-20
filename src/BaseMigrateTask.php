<?php

namespace Serebro\MongoMigration;

use Exception;
use Phalcon\Mvc\View;

abstract class BaseMigrateTask extends \Phalcon\CLI\Task
{

    const EVENT_BEFORE_ACTION = 'beforeAction';
    const EVENT_AFTER_ACTION = 'afterAction';

    const BASE_MIGRATION = 'm000000_000000_base';

    /** @var  \Phalcon\DI */
    public $di;

    public $default_action = 'up';

    public $migration_path = './app/migrations/';

    public $template_file = './app/views/migrations/template.php';


    public function initialize()
    {
    }

    /**
     * Upgrades the application by applying new migrations.
     * For example,
     *
     * ~~~
     * migrate     # apply all new migrations
     * migrate 3   # apply the first 3 new migrations
     * ~~~
     *
     * @param integer $limit the number of new migrations to be applied. If 0, it means
     *                       applying all available new migrations.
     *
     * @return integer the status of the action execution. 0 means normal, other values mean abnormal.
     */
    public function upAction()
    {
        $silent = (bool)$this->dispatcher->getParam('silent');
        $limit = (int)$this->dispatcher->getParam('limit', 'int');
        $migrations = $this->getNewMigrations();
        if (empty($migrations)) {
            Console::output('No new migration found. Your system is up-to-date.');
            return;
        }

        $total = count($migrations);
        $limit = (int)$limit;
        if ($limit > 0) {
            $migrations = array_slice($migrations, 0, $limit);
        }

        $n = count($migrations);
        if ($n === $total) {
            Console::output("Total $n new " . ($n === 1 ? 'migration' : 'migrations') . " to be applied:");
        } else {
            Console::output(
                "Total $n out of $total new " . ($total === 1 ? 'migration' : 'migrations') . " to be applied:"
            );
        }

        foreach ($migrations as $migration) {
            Console::output("    $migration");
        }

        $ok = $silent ? true : Console::confirm('Apply the above ' . ($n === 1 ? 'migration' : 'migrations') . "?");

        if ($ok) {
            foreach ($migrations as $migration) {
                if (!$this->migrateUp($migration)) {
                    Console::output("Migration failed. The rest of the migrations are canceled.\n");
                    return;
                }
            }
            Console::output("Migrated up successfully.\n");
        }
    }

    /**
     * Downgrades the application by reverting old migrations.
     * For example,
     *
     * ~~~
     * migrate/down     # revert the last migration
     * migrate/down 3   # revert the last 3 migrations
     * migrate/down all # revert all migrations
     * ~~~
     *
     * @param integer $limit the number of migrations to be reverted. Defaults to 1,
     *                       meaning the last applied migration will be reverted.
     * @throws Exception if the number of the steps specified is less than 1.
     *
     * @return integer the status of the action execution. 0 means normal, other values mean abnormal.
     */
    public function downAction($limit = 1)
    {
        if ($limit === 'all') {
            $limit = null;
        } else {
            $limit = (int)$limit;
            if ($limit < 1) {
                throw new Exception("The step argument must be greater than 0.");
            }
        }

        $migrations = $this->getMigrationHistory($limit);

        if (empty($migrations)) {
            Console::output("No migration has been done before.");
            return 0;
        }

        $n = count($migrations);
        Console::output("Total $n " . ($n === 1 ? 'migration' : 'migrations') . " to be reverted:");
        foreach ($migrations as $migration) {
            Console::output("    {$migration['version']}");
        }
        Console::output();

        if (Console::confirm('Revert the above ' . ($n === 1 ? 'migration' : 'migrations') . "?")) {
            foreach ($migrations as $migration) {
                if (!$this->migrateDown($migration['version'])) {
                    Console::output("Migration failed. The rest of the migrations are canceled.\n");
                    return -1;
                }
            }
            Console::output("Migrated down successfully.\n");
        }
    }


    /**
     * Creates a new migration.
     *
     * This command creates a new migration using the available migration template.
     * After using this command, developers should modify the created migration
     * skeleton by filling up the actual migration logic.
     *
     * ~~~
     * migrate create create_user_collection
     * ~~~
     *
     * @param string $name the name of the new migration. This should only contain
     *                     letters, digits and/or underscores.
     * @throws Exception if the name argument is invalid.
     */
    public function createAction($name = '')
    {
        if (!preg_match('/^\w+$/', $name)) {
            throw new Exception("The migration name should contain letters, digits and/or underscore characters only." . PHP_EOL);
        }

        $name = 'm' . gmdate('ymd_His') . '_' . $name;
        $file = realpath($this->migration_path) . '/' . $name . '.php';

        if (Console::confirm("Create new migration '$file'?")) {
            /** @var View $view */
            $view = clone($this->di->get('view'));
            $view->setMainView(null);
            $content = $view->getRender('migration', 'template', ['className' => $name]);
            file_put_contents($file, $content);
            Console::output("New migration created successfully.");
        }
    }

    /**
     * Upgrades with the specified migration class.
     * @param string $class the migration class name
     * @return boolean whether the migration is successful
     */
    protected function migrateUp($class)
    {
        if ($class === self::BASE_MIGRATION) {
            return true;
        }

        Console::output("*** applying $class");
        $start = microtime(true);
        /** @var \Serebro\MongoMigration\Migration $migration */
        $migration = $this->createMigration($class);
        if ($migration->up() !== false) {
            $this->addMigrationHistory($class);
            $time = microtime(true) - $start;
            Console::output("*** applied $class (time: " . sprintf("%.3f", $time) . "s)\n");
            return true;
        } else {
            $time = microtime(true) - $start;
            Console::output("*** failed to apply $class (time: " . sprintf("%.3f", $time) . "s)\n");
            return false;
        }
    }

    /**
     * Downgrades with the specified migration class.
     * @param string $class the migration class name
     * @return boolean whether the migration is successful
     */
    protected function migrateDown($class)
    {
        if ($class === self::BASE_MIGRATION) {
            return true;
        }

        Console::output("*** reverting $class");
        $start = microtime(true);
        /** @var \Serebro\MongoMigration\Migration $migration */
        $migration = $this->createMigration($class);
        if ($migration->down() !== false) {
            $this->removeMigrationHistory($class);
            $time = microtime(true) - $start;
            Console::output("*** reverted $class (time: " . sprintf("%.3f", $time) . "s)\n");
            return true;
        } else {
            $time = microtime(true) - $start;
            Console::output("*** failed to revert $class (time: " . sprintf("%.3f", $time) . "s)\n");
            return false;
        }
    }

    /**
     * Returns the migrations that are not applied.
     * @return array list of new migrations
     */
    protected function getNewMigrations()
    {
        $applied = [];
        foreach ($this->getMigrationHistory(null) as $row) {
            $applied[substr($row['version'], 1, 13)] = true;
        }

        $migrations = [];
        $handle = opendir($this->migration_path);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $this->migration_path . $file;
            if (
                preg_match('/^(m(\d{6}_\d{6})_.*?)\.php$/', $file, $matches) &&
                is_file($path) &&
                !isset($applied[$matches[2]])
            ) {
                $migrations[] = $matches[1];
            }
        }
        closedir($handle);
        sort($migrations);

        return $migrations;
    }

    /**
     * Creates a new migration instance.
     * @param string $class the migration class name
     */
    protected function createMigration($class)
    {
        $file = $this->migration_path . DIRECTORY_SEPARATOR . $class . '.php';
        require_once($file);
        return new $class();
    }

    /**
     * Returns the migration history.
     * @param integer $limit the maximum number of records in the history to be returned. `null` for "no limit".
     * @return array the migration history
     */
    abstract protected function getMigrationHistory($limit);

    /**
     * Adds new migration entry to the history.
     * @param string $version migration version name.
     */
    abstract protected function addMigrationHistory($version);

    /**
     * Removes existing migration from the history.
     * @param string $version migration version name.
     */
    abstract protected function removeMigrationHistory($version);
}
