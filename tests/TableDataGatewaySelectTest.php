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

use PHPUnit_Framework_Constraint_IsType;

/**
 * Unit Tests for the select functionality of TableDataGateway.
 *
 * @author     Stefan Priebsch <stefan@priebsch.de>
 * @copyright  Stefan Priebsch <stefan@priebsch.de>. All rights reserved.
 * @todo test float, blob, and bool
 */
class TableDataGatewaySelectTest extends TableDataGatewayTestBase
{
    /**
	 * @covers spriebsch\DB\TableDataGateway
     */
    public function testSelectReturnsResultRecords()
    {
        $result = $this->gw->select(array('col1' => 'text3'));

        $this->assertEquals(3, count($result));
        $this->assertEquals(44, $result[0]['col2']);
        $this->assertEquals(45, $result[1]['col2']);
        $this->assertEquals(46, $result[2]['col2']);
    }

    /**
	 * @covers spriebsch\DB\TableDataGateway
     */
    public function testSelectOneReturnsFirstResultRecord()
    {
        $result = $this->gw->selectOne(array('col1' => 'text3'));

        $this->assertEquals(44, $result['col2']);
    }

    /**
     * @covers spriebsch\DB\TableDataGateway
     */
    public function testGatewayReturnsIntegerValues()
    {
        $result = $this->gw->selectOne(array('col1' => 'text3'));

        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $result['col2']);
    }

    /**
     * @covers spriebsch\DB\TableDataGateway
     */
    public function testGatewayReturnsBooleanValues()
    {
        $result = $this->gw->selectOne(array('col1' => 'text3'));

        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_BOOL, $result['col3']);
    }

    /**
     * Bug: Empty "selectOne()" query result for causes "Invalid argument supplied for foreach()" 
     * because fixType() is called with false as parameter. 
     *
     * @covers spriebsch\DB\TableDataGateway
     */
    public function testBugSelectOneShouldNotCallFixTypesOnEmptyResult()
    {
        $result = $this->gw->selectOne(array('col1' => 'nonsense'));

        $this->assertFalse($result);
    }

    /**
     * Make sure "select" query result causes "Invalid argument supplied for foreach()"
     * because fixType() is called with false as parameter. 
     *
     * @covers spriebsch\DB\TableDataGateway
     */
    public function testBugSelectShouldNotCallFixTypesOnEmptyResult()
    {
        $result = $this->gw->select(array('col1' => 'nonsense'));

        $this->assertEquals(0, count($result));
    }
}
?>