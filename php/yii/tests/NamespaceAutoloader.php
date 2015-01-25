<?php

class NamespaceAutoloader
{
    
    // map for namespace is matching of path in file system
    protected $namespacesMap = array();

    public function addNamespace($namespace, $rootDir)
    {
        if (is_dir($rootDir)) {
            $this->namespacesMap[$namespace] = $rootDir;
            return true;
        }
        return false;
    }

    public function register()
    {
        spl_autoload_register(array($this, 'autoload'));
    }

    protected function autoload($class)
    {
        $pathParts = explode('\\', $class);
        if (is_array($pathParts)) {
            $namespace = array_shift($pathParts);
            if (isset($this->namespacesMap[$namespace]) && $this->namespacesMap[$namespace] != '') {
                require_once($this->namespacesMap[$namespace] . '/' . implode('/', $pathParts) . '.php');
                return true;
            }
        }
        return false;
    }

}