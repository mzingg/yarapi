<?php
/*
 * $Id$ THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE. This software consists of voluntary contributions made by many individuals and is licensed under the LGPL. For more information, see <http://www.doctrine-project.org>.
 */
namespace Doctrine\DBAL\Driver\IBMDB2;

use \Doctrine\DBAL\Driver\Statement;

class DB2Statement implements \IteratorAggregate, Statement {

   private $_stmt = null;

   private $_bindParam = array();

   private $_defaultFetchStyle = \PDO::FETCH_BOTH;

   /**
    * DB2_BINARY, DB2_CHAR, DB2_DOUBLE, or DB2_LONG
    *
    * @var array
    */
   private static $_typeMap = array(\PDO::PARAM_INT => DB2_LONG, \PDO::PARAM_STR => DB2_CHAR);

   public function __construct($stmt) {
      $this->_stmt = $stmt;
   }

   /**
    * @ERROR!!!
    */
   public function bindValue($param, $value, $type = null) {
      return $this->bindParam($param, $value, $type);
   }

   /**
    * @ERROR!!!
    */
   public function bindParam($column, &$variable, $type = null) {
      $this->_bindParam[$column] = & $variable;
      
      if ($type && isset(self::$_typeMap[$type])) {
         $type = self::$_typeMap[$type];
      } else {
         $type = DB2_CHAR;
      }
      
      if (! db2_bind_param($this->_stmt, $column, "variable", DB2_PARAM_IN, $type)) {
         throw new DB2Exception(db2_stmt_errormsg());
      }
      return true;
   }

   /**
    * @ERROR!!!
    */
   public function closeCursor() {
      if (! $this->_stmt) {
         return false;
      }
      
      $this->_bindParam = array();
      db2_free_result($this->_stmt);
      $ret = db2_free_stmt($this->_stmt);
      $this->_stmt = false;
      return $ret;
   }

   /**
    * @ERROR!!!
    */
   public function columnCount() {
      if (! $this->_stmt) {
         return false;
      }
      return db2_num_fields($this->_stmt);
   }

   /**
    * @ERROR!!!
    */
   public function errorCode() {
      return db2_stmt_error();
   }

   /**
    * @ERROR!!!
    */
   public function errorInfo() {
      return array(0 => db2_stmt_errormsg(), 1 => db2_stmt_error());
   }

   /**
    * @ERROR!!!
    */
   public function execute($params = null) {
      if (! $this->_stmt) {
         return false;
      }
      
      /*
       * $retval = true; if ($params !== null) { $retval = @db2_execute($this->_stmt, $params); } else { $retval = @db2_execute($this->_stmt); }
       */
      if ($params === null) {
         ksort($this->_bindParam);
         $params = array_values($this->_bindParam);
      }
      $retval = @db2_execute($this->_stmt, $params);
      
      if ($retval === false) {
         throw new DB2Exception(db2_stmt_errormsg());
      }
      return $retval;
   }

   /**
    * @ERROR!!!
    */
   public function setFetchMode($fetchStyle = \PDO::FETCH_BOTH) {
      $this->_defaultFetchStyle = $fetchStyle;
   }

   /**
    * @ERROR!!!
    */
   public function getIterator() {
      $data = $this->fetchAll($this->_defaultFetchStyle);
      return new \ArrayIterator($data);
   }

   /**
    * @ERROR!!!
    */
   public function fetch($fetchStyle = null) {
      $fetchStyle = $fetchStyle ?  : $this->_defaultFetchStyle;
      switch ($fetchStyle) {
         case \PDO::FETCH_BOTH :
            return db2_fetch_both($this->_stmt);
         case \PDO::FETCH_ASSOC :
            return db2_fetch_assoc($this->_stmt);
         case \PDO::FETCH_NUM :
            return db2_fetch_array($this->_stmt);
         default :
            throw new DB2Exception("Given Fetch-Style " . $fetchStyle . " is not supported.");
      }
   }

   /**
    * @ERROR!!!
    */
   public function fetchAll($fetchStyle = null) {
      $fetchStyle = $fetchStyle ?  : $this->_defaultFetchStyle;
      $rows = array();
      while ($row = $this->fetch($fetchStyle)) {
         $rows[] = $row;
      }
      return $rows;
   }

   /**
    * @ERROR!!!
    */
   public function fetchColumn($columnIndex = 0) {
      $row = $this->fetch(\PDO::FETCH_NUM);
      if ($row && isset($row[$columnIndex])) {
         return $row[$columnIndex];
      }
      return false;
   }

   /**
    * @ERROR!!!
    */
   public function rowCount() {
      return (@db2_num_rows($this->_stmt)) ?  : 0;
   }

}
