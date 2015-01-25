<?php

namespace library;

// определение класса-потомка от CUploadedFile (для тестирования загрузок картинок)

/**
 * UploadedFile - хранит информацию о загруженном файле.
 *
 * @property string $name - оригинальное имя загруженного файла.
 * @property string $tempName - путь к временному файлу на сервере.
 * @property string $type - MIME-тип загруженного файла (например, "image/gif").
 * @property integer $size - реальный размер загруженного файла в байтах.
 * @property integer $error - код ошибки.
 * @property boolean $hasError - наличие ошибки при загрузке файла.
 * @property string $extensionName - расширение файла, без точки.
 */

class UploadedFile extends \CUploadedFile
{

    static private $__files;

    private $__name;
    private $__tempName;
    private $__type;
    private $__size;
    private $__error;

	/**
	 * Конструктор класса.
	 * 
     * @param string $name - оригинальное имя загруженного файла.
     * @param string $tempName - путь к временному файлу на сервере.
     * @param string $type - MIME-тип загруженного файла (например, "image/gif").
     * @param integer $size - реальный размер загруженного файла в байтах.
     * @param integer $error - код ошибки.
	 */
    public function __construct($name, $tempName, $type, $size, $error)
    {
        $this->__name     = $name;
        $this->__tempName = $tempName;
        $this->__type     = $type;
        $this->__size     = $size;
        $this->__error    = $error;
    }

	/**
	 * Вывод строки.
     * PHP magic-метод, который возвращает строковое представление объекта.
	 * Здесь это название загруженного файла.
	 * @return string строковое представление объекта
	 */
    public function __toString()
    {
        return $this->__name;
    }

	/**
	 * Сохранение загруженного файла.
     * 
	 * @param string $file путь для сохранения загруженного файла.
	 * @param boolean $deleteTempFile удалять ли временный файл после сохранения.
	 * @return boolean был ли файл успешно сохранен
	 */
    public function saveAs($file, $deleteTempFile = true)
    {
        if ($this->__error == UPLOAD_ERR_OK) {
            if ($deleteTempFile) {
                return move_uploaded_file($this->__tempName, $file);
            } elseif (is_uploaded_file($this->__tempName)) {
                return copy($this->__tempName, $file);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getName()
    {
        return $this->__name;
    }

    public function getTempName()
    {
        return $this->__tempName;
    }

    public function getType()
    {
        return $this->__type;
    }

    public function getSize()
    {
        return $this->__size;
    }

    public function getError()
    {
        return $this->__error;
    }

    public function getHasError()
    {
        return $this->__error != UPLOAD_ERR_OK;
    }

    public function getExtensionName()
    {
        if (($pos = strrpos($this->__name, '.')) !== false) {
            return (string) substr($this->__name, $pos + 1);
        } else {
            return '';
        }
    }

}