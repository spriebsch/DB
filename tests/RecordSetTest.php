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

use PHPUnit_Framework_TestCase;

/**
 * Unit Tests for RecordSet class.
 *
 * @author     Stefan Priebsch <stefan@priebsch.de>
 * @copyright  Stefan Priebsch <stefan@priebsch.de>. All rights reserved.
 */
class RecordSetTest extends PHPUnit_Framework_TestCase
{
    protected $testData = array(
        array('id' => 1, 'key' => 'value'), 
        array('id' => 2, 'key' => 42)
    );

    public function setUp()
    {
        $this->gw = $this->getMock('spriebsch\DB\TableDataGateway', array(), array(), '', false, false);

        $this->gw->expects($this->once())
                 ->method('getIdColumn')
                 ->will($this->returnValue('id'));

        $this->rs = new RecordSet($this->gw, $this->testData);
    }

    protected function tearDown()
    {
        unset($this->rs);
        unset($this->gw);
    }

    /**
	 * Pointless test to achieve constructor coverage.
	 *
	 * @covers spriebsch\DB\RecordSet::__construct
	 */
    public function testConstructorCreatesTheObject()
    {
        $this->assertTrue($this->rs instanceOf RecordSet);
    }
    
    /**
     * @covers spriebsch\DB\RecordSet
     */
    public function testHasReturnsTrueForExistingRecord()
    {
        $this->assertTrue($this->rs->has(1));
    }

    /**
     * @covers spriebsch\DB\RecordSet
     */
    public function testHasReturnsFalseForNonExistingRecord()
    {
        $this->assertFalse($this->rs->has(999));
    }

    /**
     * @covers spriebsch\DB\RecordSet
     */
    public function testGetReturnsRecord()
    {
        $this->assertEquals(array('id' => 1, 'key' => 'value'), $this->rs->get(1));
    }

    /**
     * @covers spriebsch\DB\RecordSet
     */
    public function testGetReturnsColumn()
    {
        $this->assertEquals('value', $this->rs->get(1, 'key'));
    }
    
    /**
     * @covers spriebsch\DB\RecordSet
     * @expectedException spriebsch\DB\RecordSetException
     */
    public function testGetThrowsExceptionOnNonExistingRecord()
    {
        $this->rs->get(999);
    }

    /**
     * @covers spriebsch\DB\RecordSet
     */
    public function testGetReturnsNullForNonColumn()
    {
        $this->assertNull($this->rs->get(1, 'nonsense'));
    }
    
    public function testSetChangesValue()
    {
        $this->rs->set(1, 'key', 'changed');

        $this->assertEquals(array('id' => 1, 'key' => 'changed'), $this->rs->get(1));
    }

    /**
     * @covers spriebsch\DB\RecordSet
     * @expectedException spriebsch\DB\RecordSetException
     */
    public function testSetThrowsExceptionOnNonExistingRecord()
    {
        $this->rs->set(99, 'nonsense', 'nonsense');
    }

    /**
     * @covers spriebsch\DB\RecordSet
     */
    public function testAddAddsRecordToDatabaseAndAssignsId()
    {
        // The ID will be overwritten.
        $record = array('id' => 0, 'key' => 'new');

        // Make sure record gets inserted to database. 
        // Database returns ID 99 for this record.
        $this->gw->expects($this->once())
                 ->method('insert')
                 ->with($record)
                 ->will($this->returnValue(99));
        
        $this->rs->add($record);

        $this->assertEquals(array('id' => 99, 'key' => 'new'), $this->rs->get(99));
    }
    
    public function testCountReturnsNumberOfRecords()
    {
        $this->assertEquals(2, count($this->rs));
    }
    
    public function testFindReturnsIdOfFirstMatchingRecord()
    {
        $this->assertEquals(2, $this->rs->find(array('key' => 42)));
    }

    public function testFindReturnsNullWhenNoMatchingRecordIsFound()
    {
        $this->assertNull($this->rs->find(array('key' => 'nonsense')));
    }
}
?>