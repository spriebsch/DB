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
 * Database handler class.
 * Wraps PDO to work around connecting to the database in PDO constructor.
 * Will lazy initialize DB connection on first request.  
 *
 * @author Stefan Priebsch <stefan@priebsch.de>
 * @copyright Stefan Priebsch <stefan@priebsch.de>. All rights reserved.
 */
class DatabaseHandler
{
	protected $pdo;

	protected $dsn;
	protected $username;
	protected $password;
	protected $options = array();

	/**
	 * Constructs the object.
	 * Parameters are the same as in the original PDO class.
	 * 
	 * @param unknown_type $dsn
	 * @param unknown_type $username
	 * @param unknown_type $password
	 * @param unknown_type $options
	 * @return unknown_type
	 */
    public function __construct($dsn, $username = '', $password = '', $options = array())
	{
        $this->dsn      = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options  = $options;
	}

	/**
	 * Delegates all method calls to the PDO object, lazy initializing it on demand. 
	 *
	 * @param string $method
	 * @param array $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
	    if ($this->pdo === null) {
	        try {
                $this->pdo = new \PDO($this->dsn, $this->username, $this->password, $this->options);
	        }
	        
	        catch (\PDOException $e) {
	            // PDO throws exception in constructor when DB connect fails.
	            // Since DatabaseException extends PDOException, catch PDOException in client code will still work.
	            throw new DatabaseException('Cannot create PDO object', 0, $e);
	        }
	    }

	    return call_user_func_array(array($this->pdo, $method), $parameters);
	}
}
?>
