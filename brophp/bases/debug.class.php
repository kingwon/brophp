<?php
/** ******************************************************************************
 * brophp.com 调试模式类，用于在开发阶段调试程序使用。                           *
 * *******************************************************************************
 * 许可声明：专为《细说PHP》读者及LAMP兄弟连学员提供的“学习型”超轻量级php框架。*
 * *******************************************************************************
 * 版权所有 (C) 2011-2013 北京易第优教育咨询有限公司，并保留所有权利。           *
 * 网站地址: http://www.lampbrother.net (LAMP兄弟连)                             *
 * *******************************************************************************
 * $Author: 高洛峰 (skygao@lampbrother.net) $                                    *
 * $Date: 2011-07-18 10:00:00 $                                                  * 
 * ******************************************************************************/
	class Debug {
		static $includefile=array();
		static $info=array();
		static $sqls=array();
		static $startTime;                //保存脚本开始执行时的时间（以微秒的形式保存）
		static $stopTime;                //保存脚本结束执行时的时间（以微秒的形式保存）
		
		static $msg = array(
       			 E_WARNING=>'运行时警告',
       			 E_NOTICE=>'运行时提醒',
        		 E_STRICT=>'编码标准化警告',
        		 E_USER_ERROR=>'自定义错误',
        		 E_USER_WARNING=>'自定义警告',
        		 E_USER_NOTICE=>'自定义提醒',
        		 'Unkown '=>'未知错误'
		 );

		/**
		 * 在脚本开始处调用获取脚本开始时间的微秒值
		 */
		static function start(){                       
			self::$startTime = microtime(true);   //将获取的时间赋给成员属性$startTime
		}
		/**
		 *在脚本结束处调用获取脚本结束时间的微秒值
		 */
		static function stop(){
			self::$stopTime= microtime(true);   //将获取的时间赋给成员属性$stopTime
		}

		/**
		 *返回同一脚本中两次获取时间的差值
		 */
		static function spent(){
			return round((self::$stopTime - self::$startTime) , 4);  //计算后以4舍5入保留4位返回
		}

    		/*错误 handler*/
   		static function Catcher($errno, $errstr, $errfile, $errline){
	   		if(!isset(self::$msg[$errno])) 
				$errno='Unkown';

			if($errno==E_NOTICE || $errno==E_USER_NOTICE)
				$color="#000088";
			else
				$color="red";

	   		$mess='<font color='.$color.'>';
	   		$mess.='<b>'.self::$msg[$errno]."</b>[在文件 {$errfile} 中,第 $errline 行]:";
	   		$mess.=$errstr;
	   		$mess.='</font>'; 		
	  		self::addMsg($mess);
		}
		/**
		 * 添加调试消息
		 * @param	string	$msg	调试消息字符串
		 * @param	int	$type	消息的类型
		 */
		static function addmsg($msg,$type=0) {
			if(defined("DEBUG") && DEBUG==1){
				switch($type){
					case 0:
						self::$info[]=$msg;
						break;
					case 1:
						self::$includefile[]=$msg;
						break;
					case 2:
						self::$sqls[]=$msg;
						break;
				}
			}
		}
		/**
		 * 输出调试消息
		 */
		static function message(){
			echo '<div style="float:left;clear:both;text-align:left;font-size:11px;color:#888;width:95%;margin:10px;padding:10px;background:#F5F5F5;border:1px dotted #778855;z-index:100">';
			echo '<div style="float:left;width:100%;"><span style="float:left;width:200px;"><b>运行信息</b>( <font color="red">'.self::spent().' </font>秒):</span><span onclick="this.parentNode.parentNode.style.display=\'none\'" style="cursor:pointer;float:right;width:35px;background:#500;border:1px solid #555;color:white">关闭X</span></div><br>';
			echo '<ul style="margin:0px;padding:0 10px 0 10px;list-style:none">';
			if(count(self::$includefile) > 0){
				echo '［自动包含］';
				foreach(self::$includefile as $file){
					echo '<li>&nbsp;&nbsp;&nbsp;&nbsp;'.$file.'</li>';
				}		
			}
			if(count(self::$info) > 0 ){
				echo '<br>［系统信息］';
				foreach(self::$info as $info){
					echo '<li>&nbsp;&nbsp;&nbsp;&nbsp;'.$info.'</li>';
				}
			}

			if(count(self::$sqls) > 0) {
				echo '<br>［SQL语句］';
				foreach(self::$sqls as $sql){
					echo '<li>&nbsp;&nbsp;&nbsp;&nbsp;'.$sql.'</li>';
				}
			}
			echo '</ul>';
			echo '</div>';	
		}
	}
