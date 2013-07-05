<?php
/** ******************************************************************************
 * brophp.com 控制器的基类，处理模块和操作，以及提供一些在操作中使用的公用方法。 *
 * *******************************************************************************
 * 许可声明：专为《细说PHP》读者及LAMP兄弟连学员提供的“学习型”超轻量级php框架。*
 * *******************************************************************************
 * 版权所有 (C) 2011-2013 北京易第优教育咨询有限公司，并保留所有权利。           *
 * 网站地址: http://www.lampbrother.net (LAMP兄弟连)                             *
 * *******************************************************************************
 * $Author: 高洛峰 (skygao@lampbrother.net) $                                    *
 * $Date: 2011-07-18 10:00:00 $                                                  * 
 * ******************************************************************************/
	class Action extends MyTpl{
		/**
		 * 该方法用来运行框架中的操制器，在brophp.php入口文件中调用
		 */
		function run(){
			if($this->left_delimiter!="<{")
				parent::__construct();	

			
			//如果有子类Common，调用这个类的init()方法 做权限控制
			if(method_exists($this, "init")){
				$this->init();
			}

			//根据动作去找对应的方法
			$method=$_GET["a"];
			if(method_exists($this, $method)){
				$this->$method();
			}else{
				Debug::addmsg("<font color='red'>没有{$_GET["a"]}这个操作！</font>");
			}	
		}

		/** 
		 * 用于在控制器中进行位置重定向
		 * @param	string	$path	用于设置重定向的位置
		 * @param	string	$args 	用于重定向到新位置后传递参数
		 * 
		 * $this->redirect("index")  /当前模块/index
		 * $this->redirect("user/index") /user/index
		 * $this->redirect("user/index", 'page/5') /user/index/page/5
		 */
		function redirect($path, $args=""){
			$path=trim($path, "/");
			if($args!="")
				$args="/".trim($args, "/");
			if(strstr($path, "/")){
				$url=$path.$args;
			}else{
				$url=$_GET["m"]."/".$path.$args;
			}

			$uri=B_APP.'/'.$url;
			//使用js跳转前面可以有输出
			echo '<script>';
			echo 'location="'.$uri.'"';
			echo '</script>';
		}

		/**
		 * 成功的消息提示框
		 * @param	string	$mess		用示输出提示消息
		 * @param	int	$timeout	设置跳转的时间，单位：秒
		 * @param	string	$location	设置跳转的新位置
		 */
		function success($mess="操作成功", $timeout=1, $location=""){
			$this->pub($mess, $timeout, $location);
			$this->assign("mark", true);  //如果成功 $mark=true
			$this->display("public/success");
			exit;
		}
		/**
		 * 失败的消息提示框
		 * @param	string	$mess		用示输出提示消息
		 * @param	int	$timeout	设置跳转的时间，单位：秒
		 * @param	string	$location	设置跳转的新位置
		 */
		function error($mess="操作失败", $timeout=3, $location=""){
			$this->pub($mess, $timeout, $location);
			$this->assign("mark", false); //如果失败 $mark=false
			$this->display("public/success");
			exit;
		}

		private function pub($mess, $timeout, $location){	
			$this->caching=0;     //设置缓存关闭
			if($location==""){
				$location="window.history.back();";
			}else{
				$path=trim($location, "/");
			
				if(strstr($path, "/")){
					$url=$path;
				}else{
					$url=$_GET["m"]."/".$path;
				}
				$location=B_APP.'/'.$url;
				$location="window.location='{$location}'";
			}
			$this->assign("mess", $mess);
			$this->assign("timeout", $timeout);
			$this->assign("location", $location);
			debug(0);
		}

	}
