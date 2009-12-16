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
 * @todo a readonly recordset?
 */
class RecordSet implements \Countable
{
    /**
     * @var int
     */
    protected $idColumn = 'id';

    /**
     * @var array
     */
    protected $records = array();

    /**
     * @var spriebsch\DB\TableDataGateway
     */
    protected $tableDataGateway;

    /**
     * Constructs the object.
     */
    public function __construct(TableDataGateway $tableDataGateway, array $records = array())
	{
        $this->tableDataGateway = $tableDataGateway;
	    $this->indexRecords($records);

        $this->idColumn = $tableDataGateway->getIdColumn();
	}

	/**
	 * Indexes the records for quick internal access.
	 *
	 * @param array $records
	 * @return null
	 */
	protected function indexRecords(array $records)
	{
	   foreach ($records as $record) {
            $this->records[$record[$this->idColumn]] = $record;	       
	   }
	}
	
	/**
	 * Returns the number of records in the recordset.
	 * 
	 * @return int
	 */
	public function count()
	{
        return count($this->records);
	}

	/**
	 * Returns ID of (first) record matching given criteria.
	 * Criteria is an associative array of the form array('column' => 'value', ...).
	 * Returns NULL if no matching record is found. 
	 * 
	 * @param array $criteria
	 * @return int
	 */
	public function find($criteria)
	{
        foreach ($this->records as $id => $record) {
            if (array_diff($criteria, $record) == array()) {
                return $id;
            }
        }
        
        return null;
	}

	/**
	 * Checks whether record of given ID is in the record set.
	 *
	 * @param int $id
	 * @return bool
	 */
	public function has($id)
	{
	    return isset($this->records[$id]);
	}

	/**
	 * Returns a record or single column of given record.
	 *
	 * @param int $id Primary key of the record 
	 * @param string $column Column name
	 * @return mixed
	 */
	public function get($id, $column = null)
	{
	    if (!$this->has($id)) {
	       throw new RecordSetException('ID "' . $id . '" does not exist');
	    }
	    
	    if ($column === null) {
    	    return $this->records[$id];
	    }

	    if (!isset($this->records[$id][$column])) {
           throw new RecordSetException('Column "' . $column . '" does not exist');
	    }

        return $this->records[$id][$column];
	}

    /**
     * Sets column in given record. 
     * 
     * @param $id Primary key of the record
     * @param $column Colum name to set
     * @param $value Value to set
     * @return null
     */
	public function set($id, $column, $value)
	{
        if (!$this->has($id)) {
           throw new RecordSetException('ID "' . $id . '" does not exist');
        }

	    $this->records[$id][$column] = $value;
	}

    /**
     * Adds a record to the record set, automatically inserting it to the database
     * to be able to assign an ID. ID column of given record will be overwritten
     * with the record's database ID. 
     *
     * @param array $record
     * @return int
     */
    public function add(array $record)
    {
        // Store record to database.
        $id = $this->tableDataGateway->insert($record);

        // Set the ID assigned by DB.
        $record[$this->idColumn] = $id;

        $this->records[$id] = $record;
        return $id;
    }
}
?>