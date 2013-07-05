<?php
/** ******************************************************************************
 * brophp.com 框架内置的函数库文件，声明在这个文件中的函数可以任何位置直接调用。 *
 * *******************************************************************************
 * 许可声明：专为《细说PHP》读者及LAMP兄弟连学员提供的“学习型”超轻量级php框架。*
 * *******************************************************************************
 * 版权所有 (C) 2011-2013 北京易第优教育咨询有限公司，并保留所有权利。           *
 * 网站地址: http://www.lampbrother.net (LAMP兄弟连)                             *
 * *******************************************************************************
 * $Author: 高洛峰 (skygao@lampbrother.net) $                                    *
 * $Date: 2011-07-18 10:00:00 $                                                  * 
 * ******************************************************************************/

	/**
	 * 输出各种类型的数据，调试程序时打印数据使用。
	 * @param	mixed	参数：可以是一个或多个任意变量或值
	 */
	function p(){
		$args=func_get_args();  //获取多个参数
		if(count($args)<1){
			Debug::addmsg("<font color='red'>必须为p()函数提供参数!");
			return;
		}	

		echo '<div style="width:100%;text-align:left"><pre>';
		//多个参数循环输出
		foreach($args as $arg){
			if(is_array($arg)){  
				print_r($arg);
				echo '<br>';
			}else if(is_string($arg)){
				echo $arg.'<br>';
			}else{
				var_dump($arg);
				echo '<br>';
			}
		}
		echo '</pre></div>';	
	}
	/**
	 * 创建Models中的数据库操作对象
	 *  @param	string	$className	类名或表名
	 *  @param	string	$app	 应用名,访问其他应用的Model
	 *  @return	object	数据库连接对象
	 */
	function D($className=null,$app=""){
		$db=null;	
		//如果没有传表名或类名，则直接创建DB对象，但不能对表进行操作
		if(is_null($className)){
			$class="D".DRIVER;

			$db=new $class;
		}else{
			$className=strtolower($className);
			$model=Structure::model($className, $app);	
			$model=new $model();

			//如果表结构不存在，则获取表结构
			$model->setTable($className);		
		

			$db=$model;
		}
		if($app=="")
			$db->path=APP_PATH;
		else
			$db->path=PROJECT_PATH.strtolower($app).'/';
		return $db;
	}
	/**
	 * 文件尺寸转换，将大小将字节转为各种单位大小
	 * @param	int	$bytes	字节大小
	 * @return	string	转换后带单位的大小
	 */
	function tosize($bytes) {       	 	     //自定义一个文件大小单位转换函数
		if ($bytes >= pow(2,40)) {      		     //如果提供的字节数大于等于2的40次方，则条件成立
			$return = round($bytes / pow(1024,4), 2);    //将字节大小转换为同等的T大小
			$suffix = "TB";                        	     //单位为TB
		} elseif ($bytes >= pow(2,30)) {  		     //如果提供的字节数大于等于2的30次方，则条件成立
			$return = round($bytes / pow(1024,3), 2);    //将字节大小转换为同等的G大小
			$suffix = "GB";                              //单位为GB
		} elseif ($bytes >= pow(2,20)) {  		     //如果提供的字节数大于等于2的20次方，则条件成立
			$return = round($bytes / pow(1024,2), 2);    //将字节大小转换为同等的M大小
			$suffix = "MB";                              //单位为MB
		} elseif ($bytes >= pow(2,10)) {  		     //如果提供的字节数大于等于2的10次方，则条件成立
			$return = round($bytes / pow(1024,1), 2);    //将字节大小转换为同等的K大小
			$suffix = "KB";                              //单位为KB
		} else {                     			     //否则提供的字节数小于2的10次方，则条件成立
			$return = $bytes;                            //字节大小单位不变
			$suffix = "Byte";                            //单位为Byte
		}
		return $return ." " . $suffix;                       //返回合适的文件大小和单位
	}
	/**
	 * 关闭调试模式
	 * @param	bool	$falg	调式模式的关闭开关
	 */
	function debug($falg=0){
		$GLOBALS["debug"]=$falg;
	}

