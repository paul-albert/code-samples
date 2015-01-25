<?php

namespace library;

/**
 * Переопределение стандартных PHP-функций
 */

function is_uploaded_file($filename)
{
    // проверяет только наличие самого файла по его пути
    return file_exists($filename);
}

function move_uploaded_file($filename, $destination)
{
    // только копирует файл
    return copy($filename, $destination);
}
