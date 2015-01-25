<?php

Yii::import('system.test.CDbFixtureManager');

/**
 * Основной класс для управления фикстурами
 * (загрузка фикстур в БД)
 */
class CommonDbFixtureManager extends CDbFixtureManager
{
    
	private $_rows;				// fixture name, row alias => row
	private $_records;			// fixture name, row alias => record (or class name)
    
	/**
     * (переопределение метода родительского класса CDbFixtureManager)
     * 
	 * Возвращает информацию о доступных фикстурах.
	 * В родительском классе этот метод ищет все PHP-файлы по папке с маршрутом {@link basePath}.
     * Здесь же возвращается просто пустой массив - с целью пропустить этап загрузки всех фикстур.
	 * @return array массив информации о доступных фикстурах (в формате "table name" => "fixture file")
	 */
    public function getFixtures()
    {
        return array();
    }
    
    /**
     * (переопределение метода родительского класса CDbFixtureManager)
     * 
     * Загрузка фикстуры в заданную таблицу.
     * Заданные строки будут вставлены в соответствующую таблицу.
     * Метод возвращает загруженные строки.
     * Если таблица имеет первичный ключ с автоинкрементом, то каждая строка будет содержать обновленное значение этого ключа.
     * Если фикстура не существует, то метод вернет false.
     * Примечание: можно вызвать метод {@link resetTable} перед вызовом этого метода для очистки этой соответствующей таблицы.
     * @param string $tableName название таблицы
     * @return array массив загруженных строк фикстуры, проиндексированный по алиасам строк.
     * False возвращается, если таблица не имеет фикстуры.
     */
    public function loadFixture($tableName)
    {
        // вырезаем из названия таблицы ее префикс, если он есть
        if (($prefix = $this->getDbConnection()->tablePrefix) !== null) {
            $tableName = str_replace($prefix, '', $tableName);
        }
        
        // проверка наличия файла с фикстурой
        // (возможные форматы названия файла:
        // "tablename.php" или "databasename.tablename.php",
        // где tablename - название таблицы, databasename - название БД;
        // кавычек быть не должно.)
        // (это нужно для одноимённых таблиц, но для разных БД - во избежание путанницы!)
        $fileName = $this->basePath . DIRECTORY_SEPARATOR . $tableName . '.php';
        if (!is_file($fileName)) {
            $fileName = $this->basePath . DIRECTORY_SEPARATOR . $this->getDbName() . '.' . $tableName . '.php';
            if (!is_file($fileName)) {
                return false;
            }
        }
        
        // массив вставленных строк
        $rows = array();
        // схема для работы с БД для текущего дескриптора БД
        $schema = $this->getDbConnection()->getSchema();
        // генератор SQL-команд
        $builder = $schema->getCommandBuilder();
        // метаданные для таблицы БД
        $table = $schema->getTable($tableName);
        
        // цикл по массиву фикстуры из найденного файла фикстуры
        foreach (require($fileName) as $alias => $row) {
            // вставка строки из массива в таблицу БД
            $builder->createInsertCommand($table, $row)->execute();
            $primaryKey = $table->primaryKey;
            if ($table->sequenceName !== null) {
                if (is_string($primaryKey) && !isset($row[$primaryKey])) {
                    $row[$primaryKey] = $builder->getLastInsertID($table);
                } elseif(is_array($primaryKey)) {
                    foreach ($primaryKey as $pk) {
                        if (!isset($row[$pk])) {
                            $row[$pk] = $builder->getLastInsertID($table);
                            break;
                        }
                    }
                }
            }
            $rows[$alias] = $row;
        }
        // возврат массива со вставленными строками
        return $rows;
    }

    /**
     * Получение названия БД через парсинг параметров соединения
     * в дескрипторе подключения к БД.
     * 
     * @return string название БД
     */
    protected function getDbName()
    {
        if (!empty($this->getDbConnection()->connectionString)) {
            $arr1 = explode(';', $this->getDbConnection()->connectionString);
            if (!empty($arr1)) {
                foreach ($arr1 as $item1) {
                    $arr2 = explode('=', $item1);
                    if ($arr2[0] === 'dbname') {
                        return trim($arr2[1]);
                    }
                }
            }
        }
        return '';
    }
    
}