<?php	
/*********************************************************************************
 * brophp.com 框架入口文件，所有脚本都是从这个文件开始执行，主要是一些全局设置。 *
 * *******************************************************************************
 * 许可声明：专为《细说PHP》读者及LAMP兄弟连学员提供的“学习型”超轻量级php框架。*
 * *******************************************************************************
 * 版权所有 (C) 2011-2013 北京易第优教育咨询有限公司，并保留所有权利。           *
 * 网站地址: http://www.lampbrother.net (LAMP兄弟连)                             *
 * *******************************************************************************
 * $Author: 高洛峰 (skygao@lampbrother.net) $                                    *
 * $Date: 2011-07-18 10:00:00 $                                                  * 
 * ******************************************************************************/
	
	header("Content-Type:text/html;charset=utf-8");  //设置系统的输出字符为utf-8
	date_default_timezone_set("PRC");    		 //设置时区（中国）

	//PHP程序所有需要的路径，都使用相对路径
	define("BROPHP_PATH", rtrim(BROPHP, '/').'/');     //BroPHP框架的路径
	define("APP_PATH", rtrim(APP,'/').'/');            //用户项目的应用路径
	define("PROJECT_PATH", dirname(BROPHP_PATH).'/');  //项目的根路径，也就是框架所在的目录
	define("TMPPATH", str_replace(array(".", "/"), "_", ltrim($_SERVER["SCRIPT_NAME"], '/'))."/");
	
	//包含系统配置文件
	$config=PROJECT_PATH."config.inc.php";
	if(file_exists($config)){
		include $config;
	}
	//设置Debug模式
	if(defined("DEBUG") && DEBUG==1){
		$GLOBALS["debug"]=1;                 //初例化开启debug
		error_reporting(E_ALL ^ E_NOTICE);   //输出除了注意的所有错误报告
		include BROPHP_PATH."bases/debug.class.php";  //包含debug类
		Debug::start();                               //开启脚本计算时间
		set_error_handler(array("Debug", 'Catcher')); //设置捕获系统异常
	}else{
		ini_set('display_errors', 'Off'); 		//屏蔽错误输出
		ini_set('log_errors', 'On');             	//开启错误日志，将错误报告写入到日志中
		ini_set('error_log', PROJECT_PATH.'runtime/error_log'); //指定错误日志文件

	}
	//包含框架中的函数库文件
	include BROPHP_PATH.'commons/functions.inc.php';
	

	//包含全局的函数库文件，用户可以自己定义函数在这个文件中
	$funfile=PROJECT_PATH."commons/functions.inc.php";
	if(file_exists($funfile))
		include $funfile;

	
	//设置包含目录（类所在的全部目录）,  PATH_SEPARATOR 分隔符号 Linux(:) Windows(;)
	$include_path=get_include_path();                         //原基目录
	$include_path.=PATH_SEPARATOR.BROPHP_PATH."bases/";       //框架中基类所在的目录
	$include_path.=PATH_SEPARATOR.BROPHP_PATH."classes/" ;    //框架中扩展类的目录
	$include_path.=PATH_SEPARATOR.BROPHP_PATH."libs/" ;       //模板Smarty所在的目录
	$include_path.=PATH_SEPARATOR.PROJECT_PATH."classes/";    //项目中用的到的工具类
	$controlerpath=PROJECT_PATH."runtime/controls/".TMPPATH;  //生成控制器所在的路径
	$include_path.=PATH_SEPARATOR.$controlerpath;             //当前应用的控制类所在的目录 

	//设置include包含文件所在的所有目录	
	set_include_path($include_path);

	//自动加载类 	
	function __autoload($className){
		if($className=="memcache"){        //如果是系统的Memcache类则不包含
			return;
		}else if($className=="Smarty"){    //如果类名是Smarty类，则直接包含
			include "Smarty.class.php";
		}else{                             //如果是其他类，将类名转为小写
			include strtolower($className).".class.php";	
		}
		Debug::addmsg("<b> $className </b>类", 1);  //在debug中显示自动包含的类
	}

	//判断是否开启了页面静态化缓存
	if(CSTART==0){
		Debug::addmsg("<font color='red'>没有开启页面缓存!</font>（但可以使用）"); 
	}else{
		Debug::addmsg("开启页面缓存，实现页面静态化!"); 
	}
	
	//启用memcache缓存
	if(!empty($memServers)){
		//判断memcache扩展是否安装
		if(extension_loaded("memcache")){
			$mem=new MemcacheModel($memServers);
			//判断Memcache服务器是否有异常
			if(!$mem->mem_connect_error()){
				Debug::addmsg("<font color='red'>连接memcache服务器失败,请检查!</font>"); //debug
			}else{
				define("USEMEM",true);
				Debug::addmsg("启用了Memcache");
			}
		}else{
			Debug::addmsg("<font color='red'>PHP没有安装memcache扩展模块,请先安装!</font>"); //debug
		}	
	}else{
		Debug::addmsg("<font color='red'>没有使用Memcache</font>(为程序的运行速度，建议使用Memcache)");  //debug
	}

	//如果Memcach开启，设置将Session信息保存在Memcache服务器中
	if(defined("USEMEM")){
		MemSession::start($mem->getMem());
		Debug::addmsg("开启会话Session (使用Memcache保存会话信息)"); //debug

	}else{
		session_start();
		Debug::addmsg("<font color='red'>开启会话Session </font>(但没有使用Memcache，开启Memcache后自动使用)"); //debug
	}
	Debug::addmsg("会话ID:".session_id());
	
	Structure::create();   //初使化时，创建项目的目录结构
	Prourl::parseUrl();    //解析处理URL 

	//模板文件中所有要的路径，html\css\javascript\image\link等中用到的路径，从WEB服务器的文档根开始

	
	$spath=rtrim(substr(dirname(str_replace("\\", '/', dirname(__FILE__))), strlen(rtrim($_SERVER["DOCUMENT_ROOT"],"/\\"))), '/\\');
	$GLOBALS["root"]=$spath.'/'; //Web服务器根到项目的根
	$GLOBALS["public"]=$GLOBALS["root"].'public/';        //项目的全局资源目录
	$GLOBALS["res"]=rtrim(dirname($_SERVER["SCRIPT_NAME"]),"/\\").'/'.ltrim(APP_PATH, './')."views/".TPLSTYLE."/resource/"; //当前应用模板的资源

	$GLOBALS["app"]=$_SERVER["SCRIPT_NAME"].'/';           	//当前应用脚本文件
	$GLOBALS["url"]=$GLOBALS["app"].$_GET["m"].'/';       //访问到当前模块

	define("B_ROOT", rtrim($GLOBALS["root"], '/'));
	define("B_PUBLIC", rtrim($GLOBALS["public"], '/'));
	define("B_APP", rtrim($GLOBALS["app"], '/'));
	define("B_URL", rtrim($GLOBALS["url"], '/'));
	define("B_RES", rtrim($GLOBALS["res"], '/'));


	//控制器类所在的路径
	$srccontrolerfile=APP_PATH."controls/".strtolower($_GET["m"]).".class.php";

	Debug::addmsg("当前访问的控制器类在项目应用目录下的: <b>$srccontrolerfile</b> 文件！");
	//控制器类的创建
	if(file_exists($srccontrolerfile)){
		Structure::commoncontroler(APP_PATH."controls/",$controlerpath);
		Structure::controler($srccontrolerfile, $controlerpath, $_GET["m"]); 

		$className=ucfirst($_GET["m"])."Action";
		
		$controler=new $className();
		$controler->run();

	}else{
		Debug::addmsg("<font color='red'>对不起!你访问的模块不存在,应该在".APP_PATH."controls目录下创建文件名为".strtolower($_GET["m"]).".class.php的文件，声明一个类名为".ucfirst($_GET["m"])."的类！</font>");
		
	}
	//设置输出Debug模式的信息
	if(defined("DEBUG") && DEBUG==1 && $GLOBALS["debug"]==1){
		Debug::stop();
		Debug::message();
	}



