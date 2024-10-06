<?php
namespace FreePBX\modules\Endpointman\Provisioner;

require_once(__DIR__.'/../epm_system.class.php');

class ProvisionerBaseDB
{
    protected $debug  = false;
    protected $system = null;
    protected $parent = null;

    /**
     * Constructor.
     */
    public function __construct($debug = true)
    {
        $this->parent    = null;
        $this->debug     = $debug;
        $this->system    = new \FreePBX\modules\Endpointman\epm_system();
    }
    

    public function sendDebug($message)
    {
        if ($this->debug)
        {
            dbug($message);
        }
    }

    /**
     * Retrieves the parent.
     *
     * @return mixed The parent.
     */
    public function getParent()
    {
        return $this->parent;
    }
    
    /**
     * Sets the parent.
     *
     * @param mixed $new_parent The new parent.
     * @return mixed The new parent.
     */
    public function setParent($new_parent = null)
    {
        $this->parent = $new_parent;
        return $this->parent;
    }

    /**
     * Checks if the parent is set.
     *
     * @return bool True if the parent is set, false otherwise.
     */
    public function isParentSet()
    {
        return !empty($this->parent);
    }


    public function isSetDB()
    {
        if (! $this->isParentSet())
        {
            return false;
        }
        if (empty($this->parent->db))
        {
            return false;
        }
        return true;
    }


    /**
     * Insert data into a table.
     * 
     * @param string $table The table to insert into.
     * @param array $newdata The data to insert.
     * @param array $primary_key The primary key. Default is an empty array.
     * @return mixed The last insert id or false if the data is not inserted.
     * 
     * @example
     * $datos = [
     * 'name' => 'pepe',
     * 'surname' => 'garcia',
     * ];
     * $this->insertQuery("table", $datos);
     * Insert into table (name, surname) values ('pepe', 'garcia')
     * 
     * @example
     * $datos = [
     * 'name' => 'pepe',
     * 'surname' => 'garcia',
     * ];
     * $this->insertQuery("table", $datos, ['id']);
     * Insert into table (name, surname) values ('pepe', 'garcia') on duplicate key update name = 'pepe', surname = 'garcia'
     * 
     * @example
     * $datos = [
     * 'name' => 'pepe',
     * 'surname' => 'garcia',
     * ];
     * $this->insertQuery("table", $datos, ['id', 'name']);
     * Insert into table (name, surname) values ('pepe', 'garcia') on duplicate key update surname = 'garcia'
     * 
     * @example
     * $datos = [
     * 'name' => 'pepe',
     * 'surname' => 'garcia',
     * ];
     * $this->insertQuery("table", $datos, ['id', 'name', 'surname']);
     * Insert into table (name, surname) values ('pepe', 'garcia')
     * 
     */
    protected function insertQuery(string $table, array $newdata, array $primary_key = [])
    {
        if (! $this->isSetDB())
        {
            return false;
        }
        if (empty($newdata) || !is_array($newdata))
        {
            return false;
        }
        $params = [];
        $sql    = sprintf("INSERT INTO %s", $table);
        $keys   = [];
        $values = [];
        foreach ($newdata as $key => $value)
        {
            $keys[] = $key;
            $values[] = ":$key";
            $params[":$key"] = $value;
        }
        $sql .= sprintf(" (%s) VALUES (%s)", implode(", ", $keys), implode(", ", $values));

        if (! empty($primary_key))
        {
            $update = [];
            foreach ($newdata as $key => $value)
            {
                if (in_array($key, $primary_key))
                {
                    continue;
                }
                $update[] = sprintf("%s = :%s", $key, $key);
                $params[":$key"] = $value;
            }
            if (! empty($update))
            {
                $sql .= sprintf(" ON DUPLICATE KEY UPDATE %s", implode(", ", $update));
            }   
        }

        $stmt = $this->parent->db->prepare($sql);
        $stmt->execute($params);
        return $this->parent->db->lastInsertId();
    }


    /**
     * Executes a query.
     * 
     * @param string $table The table.
     * @param string $select The select.
     * @param array $where The where clause as an array or a string.
     *      - If it is an array, it must be like this:
     *         [
     *            'id' => [
     *               'operator' => "=",
     *              'value'    => '18'
     *         ],
     *     ]
     *     - If it is a string, it must be like this (using the default operator "=" and the column "id"): "18"
     * @param string $limit The limit.
     * @param bool $getFirstCol True to get the first column only is limit is 1, false get all columns. Default is false.
     * @param string $order The order.
     * @param string $order_dir The order direction. Default is "ASC".
     * @return mixed The result. If the limit is 1 and getFirstCol is true, it returns the first column only.
     * @throws \Exception If the database is not set.
     * @throws \Exception If the table is empty.
     * 
     * @example
     * $where = [
     *    'id' => ['operator' => "=", 'value' => '18'],
     * ];
     * $this->query("tabla", "id, name", $where);
     * return [ ['id' => '18', 'name' => 'Jon'] ];
     * Return data from a table with a where clause, in this case, the table is "tabla", the select is "id, name", and the where clause is "id = 18".
     * 
     * @example
     * $where = "18";
     * $this->query("tabla", "id, name", $where);
     * return [ ['id' => '18', 'name' => 'Jon'] ];
     * Return data from a table with a where clause, in this case, the table is "tabla", the select is "id, name", and the where clause is "id = 18".
     * 
     * @example
     * $where = [
     *   'name' => ['operator' => "LIKE", 'value' => 'Jon'],
     * ];
     * $this->query("tabla", "id, name", $where);
     * return [ ['id' => '18', 'name' => 'Jon'] ];
     * Return data from a table with a where clause, in this case, the table is "tabla", the select is "id, name", and the where clause is "name LIKE 'Jon'".
     * 
     * @example
     * $this->query("tabla", "id, name");
     * return [ ['id' => '18', 'name' => 'Jon'] ];
     * Return all data from a table, in this case, the table is "tabla", and the select is "id, name".
     * 
     * @example
     * $this->query("tabla", "id, name", [], 1, true);
     * return "18";
     * Return the first column from a table, in this case, the table is "tabla", the select is "id, name", and the limit is 1.
     * 
     */
    protected function querySelect(string $table, string $select = "*", $where = [], $limit = "", $getFirstCol = false, $order = "", $order_dir = "ASC")
    {
        if (! $this->isSetDB())
        {
            return false;
        }
        if (empty($table) || ! is_string($table))
        {
            return false;
        }
        if (empty($select))
        {
            $select = "*";
        }
        if (empty($order_dir))
        {
            $order_dir = "ASC";
        }
        else if (in_array(strtoupper($order_dir), ["ASC", "DESC"]))
        {
            $order_dir = "ASC";
        }

        $params = [];
        $sql    = sprintf("SELECT %s FROM %s", $select, $table);
        if (! empty($where))
        {
            if (is_string($where) || is_numeric($where))
            {
                $where = [
                    'id' => [
                        'operator' => "=", 
                        'value'    => $where
                    ],
                ];
            }

            if (! is_array($where))
            {
                $where = [];
            }
            $where_keys = [];
            foreach ($where as $key => $value)
            {
                $where_keys[] = sprintf("%s %s :%s", $key, $value['operator'], $key);
                $params[":$key"] = $value['value'];
            }
            if (!empty($where_keys))
            {
                $sql .= " WHERE " . implode(" AND ", $where_keys);
            }
        }
        if (! empty($order))
        {
            $sql .= " ORDER BY $order $order_dir";
        }
        if (! empty($limit))
        {
            $sql .= " LIMIT $limit";
        }

        $stmt = $this->parent->db->prepare($sql);
        $stmt->execute($params);
        if ($limit == 1)
        {
            if ($getFirstCol)
            {
                $data = $stmt->fetch(\PDO::FETCH_BOTH);
                $data = $data[0] ?? "";
            }
            else
            {
                $data = $stmt->fetch(\PDO::FETCH_ASSOC);
            }
            return $data;
        }
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Update data in a table.
     * 
     * @param string $table The table to update.
     * @param array $data The data to update.
     * @param array $where The where clause as an array or a string. 
     *     - If it is an array, it must be like this:
     *        [
     *          'id' => [
     *            'operator' => "=",  (default is "=", can be "=", "<", ">", "<=", ">=", "<>", "LIKE", "IN", "NOT IN", "IS NULL", "IS NOT NULL")
     *            'value'    => '18'
     *       ],
     *  ]
     * - If it is a string, it must be like this (using the default operator "=" and the column "id"): "18"
     * @return bool True if the data is updated, false otherwise.
     * @example
     * $datos = [
     * 'name' => 'pepe',
     * 'surname' => 'garcia',
     * ];
     * $where = [
     * 'id' => ['operator' => "=", 'value' => '18'],
     * ];
     * $this->updateQuery("table", $datos, $where);
     * Update table set name = 'pepe', surname = 'garcia' where id = 18
     * 
     * @example
     * $datos = [
     * 'name' => 'pepe',
     * 'surname' => 'garcia',
     * ];
     * $where = "18";
     * $this->updateQuery("table", $datos, $where);
     * Update table set name = 'pepe', surname = 'garcia' where id = 18
     * 
     * @example
     * $datos = [
     * 'enabled' => '0',
     * ];
     * $where = [
     * 'name' => ['operator' => "LIKE", 'value' => 'pepe'],
     * ];
     * $this->updateQuery("table", $datos, $where);
     * Update table set enabled = '0' where name LIKE 'pepe'
     */
    protected function updateQuery(string $table, array $data = [], $where = [])
    {
        if (! $this->isSetDB())
        {
            return false;
        }
        if (empty($table) || !is_string($table))
        {
            return false;
        }
        if (empty($data) || !is_array($data))
        {
            return false;
        }
        if (empty($where) || (!is_array($where) && !is_string($where)))
        {
            return false;
        }
        $params    = [];
        $sql       = sprintf("UPDATE %s SET ", $table);

        // Process the data
        $data_keys = [];
        foreach ($data as $key => $value)
        {
            if (empty($value))
            {
                $value = 0;
            }
            $data_keys[] = sprintf("%s = :%s", $key, $key);
            $params[":$key"] = $value;
        }

        if (empty($data_keys))
        {
            return false;
        }
        $sql .= implode(", ", $data_keys);

        if (is_string($where) || is_numeric($where))
        {
            $where = [
                'id' => [
                    'operator' => "=", 
                    'value'    => $where
                ],
            ];
        }

        // Process the where
        $where_keys = [];
        foreach ($where as $key => $value)
        {
            if (empty($value) || !is_array($value))
            {
                continue;
            }
            $where_keys[] = sprintf("%s %s :%s", $key, $value['operator'], $key);
            $params[":$key"] = $value['value'];
        }
        if (! empty($where_keys))
        {
            $sql .= " WHERE " . implode(" AND ", $where_keys);
        }

        $stmt = $this->parent->db->prepare($sql);
        $stmt->execute($params);
        return true;
    }


    /**
     * Replace data in a table.
     * 
     * @param string $table The table to replace.
     * @param array $data The data to replace.
     * @return bool True if the data is replaced, false otherwise.
     * 
     * @example
     * $datos = [
     *      'name' => 'pepe',
     *      'surname' => 'garcia',
     * ];
     * $this->remplaceQuery("table", $datos);
     * Replace into table (name, surname) values ('pepe', 'garcia') or create a new row if it does not exist
     */
    protected function remplaceQuery(string $table, array $data = [])
    {
        if (! $this->isSetDB())
        {
            return false;
        }
        if (empty($table) || !is_string($table))
        {
            return false;
        }
        if (empty($data) || !is_array($data))
        {
            return false;
        }
        $params = [];
        $sql    = sprintf("REPLACE INTO %s", $table);
        $keys   = [];
        $values = [];
        foreach ($data as $key => $value)
        {
            $keys[] = $key;
            $values[] = ":$key";
            $params[":$key"] = $value;
        }
        $sql .= sprintf(" (%s) VALUES (%s)", implode(", ", $keys), implode(", ", $values));

        $stmt = $this->parent->db->prepare($sql);
        $stmt->execute($params);
        return true;
    }

    /**
     * Delete data from a table.
     * 
     * @param string $table The table to delete from.
     * @param array|string $where The where clause as an array or a string.
     *    - If it is an array, it must be like this:
     *      [
     *       'id' => [
     *        'operator' => "=",  (default is "=", can be "=", "<", ">", "<=", ">=", "<>", "LIKE", "IN", "NOT IN", "IS NULL", "IS NOT NULL")
     *       'value'    => '18'
     *   ],
     * ]
     * - If it is a string, it must be like this (using the default operator "=" and the column "id"): "18"
     * @return bool True if the data is deleted, false otherwise.
     * @example
     * $where = [
     * 'id' => ['operator' => "=", 'value' => '18'],
     * ];
     * $this->deleteQuery("table", $where);
     * Delete from table where id = 18
     * 
     * @example
     * $where = "18";
     * $this->deleteQuery("table", $where);
     * Delete from table where id = 18
     * 
     * @example
     * $where = [
     * 'name' => ['operator' => "LIKE", 'value' => 'pepe'],
     * ];
     * $this->deleteQuery("table", $where);
     * Delete from table where name LIKE 'pepe'
     * 
     * @example
     * $this->deleteQuery("table");
     * Delete all from table
     */
    protected function deleteQuery($table, $where)
    {
        if (! $this->isSetDB())
        {
            return false;
        }
        if (empty($table) || !is_string($table))
        {
            return false;
        }

        if (!empty($where))
        {   
            if (is_string($where) || is_numeric($where))
            {
                $where = [
                    'id' => [
                        'operator' => "=", 
                        'value'    => $where
                    ],
                ];
            }
            if (! is_array($where))
            {
                $where = [];
            }
        }

        $sql = sprintf("DELETE FROM %s", $table);
        if (! empty($where))
        {
            $where_keys = [];
            foreach ($where as $key => $value)
            {
                if (empty($value) || !is_array($value))
                {
                    continue;
                }
                $where_keys[] = sprintf("%s %s :%s", $key, $value['operator'] ?? "=", $key);
                $params[":$key"] = $value['value'];
            }
            if (! empty($where_keys))
            {
                $sql .= sprintf(" WHERE %s", implode(" AND ", $where_keys));
            }
        }
        $stmt = $this->parent->db->prepare($sql);
        $stmt->execute($params);
        return true;
    }
}