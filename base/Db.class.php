<?php
/*
* @Title	数据库操作类
* @Author	Cheney chen
* @Date		六  6/18 11:25:51 2016
*/
namespace Base;

class DB {
	public static $DB = null;
	public static $config = [];
	public static $stmt = null;
	public static $debug = false;
	public static $db_dsn = '';
	public static $db_user = '';
	public static $db_pwd = '';

	public function __construct($config) {
		self::$config = $config['db'];

		self::connect();
		self::$DB->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
		self::$DB->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
		 self::execute('SET NAMES '. self::$config['charset']);
	}
	
	public function __destruct() {
		self::close();
	}
	
	public function connect() {
		try {
			self::$db_dsn = self::$config['dbtype'].':host='.self::$config['dbhost'].';dbname='.self::$config['dbname'];
			self::$db_user = self::$config['dbuser'];
			self::$db_pwd = self::$config['dbpasswd'];
			self::$DB = new \PDO(self::$db_dsn, self::$db_user, self::$db_pwd);	
		} catch (\PDOException $e) {
			die( "Connect Error Infomation:" . $e->getMessage ());
		}
	}
	
	public function close() {
		self::$DB = null;
	}
	
	public function quote($str) {
		return self::$DB->quote($str);
	}
	
	public function getFields($table) {
		self::$stmt = self::$DB->query("DESCRIBE $table");
		$result = self::$stmt->fetchAll(\PDO::FETCH_ASSOC);
		self::$stmt = null;
		return $result;
	}
	
	public function getLastId() {
		return self::$DB->lastInsertId();
	}
	
	public function execute($sql) {
		return self::$DB->exec($sql);
	}
	
	private function getCode($args) {
		$code = [];
		$res = '';
			
		if (is_array($args)) {
			foreach ($args AS $k => $v) {
				if ($v == '') {
					continue;
				}
				
				$code[] = "`$k`='$v'";
			}
			
			$res = implode(',', $code);
		} else {
			$res = $args;
		}
		
		//$code = substr($code, 0, -1);
		return $res;
	}	
	
	public function optimizeTable($table) {
		$sql = "OPTIMIZE TABLE $table";
		self::execute($sql);
	}
	
	public function _fetch($sql, $type) {
		$result = array();
		self::$stmt = self::$DB->query($sql);
		self::getPDOError($sql);
		self::$stmt->setFetchMode(PDO::FETCH_ASSOC);
		switch ($type) {
			case "0":
				$result = self::$stmt->fetch();
			break;
			case "1":
				$result = self::$stmt->fetchAll();
			break;
			case "2":
				$result = self::$stmt->rowCount();
			break;
		}
		
		self::$stmt = null;
		return $result;
	}	
	
	public function add($table, $args) {
		$sql = "INSERT INTO `$table` SET ";
		
		$code = self::getCode($args);
		$sql .= $code;
			
		$res = self::execute($sql);
		if ($res == 1) {
			$id = self::getLastId();
			return $this->view($table, $id);
		}
	}	
	
	public function update($table, $args, $where) {
		$code = self::getCode($args);
		$sql = "UPDATE `$table` SET ";
		$sql .= $code;
		$sql .= "where $where";
		return self::execute($sql);
	}	
	
	public function delete($table, $id) {
		$args = ['is_del' => 'N'];
		$where = "`id` = $id";

		return $this->update($table, $args, $id);
	}	
	
	public function view($table, $id) {
		$sql = "select * from $table where `id` = :id";
		//$res = self::$DB->query($sql);
		$res = self::$DB->prepare($sql);
		$res->execute(array(':id' => $id));
		$row = $res->fetch(\PDO::FETCH_ASSOC);
		return $row;
		//return self::execute($sql);
	}	
	
	 /**
	* 設置是否为调试模式
	*/
	public function setDebugMode($mode = true) {
		return ($mode == true) ? self::$debug = true : self::$debug = false;
	}	
	
	/**
	* 捕获PDO错误信息
	* 返回:出错信息
	* 类型:字串
	*/
	private function getPDOError($sql) {
		self::$debug ? self::errorfile ( $sql ) : '';
		if (self::$DB->errorCode () != '00000') {
				$info = (self::$stmt) ? self::$stmt->errorInfo () : self::$DB->errorInfo ();
				echo (self::sqlError ( 'mySQL Query Error', $info [2], $sql ));
				exit ();
		}
	}
	
	private function getSTMTError($sql) {
		self::$debug ? self::errorfile ( $sql ) : '';
		if (self::$stmt->errorCode () != '00000') {
			$info = (self::$stmt) ? self::$stmt->errorInfo () : self::$DB->errorInfo ();
			echo (self::sqlError ( 'mySQL Query Error', $info [2], $sql ));
			exit ();
		}
	}	
	
	/**
	* 寫入错误日志
	*/
	private function errorfile($sql) {
		echo $sql . '<br />';
		$errorfile = _ROOT . './dberrorlog.php';
		$sql = str_replace ( array (
			"\n",
			"\r",
			"\t",
			"  ",
			"  ",
			"  "
			), array (
			" ",
			" ",
			" ",
			" ",
			" ",
			" "
			), $sql );
			if (! file_exists ( $errorfile )) {
				$fp = file_put_contents ( $errorfile, "<?PHP exit('Access Denied'); ?>\n" . $sql );
			} else {
				$fp = file_put_contents ( $errorfile, "\n" . $sql, FILE_APPEND );
			}
	}
	
	 /**
	* 作用:运行错误信息
	* 返回:运行错误信息和SQL語句
	* 类型:字符
	*/
	
	private function sqlError($message = '', $info = '', $sql = '') {
				     
		$html = '';
		if ($message) {
			$html .=  $message;
		}
								     
		if ($info) {
			$html .= 'SQLID: ' . $info ;
		}
		
		if ($sql) {
			$html .= 'ErrorSQL: ' . $sql;
		}
														      
		throw new \Exception($html);
	}	
}
