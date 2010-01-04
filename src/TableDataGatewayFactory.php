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
 * Table Data Gateway Factory class.
 *
 * @author Stefan Priebsch <stefan@priebsch.de>
 * @copyright Stefan Priebsch <stefan@priebsch.de>. All rights reserved.
 */
class TableDataGatewayFactory
{
    /**
     * @var spriebsch\DB\DatabaseHandler
     */
    protected $db;

    /**
     * @var array
     */
    protected $gateways = array();

    /**
     * @var array
     */
    protected $dbTypes = array();

    /**
     * @var array
     */
    protected $idColumns = array();

    /**
     * Constructs the object.
     *
     * @param spriebsch\DB\DatabaseHandler
     */
    public function __construct(DatabaseHandler $db)
    {
        $this->db = $db;
    }
    
    /**
     * Registers a database table.
     *
     * @param string $table Table name
     * @param array $types Associative array of DB types
     */
    public function registerTable($table, $types, $id)
    {
        $this->dbTypes[$table]   = $types;
        $this->idColumns[$table] = $id;
    }

    /**
     * Returns a table data gateway instance.
     *
     * @param string $table Table name
     * @return spriebsch\DB\TableDataGateway
     *
     * @throws spriebsch\DB\DatabaseException Table not configured
     */
    public function getTableGateway($table)
    {
        if (!isset($this->dbTypes[$table]) || !is_array($this->dbTypes[$table])) {
            throw new DatabaseException('Table "' . $table . '" not configured');
        }

        if (!isset($this->gateways[$table])) {
            $this->gateways[$table] = new TableDataGateway($this->db, $table, $this->idColumns[$table], $this->dbTypes[$table]);
        }

        return $this->gateways[$table];
    }
}
?>