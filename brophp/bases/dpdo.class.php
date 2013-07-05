<?php
/** ******************************************************************************
 * brophp.com 数据库抽象层PDO驱动类，通过该类使用PHP的pdo扩展连接处理数据库。    *
 * *******************************************************************************
 * 许可声明：专为《细说PHP》读者及LAMP兄弟连学员提供的“学习型”超轻量级php框架。*
 * *******************************************************************************
 * 版权所有 (C) 2011-2013 北京易第优教育咨询有限公司，并保留所有权利。           *
 * 网站地址: http://www.lampbrother.net (LAMP兄弟连)                             *
 * *******************************************************************************
 * $Author: 高洛峰 (skygao@lampbrother.net) $                                    *
 * $Date: 2011-07-18 10:00:00 $                                                  * 
 * ******************************************************************************/
	class Dpdo extends DB{
		static $pdo=null;
		/**
		 *获取数据库连接对象PDO
		 */
		static function connect(){
			if(is_null(self::$pdo)) {
				try{
					if(defined("DSN"))
						$dsn=DSN;
					else
						$dsn="mysql:host=".HOST.";dbname=".DBNAME;

					$pdo=new PDO($dsn, USER, PASS, array(PDO::ATTR_PERSISTENT=>true));
					$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					self::$pdo=$pdo;
					return $pdo;
				}catch(PDOException $e){
					echo "连接数库失败：".$e->getMessage();
				}
			}else{
				return self::$pdo;
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
			 $this->setNull(); //初使化sql
			
			 $value=$this->escape_string_array($data);
			 $marr=explode("::", $method);
			 $method=strtolower(array_pop($marr));
			 if(strtolower($method)==trim("total")){
			 	$sql=preg_replace('/select.*?from/i','SELECT count(*) as count FROM',$sql);
			 }
			 $addcache=false;
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
	 	
		
			 try{
				$return=null;
	 			$pdo=self::connect();
		 		$stmt=$pdo->prepare($sql);  //准备好一个语句
		        	$result=$stmt->execute($value);   //执行一个准备好的语句
			
				//如果使用mem，并且不是查找语句
				if(isset($mem) && !$addcache){
					if($stmt->rowCount()>0){
						$mem->delCache($this->tabName);	 //清除缓存
						Debug::addmsg("清除表<b>{$this->tabName}</b>在Memcache中所有缓存!"); //debug
					}
				}
			         
				 switch($method){
					 case "select":  //查所有满足条件的
						 $data=$stmt->fetchAll(PDO::FETCH_ASSOC);

						 if($addcache){
						 	$mem->addCache($this->tabName, $memkey, $data);
						 }
						 $return=$data;
						break;
					case "find":    //只要一条记录的
						$data=$stmt->fetch(PDO::FETCH_ASSOC);

						 if($addcache){
						 	$mem->addCache($this->tabName, $memkey, $data);
						 }
						 $return=$data;
						break;

					case "total":  //返回总记录数
						$row=$stmt->fetch(PDO::FETCH_NUM);

						 if($addcache){
						 	$mem->addCache($this->tabName, $memkey, $row[0]);
						 }
					
						$return=$row[0];
						break;
					case "insert":  //插入数据 返回最后插入的ID
						if($this->auto=="yes")
							$return=$pdo->lastInsertId();
						else
							$return=$result;
						break;
					case "delete":
					case "update":        //update 
						$return=$stmt->rowCount();
						break;
					default:
						$return=$result;
				 }
				$stopTime= microtime(true);
				$ys=round(($stopTime - $startTime) , 4);
				Debug::addmsg('[用时<font color="red">'.$ys.'</font>秒] - '.$memkey,2); //debug
				return $return;
			}catch(PDOException $e){
				Debug::addmsg("<font color='red'>SQL error: ".$e->getMessage().'</font>');
				Debug::addmsg("请查看：<font color='#005500'>".$memkey.'</font>'); //debug
			}	
		}

		/**
		 * 自动获取表结构
		 */ 
		function setTable($tabName){
			$cachefile=PROJECT_PATH."runtime/data/".$tabName.".php";
			$this->tabName=TABPREFIX.$tabName; //加前缀的表名
				
			if(!file_exists($cachefile)){
				try{
					$pdo=self::connect();
					$stmt=$pdo->prepare("desc {$this->tabName}");
					$stmt->execute();
					$auto="yno";
					$fields=array();
					while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
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
				}catch(PDOException $e){
					Debug::addmsg("<font color='red'>异常：".$e->getMessage().'</font>');
				}
			}else{
				$json=ltrim(file_get_contents($cachefile),"<?ph ");
				$this->auto=substr($json,-3);
				$json=substr($json, 0, -3);
				$this->fieldList=(array)json_decode($json, true);	
			}
			Debug::addmsg("表<b>{$this->tabName}</b>结构：".implode(",", $this->fieldList),2); //debug
		}
    		/**
		* 事务开始
    		*/
		public function beginTransaction() {
			$pdo=self::connect();
			$pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 0); 
			$pdo->beginTransaction();
		}
		
		/**
     		* 事务提交
     		*/
		public function commit() {
			$pdo=self::connect();
			$pdo->commit();
			$pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1); 
		}
		
		/**
     		* 事务回滚
     		*/
		public function rollBack() {
			$pdo=self::connect();
			$pdo->rollBack();
			$pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1); 
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
			$pdo=self::connect();
			$stmt=$pdo->prepare($sql);  //准备好一个语句
		        $stmt->execute();   //执行一个准备好的语句
			$size = 0;
			while($row=$stmt->fetch(PDO::FETCH_ASSOC))
				$size += $row["Data_length"] + $row["Index_length"];
			return tosize($size);
		}
		/*
		 * 数据库的版本
		 * @return	string		返回数据库系统的版本
		 */
		function dbVersion() {
			$pdo=self::connect();
			return $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
		}
	}
