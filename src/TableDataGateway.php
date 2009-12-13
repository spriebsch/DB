<?php
/**
 * Copyright (c) 2009 Stefan Priebsch <stefan@priebsch.de>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *   * Redistributions of source code must retain the above copyright notice,
 *     this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright notice,
 *     this list of conditions and the following disclaimer in the documentation
 *     and/or other materials provided with the distribution.
 *
 *   * Neither the name of Stefan Priebsch nor the names of contributors
 *     may be used to endorse or promote products derived from this software
 *     without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER ORCONTRIBUTORS
 * BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    DB
 * @author     Stefan Priebsch <stefan@priebsch.de>
 * @copyright  Stefan Priebsch <stefan@priebsch.de>. All rights reserved.
 * @license    BSD License
 */

namespace spriebsch\DB;

/**
 * Table Data Gateway class.
 *
 * @author Stefan Priebsch <stefan@priebsch.de>
 * @copyright Stefan Priebsch <stefan@priebsch.de>. All rights reserved.
 * @todo re-introduce typecast code
 * @todo make id column name flexible
 * @allow generic sql statement
 */
class TableDataGateway
{
    /**
     * @var spriebsch\DB\DatabaseHandler
     */
	protected $db;
	
	/**
	 * The database table this gateway is bound to.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * Name of the primary key column. Defaults to "id".
	 *
	 * @var string
	 */
	protected $idColumn;
	
	/**
	 * Database type map containing the PDO types for each column.
	 *
	 * @var array
	 */
	protected $dbTypes = array();

    /**
     * Cache for prepared statements.
     *
     * @var array
     */	
	protected $statements = array();

    /**
     * Constructs the object.
     *
     * @param spriebsch\DB\DatabaseHandler $db
     * @param string $table DB table name
     * @param array $dbTypes
     * @param string $idColumn DB column name containing the primary key
     */
    public function __construct(DatabaseHandler $db, $table, $idColumn, $dbTypes)
	{
        $this->db       = $db;
        $this->table    = $table;
        $this->idColumn = $idColumn;
        $this->dbTypes  = $dbTypes;
	}

    /**
     * Returns the PDO data type for given column.
     *
     * @param string $column DB column name
     */	
	protected function getType($column)
	{
	    if (!isset($this->dbTypes[$column])) {
	        throw new DatabaseException('No type for column "' . $column . '"');
	    }
	    
	    return $this->dbTypes[$column];
	}

    /**
     * Prepares and caches a SQL statement.
     *
     * @param string $sql
     * @return PDOStatement
     *
     * @throws spriebsch\DB\DatabaseException Failed to prepare statement
     */
	protected function prepare($sql)
	{
	    if (!isset($this->statements[$sql])) {
            $statement = $this->db->prepare($sql);

            if ($statement === false) {
                $message = $this->db->errorInfo();            
                throw new DatabaseException('Failed to prepare statement: ' . $message[2]);
            }

            $this->statements[$sql] = $statement;
	    }

        return $this->statements[$sql];
	}

    /**
     * Binds parameters to prepared statement.
     *
     * @param PDOStatement $statement
     * @param array $values Associative array with column => value pairs
     * @return null
     */	
	protected function bindParameters(\PDOStatement $statement, array $values)
	{
        foreach ($values as $key => $value) {
            $statement->bindValue(':' . $key, $value, $this->getType($key));
        }
	}

    /**
     * Returns a record by ID.
     * Will throw an exception if no record was found.
     *
     * @param int $id
     * @return array
     *
     * @throws InvalidArgumentException ID is not an integer
     * @throws spriebsch\DB\DatabaseException Find ID failed on table
     * @throws spriebsch\DB\DatabaseException Record ID not found
     */
	public function find($id)
	{
        if (!is_int($id)) {
            throw new \InvalidArgumentException('ID "' . $id . '" is not an integer');
        }
		
        $statement = $this->prepare('SELECT * FROM ' . $this->table . ' WHERE ' . $this->idColumn . '=:' . $this->idColumn . ';');

        $statement->bindValue(':' . $this->idColumn, $id, $this->getType($this->idColumn));
        $statement->execute();
        
        if ($statement->errorCode() != 0) {
            $message = $statement->errorInfo();
            throw new DatabaseException('Find "' . $id . '" failed on table "' . $this->table . '": ' . $message[2]);
        }        

        $result = $statement->fetch(\PDO::FETCH_ASSOC);
        
        if ($result === false) {
        	throw new DatabaseException('Record ID "' . $id . '" not found');
        }
        
        return $result;
	}

    /**
     * Run a select statement.
     *   
     * @param array $criteria
     * @param bool $justOne Only return first result record when set  
     * @return array
     * @todo cache it anyway (map of cached statements?) -> generic statement cache?
     */
    public function select(array $criteria, $justOne = false)
    {
    	$sql = 'SELECT * FROM ' . $this->table . ' WHERE ';
        $fields = array();

        foreach (array_keys($criteria) as $key) {
            $fields[] = $key . '=:' . $key;           
        }

        $sql .= implode(' AND ', $fields);
    	
        $statement = $this->prepare($sql);        
        $this->bindParameters($statement, $criteria);
        $statement->execute();
        
        if ($statement->errorCode() != 0) {
            $message = $statement->errorInfo();
            throw new DatabaseException('Select failed on table "' . $this->table . '": ' . $message[2]);
        }
        
        if ($justOne) {
            return $statement->fetch(\PDO::FETCH_ASSOC);
        } else {
            return $statement->fetchAll(\PDO::FETCH_ASSOC);
        }
    }

    /**
     * Select one record.
     * In case of multiple result rows, 
     * @param array $criteria
     * @return array
     */
    public function selectOne(array $criteria)
    {
        return $this->select($criteria, true);
    }

    /**
     * Updates a row.
     *
     * @param array $record
     * @return bool
     *
     * @throws InvalidArgumentException Record has no ID column
     * @throws InvalidArgumentException ID is not an integer
     * @throws spriebsch\DB\DatabaseException Update ID failed on table
     */
	public function update(array $record)
	{
        if (!isset($record[$this->idColumn])) {
            throw new \InvalidArgumentException('Record has no ID column "' . $this->idColumn . '"');
        }

        if (!is_int($record[$this->idColumn])) {
            throw new \InvalidArgumentException('ID "' . $record[$this->idColumn] . '" is not an integer');
        }

        $sql = 'UPDATE ' . $this->table . ' SET ';
        $fields = array();

        $data = $record;

        // We don't have to update the ID column.
        unset($data[$this->idColumn]);
        
        foreach (array_keys($data) as $key) {
            $fields[] = $key . '=:' . $key;           
        }
    
        $sql .= implode(',', $fields) . ' WHERE ' . $this->idColumn . '=:' . $this->idColumn . ';';

        $statement = $this->prepare($sql);
        $this->bindParameters($statement, $record);
        $statement->execute();

        if ($statement->errorCode() != 0) {
            $message = $statement->errorInfo();
            throw new DatabaseException('Update ID "' . $record[$this->idColumn] . '" failed on table "' . $this->table . '": ' . $message[2]);
        }        

        return $statement->rowCount() == 1;
	}

	/**
	 * Insert new row into table.
     * Returns the ID of the inserted record.
	 *
	 * @param array $record Associative array with column => value entries
	 * @return int ID of the inserted record
	 * @exception when col not configured, or fallback to default cast?
	 */
	public function insert(array $record)
	{
        if (isset($record[$this->idColumn])) {
            throw new DatabaseException('Record to insert already has an ID');
        }

    	$sql = 'INSERT INTO ' . $this->table . ' (' . implode(',', array_keys($record)) . ') VALUES (';
    	$placeholders = array();

    	foreach (array_keys($record) as $key) {
            $placeholders[] = ':' . $key;        	
        }
    
        $sql .= implode(',', $placeholders) . ');';
        
        $statement = $this->prepare($sql);
        $this->bindParameters($statement, $record);
        $statement->execute();
        
        if ($statement->errorCode() != 0) {
        	$message = $statement->errorInfo();
        	throw new DatabaseException('Insert failed on table "' . $this->table . '": ' . $message[2]);
        }        

        return $this->db->lastInsertId();
	}

	/**
	 * Deletes a record.
	 * Returns a boolean value.
	 * 
	 * @param int $id ID of the row to delete
	 * @return bool
     *
     * @throws InvalidArgumentException ID is not an integer
     * @throws spriebsch\DB\DatabaseException Delete ID failed on table
	 */    /**
     * Returns all records in the table.
     *
     * @return array
     *
     * @throws spriebsch\DB\DatabaseException FindAll failed on table
     */
    public function findAll()
    {
        $statement = $this->prepare('SELECT * FROM ' . $this->table);
        $statement->execute();
        
        if ($statement->errorCode() != 0) {
            $message = $statement->errorInfo();
            throw new DatabaseException('FindAll failed on table "' . $this->table . '": ' . $message[2]);
        }        

        return $statement->fetchAll(\PDO::FETCH_ASSOC);        
    }

    /**
     * Deletes a record.
     *
     * @param int $id ID of the record to delete
     * @return bool
     */
	public function delete($id)
	{
		if (!is_int($id)) {
			throw new \InvalidArgumentException('ID "' . $id . '" is not an integer');
		}

        $statement = $this->prepare('DELETE FROM ' . $this->table . ' WHERE ' . $this->idColumn . '=:' . $this->idColumn . ';');
        $statement->bindValue(':' . $this->idColumn, $id, $this->getType($this->idColumn));
        $statement->execute();
        
        if ($statement->errorCode() != 0) {
            $message = $statement->errorInfo();
            throw new DatabaseException('Delete ID "' . $id . '" failed on table "' . $this->table . '": ' . $message[2]);
        }        

        return $statement->rowCount() == 1;
	}
}
?>
