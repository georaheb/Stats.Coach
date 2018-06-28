<?php

namespace Table;

use CarbonPHP\Database;
use CarbonPHP\Entities;
use CarbonPHP\Error\PublicAlert;
use CarbonPHP\Interfaces\iRest;

class sessions extends Entities implements iRest
{
    const COLUMNS = [
            'user_id',
            'user_ip',
            'session_id',
            'session_expires',
            'session_data',
            'user_online_status',
    ];

    const PRIMARY = "session_i";

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

        $get = $where = [];
        foreach ($argv as $column => $value) {
            if (!is_int($column) && in_array($column, self::COLUMNS)) {
                if ($value !== '') {
                    $where[$column] = $value;
                } else {
                    $get[] = $column;
                }
            } elseif (in_array($value, self::COLUMNS)) {
                $get[] = $value;
            }
        }

        $get =  !empty($get) ? implode(", ", $get) : ' * ';

        $sql = 'SELECT ' .  $get . ' FROM statscoach.sessions';

        if ($primary === null) {
            $sql .= ' WHERE ';
            foreach ($where as $column => $value) {
                $sql .= "($column = " . Database::database()->quote($value) . ') AND ';
            }
            $sql = substr($sql, 0, strlen($sql)-4);
        } else if (!empty(self::PRIMARY)){
            $sql .= ' WHERE ' . self::PRIMARY . '=' . Database::database()->quote($primary);
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
        $sql = 'INSERT INTO statscoach.sessions (user_id, user_ip, session_id, session_expires, session_data, user_online_status) VALUES (:user_id, :user_ip, :session_id, :session_expires, :session_data, :user_online_status)';
        $stmt = Database::database()->prepare($sql);
            $stmt->bindValue(':user_id', isset($argv['user_id']) ? $argv['user_id'] : null, \PDO::PARAM_STR);
            $stmt->bindValue(':user_ip', isset($argv['user_ip']) ? $argv['user_ip'] : null, \PDO::PARAM_STR);
            $stmt->bindValue(':session_id', isset($argv['session_id']) ? $argv['session_id'] : null, \PDO::PARAM_STR);
            $stmt->bindValue(':session_expires', isset($argv['session_expires']) ? $argv['session_expires'] : null, \PDO::PARAM_STR);
            $stmt->bindValue(':session_data', isset($argv['session_data']) ? $argv['session_data'] : null, \PDO::PARAM_STR);
            $stmt->bindValue(':user_online_status', isset($argv['user_online_status']) ? $argv['user_online_status'] : null, \PDO::PARAM_NULL);
        return $stmt->execute();
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

        $sql = 'UPDATE statscoach.sessions ';

        $sql .= ' SET ';        // my editor yells at me if I don't separate this from the above stmt

        $set = '';
        if (isset($argv['user_id'])) {
            $set .= 'user_id=:user_id,';
        }
        if (isset($argv['user_ip'])) {
            $set .= 'user_ip=:user_ip,';
        }
        if (isset($argv['session_id'])) {
            $set .= 'session_id=:session_id,';
        }
        if (isset($argv['session_expires'])) {
            $set .= 'session_expires=:session_expires,';
        }
        if (isset($argv['session_data'])) {
            $set .= 'session_data=:session_data,';
        }
        if (isset($argv['user_online_status'])) {
            $set .= 'user_online_status=:user_online_status,';
        }

        if (empty($set)){
            return false;
        }

        $set = substr($set, 0, strlen($set)-1);

        $sql .= $set . ' WHERE ' . self::PRIMARY . "='$id'";

        $stmt = Database::database()->prepare($sql);

        if (isset($argv['user_id'])) {
            $stmt->bindValue(':user_id', $argv['user_id'], \PDO::PARAM_STR);
        }
        if (isset($argv['user_ip'])) {
            $stmt->bindValue(':user_ip', $argv['user_ip'], \PDO::PARAM_STR);
        }
        if (isset($argv['session_id'])) {
            $stmt->bindValue(':session_id', $argv['session_id'], \PDO::PARAM_STR);
        }
        if (isset($argv['session_expires'])) {
            $stmt->bindValue(':session_expires', $argv['session_expires'], \PDO::PARAM_STR);
        }
        if (isset($argv['session_data'])) {
            $stmt->bindValue(':session_data', $argv['session_data'], \PDO::PARAM_STR);
        }
        if (isset($argv['user_online_status'])) {
            $stmt->bindValue(':user_online_status', $argv['user_online_status'], \PDO::PARAM_NULL);
        }


        if (!$stmt->execute()){
            return false;
        }

        $return = array_merge($return, $argv);

        return true;

    }

    /**
    * @param array $return
    * @param string|null $primary
    * @param array $argv
    * @return bool
    */
    public static function Delete(array &$remove, string $primary = null, array $argv) : bool
    {
        $sql = 'DELETE FROM statscoach.sessions ';

        foreach($argv as $column => $constraint){
            if (!in_array($column, self::COLUMNS)){
                unset($argv[$column]);
            }
        }

        if ($primary === null) {
            /**
            *   While useful, we've decided to disallow full
            *   table deletions through the rest api. For the
            *   n00bs and future self, "I got chu."
            */
            if (empty($argv)) {
                return false;
            }
            $sql .= ' WHERE ';
            foreach ($argv as $column => $value) {
                $sql .= " $column =" . Database::database()->quote($value) . ' AND ';
            }
            $sql = substr($sql, 0, strlen($sql)-4);
        } else if (!empty(self::PRIMARY)) {
            $sql .= ' WHERE ' . self::PRIMARY . '=' . Database::database()->quote($primary);
        }

        $remove = null;

        return self::execute($sql);
    }

}