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
 * Base class for table data gateway unit tests.
 *
 * @author     Stefan Priebsch <stefan@priebsch.de>
 * @copyright  Stefan Priebsch <stefan@priebsch.de>. All rights reserved.
 * @todo test float, blob, and bool
 */
abstract class TableDataGatewayTestBase extends \PHPUnit_Framework_TestCase
{
    /**
     * Data type definition for columns of "Test" table.
     * Mapping for col99 deliberately left out.
     */
    protected $types = array(
        'id'   => \PDO::PARAM_INT,
        'col1' => \PDO::PARAM_STR,
        'col2' => \PDO::PARAM_INT, 
    );

    /**
     * Sets up test fixture running a SQL create script against
     * a SQLite in-memory database.
     * Errors in the create script will lead to a skipped test.
     */
    protected function setUp()
    {
        $this->db = new DatabaseHandler('sqlite::memory:');
        $this->db->exec(file_get_contents(__DIR__ . '/_testdata/fixture.sql'));

        if ($this->db->errorCode() != 0) {
            $message = $this->db->errorInfo();
            $this->markTestSkipped('Could not create database fixture: ' . $message[2]);
        }        

        $this->gw = new TableDataGateway($this->db, 'Test', 'id', $this->types);
    }

    /**
     * Tears down the test fixture.
     */    
    protected function tearDown()
    {
        unset($this->db);
        unset($this->gw);
    }
}
?>
