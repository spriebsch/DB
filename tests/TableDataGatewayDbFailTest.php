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

require_once __DIR__ . '/TableDataGatewayTestBase.php';

/**
 * Unit Tests for the Database Data Gateway.
 * Simulates failing queries to test gateway's error handling.
 *
 * @author     Stefan Priebsch <stefan@priebsch.de>
 * @copyright  Stefan Priebsch <stefan@priebsch.de>. All rights reserved.
 */
class TableDataGatewayDbFailTest extends TableDataGatewayTestBase
{
    /**
     * Use test database handler that returns a PDO statement simulating
     * a failed database query.
     */
    protected function setUp()
    {
        $this->db = new TestDatabaseHandler('sqlite::memory:');
        $this->db->exec(file_get_contents(__DIR__ . '/_testdata/fixture.sql'));

        if ($this->db->errorCode() != 0) {
            $message = $this->db->errorInfo();
            $this->markTestSkipped('Could not create database fixture: ' . $message[2]);
        }        
        
        $this->gw = new TableDataGateway($this->db, 'Test', 'id', $this->types);
    }

	/**
	 * @covers spriebsch\DB\TableDataGateway
	 * @expectedException spriebsch\DB\DatabaseException
	 */
    public function testSelectThrowsExceptionWhenStatementFails()
    {
        $result = $this->gw->select(array());
    }    

	/**
	 * @covers spriebsch\DB\TableDataGateway
	 * @expectedException spriebsch\DB\DatabaseException
	 */
    public function testInsertThrowsExceptionWhenStatementFails()
    {
        $result = $this->gw->insert(array());
    }    

	/**
	 * @covers spriebsch\DB\TableDataGateway
	 * @expectedException spriebsch\DB\DatabaseException
	 */
    public function testUpdateThrowsExceptionWhenStatementFails()
    {
        $result = $this->gw->update(array('id' => 1));
    }    

	/**
	 * @covers spriebsch\DB\TableDataGateway
	 * @expectedException spriebsch\DB\DatabaseException
	 */
    public function testFindThrowsExceptionWhenStatementFails()
    {
        $result = $this->gw->find(1);
    }    

	/**
	 * @covers spriebsch\DB\TableDataGateway
	 * @expectedException spriebsch\DB\DatabaseException
	 */
    public function testFindAllThrowsExceptionWhenStatementFails()
    {
        $result = $this->gw->findAll();
    }    

	/**
	 * @covers spriebsch\DB\TableDataGateway
	 * @expectedException spriebsch\DB\DatabaseException
	 */
    public function testDeleteThrowsExceptionWhenStatementFails()
    {
        $result = $this->gw->delete(1);
    }    
}

/**
 * A special database handler that returns TestPDOStatement instead of
 * PDOStatement on prepare(). Used to simulate failing queries.
 */
class TestDatabaseHandler extends DatabaseHandler
{
    public function prepare()
    {
        return new TestPDOStatement;
    }
}

/**
 * Special PDOStatement that simulates failed queries by always returning an
 * error code and an error message. Used to simulate failing queries.
 */
class TestPDOStatement extends \PDOStatement
{
    public function errorCode()
    {
        return 1;
    }
    
    public function errorInfo()
    {
        return array(1, 1, 'simluated error for unit testing purposes');
    }
}
?>
