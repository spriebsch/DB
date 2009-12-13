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
 *
 * @author     Stefan Priebsch <stefan@priebsch.de>
 * @copyright  Stefan Priebsch <stefan@priebsch.de>. All rights reserved.
 * @todo test float, blob, and bool
 */
class TableDataGatewayUpdateTest extends TableDataGatewayTestBase
{
	/**
	 * Make sure record to update contains an ID column.
	 *
	 * @covers spriebsch\DB\TableDataGateway
	 * @expectedException InvalidArgumentException
	 */
    public function testUpdateThrowsExceptionWhenRecordDoesNotContainId()
    {
        $result = $this->gw->update(array());
    }    

	/**
	 * Make sure an exception is thrown when ID is not an integer.
	 *
	 * @covers spriebsch\DB\TableDataGateway
	 * @expectedException InvalidArgumentException
	 */
    public function testUpdateThrowsExceptionWhenIdIsNotAnInteger()
    {
        $result = $this->gw->update(array('id' => 'non-integer'));
    }    

	/**
	 * Make sure an exception is thrown when there is no type information
	 * for a database column. col99 exists in the database (so preparing the
	 * statement still works) but there is no type information for it.
	 *
	 * @covers spriebsch\DB\TableDataGateway
	 * @expectedException spriebsch\DB\DatabaseException
	 */
    public function testUpdateThrowsExceptionWhenColumnHasNoTypeInfo()
    {
        $result = $this->gw->update(array('id' => 1, 'col99' => 'new'));
    }    

	/**
	 * Make sure update returns true on success.
	 *
	 * @covers spriebsch\DB\TableDataGateway
	 */
    public function testUpdateReturnsTrueOnSuccess()
    {
        $result = $this->gw->update(array('id' => 1, 'col1' => 'new', 'col2' => 3));

        $this->assertEquals(1, $result);
    }    

	/**
	 * Make sure update returns false on failure.
	 *
	 * @covers spriebsch\DB\TableDataGateway
	 */
    public function testUpdateReturnsFalseOnFailure()
    {
        $result = $this->gw->update(array('id' => 99, 'col1' => 'new', 'col2' => 3));

        $this->assertEquals(0, $result);
    }
}
?>
