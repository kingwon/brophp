<?php
/** ******************************************************************************
 * brophp.com 自动验证类，通过解析XML文件实现对表单在服务器端的自动验证。        *
 * *******************************************************************************
 * 许可声明：专为《细说PHP》读者及LAMP兄弟连学员提供的“学习型”超轻量级php框架。*
 * *******************************************************************************
 * 版权所有 (C) 2011-2013 北京易第优教育咨询有限公司，并保留所有权利。           *
 * 网站地址: http://www.lampbrother.net (LAMP兄弟连)                             *
 * *******************************************************************************
 * $Author: 高洛峰 (skygao@lampbrother.net) $                                    *
 * $Date: 2011-07-18 10:00:00 $                                                  * 
 * ******************************************************************************/
	class Validate {
		static $data;
		static $action;
		static $msg;
		static $flag=true;
		static $db=null;
		/**
		 * 获取XML内标记的属性，并处理回调内部方法
		 * @param	resource	$xml_parser	XML的资源
		 * @param	string		$tagName	数据表的名称
		 * @param	array		$args		XML标记的属性		
		 */
		static function start($xml_parser, $tagName, $args){
			if(isset($args["NAME"]) && isset($args["MSG"])) {
				if(empty($args["ACTION"]) || $args["ACTION"]=="both" || $args["ACTION"]==self::$action) {
					if(is_array(self::$data)) {
						if (array_key_exists($args["NAME"],self::$data)) {
							if(empty($args["TYPE"])){
								$method="regex";
							}else{
								$method=strtolower($args["TYPE"]);
							}
						
							if(in_array($method, get_class_methods(__CLASS__))){
								self::$method(self::$data[$args["NAME"]],$args["MSG"],$args["VALUE"],$args["NAME"]);
							}else{
								self::$msg[]="验证的规则{$args["TYPE"]} 不存在，请检查！<br>";
								self::$flag=false;
							}
					
				
						}else{
							self::$msg[]="验证的字段 {$args["NAME"]} 和表单中的输出域名称不对应<br>";
							self::$flag=false;
						}
					}
				}
			}
		
		}

		static function end($xml_parser, $tagName){
			return true;
		}	

		/**
		 * 解析XML文件
		 * @param	string	$filename	XML的文件名
		 * @param	mixed	$data		表单中输出的数据
		 * @param	string	$action		用户执行的操作add或mod,默认为both
		 * @param	object	$db		数据表的连接对象
		 */
		static function check($data, $action, $db){
			$file=substr($db->tabName, strlen(TABPREFIX));
		
			$xmlfile=$db->path."models/".$file.".xml";
			if(file_exists($xmlfile)) {
				self::$data=$data;
				self::$action=$action;
				self::$db=$db;
		
				if(is_array($data) && array_key_exists("code", $data)){
					self::vcode($data["code"], "验证码输入<font color='red'>".$data["code"]."</font>错误！");
				}

				//创建XML解析器
				$xml_parser = xml_parser_create("utf-8");

				//使用大小写折叠来保证能在元素数组中找到这些元素名称
				xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true);
				xml_set_element_handler ($xml_parser, array(__CLASS__,"start"),array(__CLASS__, "end"));
				//读取XML文件
				if (!($fp = fopen($xmlfile, "r"))) {
	    				die("无法读取XML文件$xmlfile");
				}

				//解析XML文件
				$has_error = false;			//标志位
				while ($data = fread($fp, 4096)) {
					//循环地读入XML文档，只到文档的EOF，同时停止解析
					if (!xml_parse($xml_parser, $data, feof($fp)))
					{
						$has_error = true;
						break;
					}
				}

				if($has_error) { 
					//输出错误行，列及其错误信息
					$error_line   = xml_get_current_line_number($xml_parser);
					$error_row   = xml_get_current_column_number($xml_parser);
					$error_string = xml_error_string(xml_get_error_code($xml_parser));

					$message = sprintf("XML文件 {$xmlfile}［第%d行，%d列］有误：%s", 
						$error_line,
						$error_row,
						$error_string);
						self::$msg[]= $message;
						self::$flag=false;
				}
				//关闭XML解析器指针，释放资源
				xml_parser_free($xml_parser);
				return self::$flag;
			}else{
				return true;
			}
				
		}
		/**
		 * 使用自定义的正则表达式进行验证
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	验证失败的提示消息
		 * @param	string	$rules	正则表达式
		 */ 
		static function regex($value, $msg,$rules) {
			if(!preg_match($rules, $value)) {
				self::$msg[]=$msg;
				self::$flag=false;
			}
		}
		/**
		 * 唯一性验证
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	验证失败的提示消息
		 * @param	string	$name	需要验证的字段名称
		 */ 
		static function unique($value,  $msg, $rules, $name) {
			if(self::$db->where("$name='$value'")->total() > 0){
				self::$msg[]=$msg;
				self::$flag=false;
			} 
		}
		/**
		 *非空验证
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	验证失败的提示消息
		 */ 
		static function notnull($value,  $msg) {
			if(strlen(trim($value))==0) {
				self::$msg[]=$msg;
				self::$flag=false;
			}
		}
		/**
		 *Email格式验证
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	验证失败的提示消息
		 */ 
		static function email($value, $msg) {
			$rules= "/\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/";

			if(!preg_match($rules, $value)) {
				self::$msg[]=$msg;
				self::$flag=false;
			}
		}
		/**
		 *URL格式验证
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	验证失败的提示消息
		 */ 
		static function url($value, $msg) {

			$rules='/^http\:\/\/([\w-]+\.)+[\w-]+(\/[\w-.\/?%&=]*)?$/';
			if(!preg_match($rules, $value)) {
				self::$msg[]=$msg;
				self::$flag=false;
			}

		}
		/**
		 *数字格式验证
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	验证失败的提示消息
		 */ 
		static function number($value, $msg) {
		
			$rules='/^\d+$/';
			if(!preg_match($rules, $value)) {
				self::$msg[]=$msg;
				self::$flag=false;
			}
		}
		/**
		 * 货币格式验证
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	验证失败的提示消息
		 */ 
		static function currency($value, $msg) {
		
			$rules='/^\d+(\.\d+)?$/';
			if(!preg_match($rules, $value)) {
				self::$msg[]=$msg;
				self::$flag=false;
			}
		}
		/**
		 *验证码自动验证
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	验证失败的提示消息
		 */ 
		static function vcode($value, $msg){		
			if(strtoupper($value)!=$_SESSION["code"]) {
				self::$msg[]=$msg;
				self::$flag=false;
			}
		}
		/**
		 *使用回调用函数进行验证
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	验证失败的提示消息
		 * @param	string	$rules	回调函数名称，回调用函数写在commons下的functions.inc.php
		 */ 
		static function callback($value, $msg, $rules) {
			if(!call_user_func_array($rules, array($value))) {
				self::$msg[]=$msg;
				self::$flag=false;
			}
		}
		/**
		 *验证两次输出是否一致
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	验证失败的提示消息
		 * @param	string	$rules	对应的另一个表单名称
		 */ 
		static function confirm($value, $msg, $rules) {
			if($value!=self::$data[$rules]){
				self::$msg[]=$msg;
				self::$flag=false;
			}	
		}

		/**
		 * 验证数据的值是否在一定的范围内
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	验证失败的提示消息
		 * @param	string	$rules	一个值或多个值，或一个范围
		 */
		static function in($value,$msg, $rules) {
			if(strstr($rules, ",")){
				if(!in_array($value, explode(",", $rules))){
					self::$msg[]=$msg;
					self::$flag=false;
				}	
			}else if(strstr($rules, '-')){
				list($min, $max)=explode("-", $rules);

				if(!($value>=$min && $value <=$max) ){
					self::$msg[]=$msg;
					self::$flag=false;
				}
			}else{
				if($rules!=$value){
					self::$msg[]=$msg;
					self::$flag=false;
				}
			}
		}
		/**
		 * 验证数据的值的长度是否在一定的范围内
		 * @param	string	$value	需要验证的值
		 * @param	string	$msg	验证失败的提示消息
		 * @param	string	$rules	一个范围，例如 3-20(3-20之间)、3,20(3-20之间)、3(必须是3个)、3,(3个以上)
		 */
		static function length($value,$msg, $rules) {
			$fg=strstr($rules, '-') ? "-" : ",";

			if(!strstr($rules, $fg)){
				if(strlen($value) != $rules){
					self::$msg[]=$msg;
					self::$flag=false;
				}
			}else{

				list($min, $max)=explode($fg, $rules);
				
				if(empty($max)){
					if(strlen($value) < $rules){
						self::$msg[]=$msg;
						self::$flag=false;
					}
				}else if(!(strlen($value)>=$min && strlen($value) <=$max) ){
					self::$msg[]=$msg;
					self::$flag=false;
				}
			}
		
		}
		/**
		 * 验证失败后的返回提示消息
		 */ 
		static function getMsg(){
			$msg=self::$msg;
			self::$msg='';
			self::$data=null;
			self::$action='';
			self::$flag=true;
			self::$db=null;
			return $msg;
		}

	}
