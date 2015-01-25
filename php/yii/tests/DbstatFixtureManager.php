<?php

/**
 * Класс для управления фикстурами специально для dbstat
 */
class DbstatFixtureManager extends CommonDbFixtureManager
{
    
    private $_db;      // соединение с БД
    private $_rows;    // массив строк, формат: название фикстуры, алиас строки => строка
    private $_records; // массив записей, формат: название фикстуры, алиас строки => запись (или название класса)

    /**
     * (переопределение метода getDbConnection от одного из родительских классов - для соединения с dbstat)
     * 
	 * Возвращает соединение с БД, используемое для загрузки фикстур.
	 * @return CDbConnection соединение с БД
	 */
    public function getDbConnection()
    {
        if ($this->_db === null) {
            $this->_db = Yii::app()->getComponent($this->connectionID);
            if (!$this->_db instanceof CDbConnection) {
                throw new CException(
                    Yii::t('yii', 'CDbTestFixture.connectionID "{id}" is invalid. Please make sure it refers to the ID of a CDbConnection application component.', array('{id}' => $this->connectionID))
                );
            }
        }
        return $this->_db;
    }

    /**
     * (переопределение метода load от одного из родительских классов - загрузка фикстур с использованием ShardActiveRecord)
     * 
     * Загружает заданные фикстуры.
     * Для каждой фикстуры сначала идет перезагрузка таблицы через вызов {@link resetTable} 
     * и потом идет загрузка данных фикстуры.
     * Загруженные данные фикстуры могут быть потом получены через использование {@link getRows} и {@link getRecord}.
     * Если таблица не имеет данных фикстуры, то {@link resetTable} будет вызван для перезагрузки таблицы.
     * @param array $fixtures массив фикстур для загрузки. Индексы массива - это названия фикстур,
     * а значения элементов массива либо названия классов AR либо названия таблиц.
     * Для названий таблиц следует использовать в начале знак двоеточия (например,
     * 'Post' это название класса AR, а ':Post' это название таблицы).
     */
    public function load($fixtures)
    {
        $schema = $this->getDbConnection()->getSchema();
        $schema->checkIntegrity(false);

        $this->_rows = array();
        $this->_records = array();
        foreach ($fixtures as $fixtureName => $tableName) {
            if ($tableName[0] === ':') {
                $tableName = substr($tableName, 1);
                unset($modelClass);
            } else {
                $modelClass = Yii::import($tableName, true);
                // нижеследующая строка закомментирована:
				// $tableName=CActiveRecord::model($modelClass)->tableName();
                // по той причине, что мы используем не базовую модель CActiveRecord,
                // а модель ShardActiveRecord - для статистики по шардингам пользователей
                $model = new $modelClass();
				$tableName = $model->tableName();
            }
            if (($prefix=$this->getDbConnection()->tablePrefix) !== null) {
                $tableName = preg_replace('/{{(.*?)}}/',$prefix.'\1',$tableName);
            }
            $this->resetTable($tableName);
            $rows = $this->loadFixture($tableName);
            if (is_array($rows) && is_string($fixtureName)) {
                $this->_rows[$fixtureName] = $rows;
                if (isset($modelClass)) {
                    foreach (array_keys($rows) as $alias) {
                        $this->_records[$fixtureName][$alias] = $modelClass;
                    }
                }
            }
        }

        $schema->checkIntegrity(true);
    }
}