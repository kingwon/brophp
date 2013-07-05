<?php
/** ******************************************************************************
 * brophp.com 会话控制Session类，用于将Session数据保存在Memcached服务器中。      *
 * *******************************************************************************
 * 许可声明：专为《细说PHP》读者及LAMP兄弟连学员提供的“学习型”超轻量级php框架。*
 * *******************************************************************************
 * 版权所有 (C) 2011-2013 北京易第优教育咨询有限公司，并保留所有权利。           *
 * 网站地址: http://www.lampbrother.net (LAMP兄弟连)                             *
 * *******************************************************************************
 * $Author: 高洛峰 (skygao@lampbrother.net) $                                    *
 * $Date: 2011-07-18 10:00:00 $                                                  * 
 * ******************************************************************************/
	class MemSession {
		private static $handler=null;
		private static $lifetime=null;
		/**
		 * 初使化和开启session
		 * @param	Memcache	$memcache	memcache对象
		 */
		public static function start(Memcache $memcache){
 			//将 session.save_handler 设置为 user，而不是默认的 files
			ini_set('session.save_handler', 'user');
			
			//不使用 GET/POST 变量方式
			//ini_set('session.use_trans_sid',    0);

        		//设置垃圾回收最大生存时间
        		//ini_set('session.gc_maxlifetime',  3600);

       			 //使用 COOKIE 保存 SESSION ID 的方式
       			//ini_set('session.use_cookies',      1);
        		//ini_set('session.cookie_path',      '/');

       			 //多主机共享保存 SESSION ID 的 COOKIE
        		//ini_set('session.cookie_domain','.lampbrother.net');


			self::$handler=$memcache;
			self::$lifetime=ini_get('session.gc_maxlifetime');
			session_set_save_handler(
					array(__CLASS__, 'open'),
					array(__CLASS__, 'close'),
					array(__CLASS__, 'read'),
					array(__CLASS__, 'write'),
					array(__CLASS__, 'destroy'),
					array(__CLASS__, 'gc')
				);
			session_start();
			return true;
		}

	
		public static function open($path, $name){
			return true;
		}

		public static function close(){
			return true;
		}
		/**
		 * 从SESSION中读取数据
		 * @param	string	$PHPSESSID	session的ID
		 * @return 	mixed			返回session中对应的数据
		 */
		public static function read($PHPSESSID){
			$out=self::$handler->get(self::session_key($PHPSESSID));

			if($out===false || $out == null)
				return '';

			return $out;
		}
		/**
		 *向session中添加数据
		 */
		public static function write($PHPSESSID, $data){
			
			$method=$data ? 'set' : 'replace';
		
			return self::$handler->$method(self::session_key($PHPSESSID), $data, MEMCACHE_COMPRESSED, self::$lifetime);
		}

		public static function destroy($PHPSESSID){
			return self::$handler->delete(self::session_key($PHPSESSID));
		}

		public static function gc($lifetime){
			//无需额外回收,memcache有自己的过期回收机制
			return true;
		}

		private static function session_key($PHPSESSID){
			$session_key=TABPREFIX.$PHPSESSID;

			return $session_key;
		}	
	}

