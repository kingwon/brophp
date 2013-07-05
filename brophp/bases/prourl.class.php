<?php
/** ******************************************************************************
 * brophp.com URL解析类，用于将所有请求的URL转为PATHINFO的格式。                 *
 * *******************************************************************************
 * 许可声明：专为《细说PHP》读者及LAMP兄弟连学员提供的“学习型”超轻量级php框架。*
 * *******************************************************************************
 * 版权所有 (C) 2011-2013 北京易第优教育咨询有限公司，并保留所有权利。           *
 * 网站地址: http://www.lampbrother.net (LAMP兄弟连)                             *
 * *******************************************************************************
 * $Author: 高洛峰 (skygao@lampbrother.net) $                                    *
 * $Date: 2011-07-18 10:00:00 $                                                  * 
 * ******************************************************************************/
	class Prourl {
		/**
		 * URL路由,转为PATHINFO的格式
		 */ 
		static function parseUrl(){
			if (isset($_SERVER['PATH_INFO'])){
      			 	//获取 pathinfo
				$pathinfo = explode('/', trim($_SERVER['PATH_INFO'], "/"));
			
       				// 获取 control
       				$_GET['m'] = (!empty($pathinfo[0]) ? $pathinfo[0] : 'index');

       				array_shift($pathinfo); //将数组开头的单元移出数组 
      				
			       	// 获取 action
       				$_GET['a'] = (!empty($pathinfo[0]) ? $pathinfo[0] : 'index');
				array_shift($pathinfo); //再将将数组开头的单元移出数组 

				for($i=0; $i<count($pathinfo); $i+=2){
					$_GET[$pathinfo[$i]]=$pathinfo[$i+1];
				}
			
			}else{	
				$_GET["m"]= (!empty($_GET['m']) ? $_GET['m']: 'index');    //默认是index模块
				$_GET["a"]= (!empty($_GET['a']) ? $_GET['a'] : 'index');   //默认是index动作
	
				if($_SERVER["QUERY_STRING"]){
					$m=$_GET["m"];
					unset($_GET["m"]);  //去除数组中的m
					$a=$_GET["a"];
					unset($_GET["a"]);  //去除数组中的a
					$query=http_build_query($_GET);   //形成0=foo&1=bar&2=baz&3=boom&cow=milk格式
					//组成新的URL
					$url=$_SERVER["SCRIPT_NAME"]."/{$m}/{$a}/".str_replace(array("&","="), "/", $query);
					header("Location:".$url);
				}	
			}
		}
	}
