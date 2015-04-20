<?php

namespace Serebro\MongoMigration;

use MongoId;
use Phalcon\DI\Injectable;

abstract class BaseMigration extends Injectable
{

    public $connection_name;

    public function __construct($params = [])
    {
        if ($params['connection_name']) {
            $this->connection_name = $params['connection_name'];
        }
    }

    /**
     * Creates new collection with the specified options.
     * @param string|array $collection_name name of the collection
     */
    public function createCollection($collection_name)
    {
        Console::stdOut("    > create collection $collection_name ...");
        $time = microtime(true);

        /** @var \MongoDB $mongoDb */
        $mongoDb = $this->getDI()->get('mongo');
        $mongoDb->createCollection($collection_name);

        Console::output(' done (time: ' . sprintf('%.3f', microtime(true) - $time) . 's)');
    }

    /**
     * @param string|array $collection_name
     */
    public function dropCollection($collection_name)
    {
        Console::stdOut("    > drop collection $collection_name ...");
        $time = microtime(true);

        /** @var \MongoDB $mongoDb */
        $mongoDb = $this->getDI()->get('mongo');
        $mongoDb->selectCollection($collection_name)->drop();

        Console::output(' done (time: ' . sprintf('%.3f', microtime(true) - $time) . 's)');
    }

    /**
     * @param string|array $collection_name
     * @param array|string $columns
     * @param array $options
     */
    public function createIndex($collection_name, $columns, $options = [])
    {
        Console::stdOut("    > create index on $collection_name (" . json_encode((array)$columns) . (empty($options) ? '' : ', ' . json_encode($options) . ') ...'));
        $time = microtime(true);

        /** @var \MongoDB $mongoDb */
        $mongoDb = $this->getDI()->get('mongo');
        $mongoDb->selectCollection($collection_name)->ensureIndex($columns, $options);

        Console::output(' done (time: ' . sprintf('%.3f', microtime(true) - $time) . 's)');
    }

    /**
     * @param string|array $collection_name
     * @param string|array $columns
     */
    public function dropIndex($collection_name, $columns)
    {
        Console::stdOut("    > drop index on $collection_name (" . json_encode((array)$columns) . ') ...');
        $time = microtime(true);

        /** @var \MongoDB $mongoDb */
        $mongoDb = $this->getDI()->get('mongo');
        $mongoDb->selectCollection($collection_name)->deleteIndex($columns);

        Console::output(' done (time: ' . sprintf('%.3f', microtime(true) - $time) . 's)');
    }

    /**
     * @param string|array $collection_name
     */
    public function dropAllIndexes($collection_name)
    {
        Console::stdOut("    > drop all indexes on $collection_name ...");
        $time = microtime(true);

        /** @var \MongoDB $mongoDb */
        $mongoDb = $this->getDI()->get('mongo');
        $mongoDb->selectCollection($collection_name)->deleteIndexes();

        Console::output(' done (time: ' . sprintf('%.3f', microtime(true) - $time) . 's)');
    }

    /**
     * @param array|string $collection_name
     * @param array|object $data
     * @param array $options
     * @return MongoId
     */
    public function insert($collection_name, $data, $options = [])
    {
        Console::stdOut("    > insert into $collection_name ...");
        $time = microtime(true);

        /** @var \MongoDB $mongoDb */
        $mongoDb = $this->getDI()->get('mongo');
        $id = $mongoDb->selectCollection($collection_name)->insert($data, $options);

        Console::output(' done (time: ' . sprintf('%.3f', microtime(true) - $time) . 's)');

        return $id;
    }

    /**
     * @param array|string $collection_name
     * @param array $rows array of arrays or objects to be inserted.
     * @param array $options
     * @return array inserted data
     */
    public function batchInsert($collection_name, $rows, $options = [])
    {
        Console::stdOut("    > insert into $collection_name ...");
        $time = microtime(true);

        /** @var \MongoDB $mongoDb */
        $mongoDb = $this->getDI()->get('mongo');
        $rows = $mongoDb->selectCollection($collection_name)->batchInsert($rows, $options);

        Console::output(' done (time: ' . sprintf('%.3f', microtime(true) - $time) . 's)');
        return $rows;
    }

    /**
     * Updates the rows, which matches given criteria by given data.
     * Note: for "multiple" mode Mongo requires explicit strategy "$set" or "$inc"
     * to be specified for the "newData". If no strategy is passed "$set" will be used.
     * @param array|string $collection_name collection name.
     * @param array $condition description of the objects to update.
     * @param array $newData the object with which to update the matching records.
     * @param array $options list of options in format: optionName => optionValue.
     * @return integer|boolean number of updated documents or whether operation was successful.
     */
    public function update($collection_name, $condition, $newData, $options = [])
    {
        Console::stdOut("    > update $collection_name ...");
        $time = microtime(true);

        /** @var \MongoDB $mongoDb */
        $mongoDb = $this->getDI()->get('mongo');
        $result = $mongoDb->selectCollection($collection_name)->update($condition, $newData, $options);

        Console::output(' done (time: ' . sprintf('%.3f', microtime(true) - $time) . 's)');

        return $result;
    }

    /**
     * Update the existing database data, otherwise insert this data
     * @param array|string $collection_name collection name.
     * @param array|object $data data to be updated/inserted.
     * @param array $options list of options in format: optionName => optionValue.
     * @return MongoId updated/new record id instance.
     */
    public function save($collection_name, $data, $options = [])
    {
        Console::stdOut("    > save $collection_name ...");
        $time = microtime(true);

        /** @var \MongoDB $mongoDb */
        $mongoDb = $this->getDI()->get('mongo');
        $id = $mongoDb->selectCollection($collection_name)->save($data, $options);

        Console::output(' done (time: ' . sprintf('%.3f', microtime(true) - $time) . 's)');

        return $id;
    }

    /**
     * Removes data from the collection.
     * @param array|string $collection_name collection name.
     * @param array $condition description of records to remove.
     * @param array $options list of options in format: optionName => optionValue.
     * @return integer|boolean number of updated documents or whether operation was successful.
     */
    public function remove($collection_name, $condition = [], $options = [])
    {
        Console::stdOut("    > remove $collection_name ...");
        $time = microtime(true);

        /** @var \MongoDB $mongoDb */
        $mongoDb = $this->getDI()->get('mongo');
        $result = $mongoDb->selectCollection($collection_name)->remove($condition, $options);

        Console::output(' done (time: ' . sprintf('%.3f', microtime(true) - $time) . 's)');

        return $result;
    }

    abstract public function up();

    abstract public function down();
}
