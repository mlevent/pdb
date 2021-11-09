<?php

namespace Mlevent;

class Structure
{
    private $pdb;

    public function __construct(Pdb $pdb)
    {
        $this->pdb = $pdb;
    }

    public function getPrimary()
    {
        return $this->pdb->pdo->query($this->getQuery('getPrimary'))
                    ->fetchColumn();
    }

    public function getColumns()
    {
        $columns = $this->pdb->pdo->query($this->getQuery('getColumns'))
                        ->fetchAll(2);
                        
        return call_user_func(array($this, $this->pdb->config['driver'] . 'ColParser'), $columns);
    }

    private function sqliteColParser($columns)
    {
        $parse = [];
        foreach($columns as $col) 
        {
            $parse[$col['name']] = [
                'field'   => $col['name'],
                'type'    => $col['type'],
                'null'    => !$col['notnull'],
                'default' => $col['dflt_value'],
                'primary' => $col['pk'],
                'extra'   => $col['pk']
            ];
        }
        return $parse;
    }

    private function mysqlColParser($columns)
    {
        $parse = [];
        foreach($columns as $col) 
        {
            $parse[$col['Field']] = [
                'field'   => $col['Field'],
                'type'    => $col['Type'],
                'null'    => $col['Null'] !== 'NO' ? true : false,
                'default' => $col['Default'],
                'primary' => $col['Key'] === 'PRI' ? true : false,
                'extra'   => $col['Extra']
            ];
        }
        return $parse;
    }

    private function getQuery($query)
    {
        $queries = [
            'mysql' => [
                'getColumns' => "SHOW COLUMNS FROM {$this->pdb->table}",
                'getPrimary' => "SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = '{$this->pdb->table}' AND CONSTRAINT_NAME = 'PRIMARY'",
            ],
            'sqlite' => [
                'getColumns' => "PRAGMA table_info({$this->pdb->table})",
                'getPrimary' => "SELECT t.name FROM pragma_table_info('{$this->pdb->table}') as t WHERE t.pk = 1"
            ]
        ];
        return $queries[$this->pdb->config['driver']][$query];
    }
}