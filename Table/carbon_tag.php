<?php
namespace Table;


use CarbonPHP\Database;
use CarbonPHP\Entities;
use CarbonPHP\Interfaces\iRest;

class carbon_tag extends Entities implements iRest
{
    const PRIMARY = "entity_id";

    const COLUMNS = [
    'entity_id','user_id','table_name','creation_date',
    ];

    const BINARY = [
    'entity_id','user_id',
    ];

    /**
     * @param array $return
     * @param string|null $primary
     * @param array $argv
     * @return bool
     */
    public static function Get(array &$return, string $primary = null, array $argv) : bool
    {
        if (isset($argv['limit'])){
            if ($argv['limit'] !== '') {
                $pos = strrpos($argv['limit'], "><");
                if ($pos !== false) { // note: three equal signs
                    substr_replace($argv['limit'],',',$pos, 2);
                }
                $limit = ' LIMIT ' . $argv['limit'];
            } else {
                $limit = '';
            }
        } else {
            $limit = ' LIMIT 100';
        }

        $get = isset($argv['select']) ? $argv['select'] : self::COLUMNS;
        $where = isset($argv['where']) ? $argv['where'] : [];

        $sql = '';
        foreach($get as $key => $column){
            if (!empty($sql)) {
                $sql .= ', ';
            }
            if (in_array($column, self::BINARY)) {
                $sql .= "HEX($column) as $column";
            } else {
                $sql .= $column;
            }
        }

        $sql = 'SELECT ' .  $sql . ' FROM statscoach.carbon_tag';

        $pdo = Database::database();

        if ($primary === null) {
            if (!empty($where)) {
                $build_where = function (array $set, $join = 'AND') use (&$pdo, &$build_where) {
                    $sql = '(';
                    foreach ($set as $column => $value) {
                        if (is_array($value)) {
                            $build_where($value, $join === 'AND' ? 'OR' : 'AND');
                        } else {
                            if (in_array($column, self::BINARY)) {
                                $sql .= "($column = UNHEX(" . $pdo->quote($value) . ")) $join ";
                            } else {
                                $sql .= "($column = " . $pdo->quote($value) . ") $join ";
                            }
                        }
                    }
                    return substr($sql, 0, strlen($sql) - (strlen($join) + 1)) . ')';
                };
                $sql .= ' WHERE ' . $build_where($where);
            }
        } else if (!empty(self::PRIMARY)){
            $sql .= ' WHERE ' . self::PRIMARY . '=UNHEX(' . $pdo->quote($primary) . ')';
        }

        $sql .= $limit;

        $return = self::fetch($sql);

        return true;
    }

    /**
    * @param array $argv
    * @return bool|mixed
    */
    public static function Post(array $argv)
    {
        $sql = 'INSERT INTO statscoach.carbon_tag (entity_id, user_id, table_name, creation_date) VALUES ( UNHEX(:entity_id), :user_id, :table_name, :creation_date)';
        $stmt = Database::database()->prepare($sql);
            $entity_id = $id = isset($argv['entity_id']) ? $argv['entity_id'] : self::new_entity('carbon_tag');
            $stmt->bindParam(':entity_id',$entity_id, \PDO::PARAM_STR, 16);
            
                $user_id = isset($argv['user_id']) ? $argv['user_id'] : null;
                $stmt->bindParam(':user_id',$user_id, \PDO::PARAM_STR, 16);
                    
                $table_name = isset($argv['table_name']) ? $argv['table_name'] : null;
                $stmt->bindParam(':table_name',$table_name, \PDO::PARAM_STR, 50);
                    
                $creation_date = isset($argv['creation_date']) ? $argv['creation_date'] : null;
                $stmt->bindParam(':creation_date',$creation_date, \PDO::PARAM_STR, 20);
        
        return $stmt->execute() ? $id : false;

    }

    /**
    * @param array $return
    * @param string $id
    * @param array $argv
    * @return bool
    */
    public static function Put(array &$return, string $id, array $argv) : bool
    {
        foreach ($argv as $key => $value) {
            if (!in_array($key, self::COLUMNS)){
                unset($argv[$key]);
            }
        }

        $sql = 'UPDATE statscoach.carbon_tag ';

        $sql .= ' SET ';        // my editor yells at me if I don't separate this from the above stmt

        $set = '';

        if (isset($argv['entity_id'])) {
            $set .= 'entity_id=UNHEX(:entity_id),';
        }
        if (isset($argv['user_id'])) {
            $set .= 'user_id=UNHEX(:user_id),';
        }
        if (isset($argv['table_name'])) {
            $set .= 'table_name=:table_name,';
        }
        if (isset($argv['creation_date'])) {
            $set .= 'creation_date=:creation_date,';
        }

        if (empty($set)){
            return false;
        }

        $set = substr($set, 0, strlen($set)-1);

        $sql .= $set . ' WHERE ' . self::PRIMARY . "='$id'";

        $stmt = Database::database()->prepare($sql);

        if (isset($argv['entity_id'])) {
            $entity_id = 'UNHEX('.$argv['entity_id'].')';
            $stmt->bindParam(':entity_id', $entity_id, \PDO::PARAM_STR, 16);
        }
        if (isset($argv['user_id'])) {
            $user_id = 'UNHEX('.$argv['user_id'].')';
            $stmt->bindParam(':user_id', $user_id, \PDO::PARAM_STR, 16);
        }
        if (isset($argv['table_name'])) {
            $table_name = $argv['table_name'];
            $stmt->bindParam(':table_name',$table_name, \PDO::PARAM_STR, 50 );
        }
        if (isset($argv['creation_date'])) {
            $creation_date = $argv['creation_date'];
            $stmt->bindParam(':creation_date',$creation_date, \PDO::PARAM_STR, 20 );
        }

        if (!$stmt->execute()){
            return false;
        }

        $return = array_merge($return, $argv);

        return true;

    }

    /**
    * @param array $remove
    * @param string|null $primary
    * @param array $argv
    * @return bool
    */
    public static function Delete(array &$remove, string $primary = null, array $argv) : bool
    {
        return \Table\carbon::Delete($remove, $primary, $argv);
    }
}