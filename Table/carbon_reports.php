<?php

namespace Table;

use CarbonPHP\Database;
use CarbonPHP\Entities;
use CarbonPHP\Error\PublicAlert;
use CarbonPHP\Interfaces\iRest;

class carbon_reports extends Entities implements iRest
{
    const COLUMNS = [
            'log_level',
            'report',
            'date',
            'call_trace',
    ];

    const PRIMARY = "";

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

        $sql = 'SELECT ' .  $get . ' FROM statscoach.carbon_reports';

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
        $sql = 'INSERT INTO statscoach.carbon_reports (log_level, report, date, call_trace) VALUES (:log_level, :report, :date, :call_trace)';
        $stmt = Database::database()->prepare($sql);
            $stmt->bindValue(':log_level', isset($argv['log_level']) ? $argv['log_level'] : null, \PDO::PARAM_STR);
            $stmt->bindValue(':report', isset($argv['report']) ? $argv['report'] : null, \PDO::PARAM_STR);
            $stmt->bindValue(':date', isset($argv['date']) ? $argv['date'] : null, \PDO::PARAM_STR);
            $stmt->bindValue(':call_trace', isset($argv['call_trace']) ? $argv['call_trace'] : null, \PDO::PARAM_STR);
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

        $sql = 'UPDATE statscoach.carbon_reports ';

        $sql .= ' SET ';        // my editor yells at me if I don't separate this from the above stmt

        $set = '';
        if (isset($argv['log_level'])) {
            $set .= 'log_level=:log_level,';
        }
        if (isset($argv['report'])) {
            $set .= 'report=:report,';
        }
        if (isset($argv['date'])) {
            $set .= 'date=:date,';
        }
        if (isset($argv['call_trace'])) {
            $set .= 'call_trace=:call_trace,';
        }

        if (empty($set)){
            return false;
        }

        $set = substr($set, 0, strlen($set)-1);

        $sql .= $set . ' WHERE ' . self::PRIMARY . "='$id'";

        $stmt = Database::database()->prepare($sql);

        if (isset($argv['log_level'])) {
            $stmt->bindValue(':log_level', $argv['log_level'], \PDO::PARAM_STR);
        }
        if (isset($argv['report'])) {
            $stmt->bindValue(':report', $argv['report'], \PDO::PARAM_STR);
        }
        if (isset($argv['date'])) {
            $stmt->bindValue(':date', $argv['date'], \PDO::PARAM_STR);
        }
        if (isset($argv['call_trace'])) {
            $stmt->bindValue(':call_trace', $argv['call_trace'], \PDO::PARAM_STR);
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
        $sql = 'DELETE FROM statscoach.carbon_reports ';

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