<?php
/** ******************************************************************************
 * brophp.com 数据库mysqli驱动类，通过该类使用PHP的mysqli扩展连接处理数据库。    *
 * *******************************************************************************
 * 许可声明：专为《细说PHP》读者及LAMP兄弟连学员提供的“学习型”超轻量级php框架。*
 * *******************************************************************************
 * 版权所有 (C) 2011-2013 北京易第优教育咨询有限公司，并保留所有权利。           *
 * 网站地址: http://www.lampbrother.net (LAMP兄弟连)                             *
 * *******************************************************************************
 * $Author: 高洛峰 (skygao@lampbrother.net) $                                    *
 * $Date: 2011-07-18 10:00:00 $                                                  * 
 * ******************************************************************************/
	class Dmysqli extends DB{
		static $mysqli=null;
		/**
		 * 用于获取数据库连接mysqli对象,如果已经存在mysqli对象就不在调用connect()去连接
		 */
		static function connect(){
			if(is_null(self::$mysqli)) {
				$mysqli=new mysqli(HOST, USER, PASS, DBNAME);
				if (mysqli_connect_errno()) {
					Debug::addmsg("<font color='red'>连接失败: ".mysqli_connect_error().",请查看config.inc.php文件设置是否有误！</font>");
					return false;
				}else{
					self::$mysqli=$mysqli;
					return $mysqli;
				}
			}else{
				return self::$mysqli;
			}
		}
		/**
		 * 执行SQL语句的方法
		 * @param	string	$sql		用户查询的SQL语句
		 * @param	string	$method		SQL语句的类型（select,find,total,insert,update,other）
		 * @param	array	$data		为prepare方法中的?参数绑定值
		 * @return	mixed			根据不同的SQL语句返回值
		 */
		function query($sql, $method,$data=array()){
			$startTime = microtime(true); 
			$this->setNull();  //初使化SQL

			$value=$this->escape_string_array($data);
			 $marr=explode("::", $method);
		 	 $method=strtolower(array_pop($marr));
			 if(strtolower($method)==trim("total")){
			 	$sql=preg_replace('/select.*?from/i','SELECT count(*) as count FROM',$sql);
			 }
			 $addcache=false;   //用于判断是否向mem中加数据
 			 $memkey=$this->sql($sql, $value);
			 if(defined("USEMEM")){
				 global $mem;	
				 if($method == "select" || $method == "find" || $method=="total"){
					$data=$mem->getCache($memkey);
					if($data){
						return $data;  //直接从memserver中取，不再向下执行
					}else{
						
						$addcache=true;	
					}
				 }

			 }

		

			 $mysqli=self::connect();
			 if($mysqli)
				 $stmt=$mysqli->prepare($sql);  //准备好一个语句
			 else
				 return;
			 //绑定参数
			 if(count($value) > 0) {
			 	$s = str_repeat('s', count($value));
			 	array_unshift($value, $s);
				call_user_func_array(array($stmt, 'bind_param'), $value);
			 }
			 if($stmt){
			 	$result=$stmt->execute();   //执行一个准备好的语句
			  }
			
			 //如果SQL有误，则输出并直接返回退出
			 if(!$result){
				 Debug::addmsg("<font color='red'>SQL ERROR: [{$mysqli->errno}] {$stmt->error}</font>");
				 Debug::addmsg("请查看：<font color='#005500'>".$memkey.'</font>'); //debug
				 return;
			 }

		

			//如果使用mem，并且不是查找语句
			if(isset($mem) && !$addcache){
				if($stmt->affected_rows > 0){ //有影响行数
					$mem->delCache($this->tabName);	 //清除缓存
					Debug::addmsg("清除表<b>{$this->tabName}</b>在Memcache中所有缓存!"); //debug
				}
			}

			

			$returnv=null;
			switch($method){
				case "select":  //查所有满足条件的
					$stmt->store_result(); 
					$data = $this->getAll($stmt);

					if($addcache){
					 	$mem->addCache($this->tabName, $memkey, $data);
					 }
					 $returnv=$data;
					break;
				 case "find":    //只要一条记录的
					$stmt->store_result(); 
					if($stmt->num_rows > 0) {
						$data = $this->getOne($stmt);

						if($addcache){
					 		$mem->addCache($this->tabName, $memkey, $data);
						}
						$returnv=$data;
					}else{
						$returnv=false;
					}
					break;

				case "total":  //返回总记录数
					$stmt->store_result(); 
					$row=$this->getOne($stmt);

					if($addcache){
					 	$mem->addCache($this->tabName, $memkey, $row["count"]);
					 }
					$returnv=$row["count"];
					break;
				case "insert":  //插入数据 返回最后插入的ID
					if($this->auto=="yes")
						$returnv=$mysqli->insert_id;
					else
						$returnv=$result;
					break;
				case "delete":
				case "update":        //update 
					$returnv=$stmt->affected_rows;
					break;
				default:
					$returnv=$result;
			}
			$stopTime= microtime(true);
			$ys=round(($stopTime - $startTime) , 4);
			Debug::addmsg('[用时<font color="red">'.$ys.'</font>秒] - '.$memkey,2); //debug
			return $returnv;
		}
		/**
		 * 获取多所有记录
		 */
		private function getAll($stmt) {
			$result = array();
			$field = $stmt->result_metadata()->fetch_fields();
			$out = array();
			//获取所有结果集中的字段名
			$fields = array();
			foreach ($field as $val) {
				$fields[] = &$out[$val->name];
			}
			//用所有字段名绑定到bind_result方上
			call_user_func_array(array($stmt,'bind_result'), $fields);
		       	while ($stmt->fetch()) {
				$t = array();  //一条记录关联数组
				foreach ($out as $key => $val) {
					$t[$key] = $val;
				}
				$result[] = $t;
			}
			return $result;  //二维数组
		}

		/**
		 * 获取一条记录
		 */
		private function getOne($stmt) {
			$result = array();
			$field = $stmt->result_metadata()->fetch_fields();
			$out = array();
			//获取所有结果集中的字段名
			$fields = array();
			foreach ($field as $val) {
				$fields[] = &$out[$val->name];
			}
			//用所有字段名绑定到bind_result方上
			call_user_func_array(array($stmt,'bind_result'), $fields);
		        $stmt->fetch();
			
			foreach ($out as $key => $val) {
				$result[$key] = $val;
			}
			return $result;  //一维关联数组
	       	}

		/**
		 * 自动获取表结构
		 * @param	string	$tabName	表名
		 */
		function setTable($tabName){
			$cachefile=PROJECT_PATH."runtime/data/".$tabName.".php";
			$this->tabName=TABPREFIX.$tabName; //加前缀的表名
		
			if(file_exists($cachefile)){
				$json=ltrim(file_get_contents($cachefile),"<?ph ");
				$this->auto=substr($json,-3);
				$json=substr($json, 0, -3);
				$this->fieldList=(array)json_decode($json, true);	
			
			}else{
				$mysqli=self::connect();
				if($mysqli)
					$result=$mysqli->query("desc {$this->tabName}");
				else
					return;
			
				$fields=array();
				$auto="yno";
				while($row=$result->fetch_assoc()){
					if($row["Key"]=="PRI"){
						$fields["pri"]=strtolower($row["Field"]);
					}else{
						$fields[]=strtolower($row["Field"]);
					}
					if($row["Extra"]=="auto_increment")
						$auto="yes";
				}
				//如果表中没有主键，则将第一列当作主键
				if(!array_key_exists("pri", $fields)){
					$fields["pri"]=array_shift($fields);		
				}
				if(!DEBUG)
					file_put_contents($cachefile, "<?php ".json_encode($fields).$auto);
				$this->fieldList=$fields;
				$this->auto=$auto;
				
			}
			Debug::addmsg("表<b>{$this->tabName}</b>结构：".implode(",", $this->fieldList),2); //debug
		}
    		/**
		* 事务开始
    		*/
		public function beginTransaction() {
			self::connect()->autocommit(false);
			
		}
		
		/**
     		* 事务提交
     		*/
		public function commit() {
			$mysqli=self::connect();
 			$mysqli->commit();
        		$mysqli->autocommit(true);

		}
		
		/**
     		* 事务回滚
     		*/
		public function rollBack() {
			$mysqli=self::connect();
  			$mysqli->rollback();
        		$mysqli->autocommit(true);

		}
		/*
		 * 获取数据库使用大小
		 * @return	string		返回转换后单位的尺寸
		 */
		public function dbSize() {
			$sql = "SHOW TABLE STATUS FROM " . DBNAME;
			if(defined("TABPREFIX")) {
				$sql .= " LIKE '".TABPREFIX."%'";
			}
			$mysqli=self::connect();
			$result=$mysqli->query($sql);
			$size = 0;
			while($row=$result->fetch_assoc())
				$size += $row["Data_length"] + $row["Index_length"];
			return tosize($size);
		}
		/*
		 * 数据库的版本
		 * @return	string		返回数据库系统的版本
		 */
		function dbVersion() {
			$mysqli=self::connect();
			return $mysqli->server_info;
		}
	}

