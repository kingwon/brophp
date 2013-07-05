<?php
/** ******************************************************************************
 * brophp.com 内存缓存Memcached类，用于将SQL语句的查询结果缓存在指定服务器内存中 *
 * *******************************************************************************
 * 许可声明：专为《细说PHP》读者及LAMP兄弟连学员提供的“学习型”超轻量级php框架。*
 * *******************************************************************************
 * 版权所有 (C) 2011-2013 北京易第优教育咨询有限公司，并保留所有权利。           *
 * 网站地址: http://www.lampbrother.net (LAMP兄弟连)                             *
 * *******************************************************************************
 * $Author: 高洛峰 (skygao@lampbrother.net) $                                    *
 * $Date: 2011-07-18 10:00:00 $                                                  * 
 * ******************************************************************************/
	class MemcacheModel {
		private $mc = null;
		/**
		 * 构造方法,用于添加服务器并创建memcahced对象
		 */
		function __construct($servers){
			$mc = new Memcache;
			//如果有多个memcache服务器
			if(is_array($servers[0])){
				foreach ($servers as $server){
					call_user_func_array(array($mc, 'addServer'), $server);
				}
			//如果只有一个memcache服务器
			} else {
				call_user_func_array(array($mc, 'addServer'), $servers);
			
			}
			$this->mc=$mc;
		}
		/**
		 * 获取memcached对象
		 * @return	object		memcached对象
		 */
		function getMem(){
			return $this->mc;
		}
		/**
		 * 检查mem是否连接成功
		 * @return	bool	连接成功返回true,否则返回false
		 */
		function mem_connect_error(){
			$stats=$this->mc->getStats();
			if(empty($stats)){
				return false;
			}else{
				return true;
			}
		}

		private function addKey($tabName, $key){
			
			$keys=$this->mc->get($tabName);
			if(empty($keys)){
				$keys=array();
			}
			//如果key不存在,就添加一个
			if(!in_array($key, $keys)) {
				$keys[]=$key;  //将新的key添加到本表的keys中
				$this->mc->set($tabName, $keys, MEMCACHE_COMPRESSED, 0);
				return true;   //不存在返回true
			}else{
				return false;  //存在返回false
			}
		}
		/**
		 * 向memcache中添加数据
		 * @param	string	$tabName	需要缓存数据表的表名
		 * @param	string	$sql		使用sql作为memcache的key
		 * @param	mixed	$data		需要缓存的数据
		 */
		function addCache($tabName, $sql, $data){
		
			$key=md5($sql);
			//如果不存在
			if($this->addKey($tabName, $key)){
				$this->mc->set($key, $data, MEMCACHE_COMPRESSED, 0);
			}
		}
		/**
		 * 获取memcahce中保存的数据
		 * @param	string	$sql	使用SQL的key
		 * @return 	mixed		返回缓存中的数据
		 */
		function getCache($sql){
			$key=md5($sql);
			return $this->mc->get($key);
		}


		/**
		 * 删除和同一个表相关的所有缓存
		 * @param	string	$tabName	数据表的表名
		 */ 
		function delCache($tabName){
			$keys=$this->mc->get($tabName);
		
			//删除同一个表的所有缓存
			if(!empty($keys)){
				foreach($keys as $key){
					$this->mc->delete($key, 0); //0 表示立刻删除
				}
			}
			//删除表的所有sql的key
			$this->mc->delete($tabName, 0); 
		}
		/**
		 * 删除单独一个语句的缓存
		 * @param	string	$sql 执行的SQL语句
		 */
		function delone($sql){
			$key=md5($sql);
			$this->mc->delete($key, 0); //0 表示立刻删除
		}
	}
