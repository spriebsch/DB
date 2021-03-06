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

use PDO;
use InvalidArgumentException;

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
	
	protected $criterionPostfix = '_CRITERION';

    /**
     * Constructs the object.
     *
     * @param spriebsch\DB\DatabaseHandler $db
     * @param string $table DB table name
     * @param array $dbTypes
     * @param string $idColumn DB column name containing the primary key
     */
    public function __construct(DatabaseHandler $db, $table, $idColumn, array $dbTypes)
	{
        $this->db       = $db;
        $this->table    = $table;
        $this->idColumn = $idColumn;
        $this->dbTypes  = $dbTypes;
	}
	
    protected function fixTypes(array $record)
    {
        $result = array();
         
        foreach ($record as $column => $value) {
            $result[$column] = $this->typeCast($column, $value);
        }
        
        return $result;
    }
	
	protected function typeCast($column, $value)
	{
        if (!isset($this->dbTypes[$column])) {
            return $value;
        }
        
        switch ($this->dbTypes[$column]) {
            case PDO::PARAM_BOOL:
                return (bool) $value;
            break;

            case PDO::PARAM_INT:
                return (int) $value;
            break;
            
            default:
                 return $value;
        }
	}

    /**
     * Returns the PDO data type for given column.
     *
     * @param string $column DB column name
     * @return int PDO column type
     */	
	protected function getType($column)
	{
		if (substr($column, -strlen($this->criterionPostfix)) == $this->criterionPostfix) {
			$column = substr($column, 0, -strlen($this->criterionPostfix));
		}
		
		if (!isset($this->dbTypes[$column])) {
	        throw new DatabaseException('No type for column "' . $column . '"');
	    }
	    
	    return $this->dbTypes[$column];
	}

    /**
     * Checks whether DB error info indicates a constraint violation and 
     * throws an exception containing if so.
     * Trying to detect the violating column is pretty pointless since
     * there is no decent error message when multiple constraints are violated,
     * and it's unclear if the error messages are consistent across databases. 
     *
     * @param array $message
     * @return null
     *
     * @throws spriebsch\DB\ConstraintViolationException
     */
    protected function checkForConstraintViolation($message)
    {
        if ($message[0] = 23000) {
            throw new ConstraintViolationException('Constraint violation on insert into "' . $this->table . '": ' . $message[2]);
        }
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
     * Returns the ID column.
     * 
     * @return string
     */
	public function getIdColumn()
	{
	   return $this->idColumn;
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
            throw new InvalidArgumentException('ID "' . $id . '" is not an integer');
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

        return $this->fixTypes($result);        
	}

    /**
     * Runs a select statement.
     * Returns a single record or an array of records, depending on $justOne flag.
     *   
     * @param array $criteria Associative array with column => criterion entries
     * @param bool $justOne Returns only first result record when set  
     * @return array
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
            
            $result = $statement->fetch(PDO::FETCH_ASSOC);

            // When query has no result, return empty recordset.
            if ($result === false) {
                return false;
            } 

            return $this->fixTypes($result);        
        } else {
            return array_map(array($this, 'fixTypes'), $statement->fetchAll(PDO::FETCH_ASSOC));
        }
    }

    /**
     * Runs select statement and returns first result record.
     *
     * @param array $criteria
     * @return array
     */
    public function selectOne(array $criteria)
    {
        return $this->select($criteria, true);
    }
    
    /**
     * Updates a row.
     * Returns the number of updated records.
     *
     * @param array $record Associative array with column => value entries
     * @return int
     *
     * @throws InvalidArgumentException Record has no ID column
     * @throws InvalidArgumentException ID is not an integer
     * @throws spriebsch\DB\DatabaseException Update ID failed on table
     * @todo make sure it works when criteria and record share column names
     */
	public function update(array $record, array $criteria = array())
	{
        $sql = 'UPDATE ' . $this->table . ' SET ';
        $fields = array();

        $data = $record;

        // We don't have to update the ID column.
        unset($data[$this->idColumn]);
        
        foreach (array_keys($data) as $key) {
            $fields[] = $key . '=:' . $key;           
        }

        $sql .= implode(',', $fields);

        $fields = array();

        // Criteria get a postfix to avoid problems with column names
        // that appear in the data and in the criteria.

        foreach (array_keys($criteria) as $key) {
            $fields[] = $key . '=:' . $key . $this->criterionPostfix;           
        }

        $sql .= ' WHERE ' . implode(' AND ', $fields);

        $statement = $this->prepare($sql);
        $this->bindParameters($statement, $record);

        $tmp = array();
        foreach ($criteria as $key => $value) {
        	$tmp[$key . $this->criterionPostfix] = $value;
        }

        $this->bindParameters($statement, $tmp);
        $statement->execute();

        if ($statement->errorCode() != 0) {
            $message = $statement->errorInfo();

            $this->checkForConstraintViolation($message);

            throw new DatabaseException('Update ID "' . $record[$this->idColumn] . '" failed on table "' . $this->table . '": ' . $message[2]);
        }        

        return $statement->rowCount();
	}
	
	/**
	 * Inserts row.
     * Returns ID of the inserted record.
	 *
	 * @param array $record Associative array with column => value entries
	 * @return bool
	 * 
	 * @throws InvalidArgumentException Record to insert already has an ID
	 * @throws spriebsch\DB\DatabaseException Insert failed on table
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
        	
        	$this->checkForConstraintViolation($message);

        	throw new DatabaseException('Insert failed on table "' . $this->table . '": ' . $message[2]);
        }        

        return $this->db->lastInsertId();
	}

    /**
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

        return array_map(array($this, 'fixTypes'), $statement->fetchAll(\PDO::FETCH_ASSOC));        
    }

    /**
     * Deletes a record.
     *
     * @param int $id ID of the record to delete
     * @return bool
     *
	 * @throws InvalidArgumentException ID is not an integer
     * @throws spriebsch\DB\DatabaseException Delete ID failed on table
     */
	public function delete($id)
	{
		if (!is_int($id)) {
			throw new InvalidArgumentException('ID "' . $id . '" is not an integer');
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