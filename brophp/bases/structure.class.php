<?php
/** ******************************************************************************
 * brophp.com 项目结构部署类，用于自动创建所需要的项目目录和文件结构。           *
 * *******************************************************************************
 * 许可声明：专为《细说PHP》读者及LAMP兄弟连学员提供的“学习型”超轻量级php框架。*
 * *******************************************************************************
 * 版权所有 (C) 2011-2013 北京易第优教育咨询有限公司，并保留所有权利。           *
 * 网站地址: http://www.lampbrother.net (LAMP兄弟连)                             *
 * *******************************************************************************
 * $Author: 高洛峰 (skygao@lampbrother.net) $                                    *
 * $Date: 2011-07-18 10:00:00 $                                                  * 
 * ******************************************************************************/

	class Structure {
		static $mess=array();    //提示消息

		/*
		 * 创建文件
		 * @param	string	$fileName	需要创建的文件名
		 * @param	string	$str		需要向文件中写的内容字符串
		 */
		static function touch($fileName, $str){
			if(!file_exists($fileName)){
				if(file_put_contents($fileName, $str)){
					self::$mess[]="创建文件 {$fileName} 成功.";
				}
			}
		}
		/*
		 * 创建目录
		 * @param	string	$dirs		需要创建的目录名称
		 */
		static function mkdir($dirs){
			foreach($dirs as $dir){
				if(!file_exists($dir)){
					if(mkdir($dir,"0755")){
						self::$mess[]="创建目录 {$dir} 成功.";
					}
				}
			}
		}
		/**
		 * 创建系统运行时的文件
		 */
		static function runtime(){
			$dirs=array(
					PROJECT_PATH."runtime/cache/",   //系统的缓存目录
					PROJECT_PATH."runtime/cache/".TPLSTYLE,   //系统的缓存目录
					PROJECT_PATH."runtime/comps/",   //模板的组合文件
					PROJECT_PATH."runtime/comps/".TPLSTYLE,   //模板的组合文件
					PROJECT_PATH."runtime/comps/".TPLSTYLE."/".TMPPATH,   //模板的组合文件
					PROJECT_PATH."runtime/data/",    //数据表的结构文件
					PROJECT_PATH."runtime/controls/",
					PROJECT_PATH."runtime/controls/".TMPPATH,
					PROJECT_PATH."runtime/models/",
					PROJECT_PATH."runtime/models/".TMPPATH
				);
			self::mkdir($dirs);   //创建目录	
		}
		/**
		 *创建项目的目录结构
		 */
		static function create(){
			self::mkdir(array(PROJECT_PATH."runtime/"));
			//文件锁，一旦生成，就不再创建
			$structFile=PROJECT_PATH."runtime/".str_replace("/","_",$_SERVER["SCRIPT_NAME"]);  //主入口文件名

			if(!file_exists($structFile)) {	
				$fileName=PROJECT_PATH."config.inc.php";
				$str=<<<st
<?php
	define("DEBUG", 1);				      //开启调试模式 1 开启 0 关闭
	define("DRIVER","pdo");				      //数据库的驱动，本系统支持pdo(默认)和mysqli两种
	//define("DSN", "mysql:host=localhost;dbname=brophp"); //如果使用PDO可以使用，不使用则默认连接MySQL
	define("HOST", "localhost");			      //数据库主机
	define("USER", "root");                               //数据库用户名
	define("PASS", "");                                   //数据库密码
	define("DBNAME","brophp");			      //数据库名
	define("TABPREFIX", "bro_");                           //数据表前缀
	define("CSTART", 0);                                  //缓存开关 1开启，0为关闭
	define("CTIME", 60*60*24*7);                          //缓存时间
	define("TPLPREFIX", "tpl");                           //模板文件的后缀名
	define("TPLSTYLE", "default");                        //默认模板存放的目录

	//\$memServers = array("localhost", 11211);	     //使用memcache服务器
	/*
	如果有多台memcache服务器可以使用二维数组
	\$memServers = array(
			array("www.lampbrother.net", '11211'),
			array("www.brophp.com", '11211'),
			...
		);
	*/
st;
				self::touch($fileName, $str);
				if(!defined("DEBUG"))
					include $fileName;
				$dirs=array(
					PROJECT_PATH."classes/",    //项目的通用类
					PROJECT_PATH."commons/",    //项目的通用函数 functions.inc.php
					PROJECT_PATH."public",      //系统公共目录
					PROJECT_PATH."public/uploads/",  //系统公共上传文件目录
					PROJECT_PATH."public/css/",      //系统公css共目录
					PROJECT_PATH."public/js/",       //系统公共javascript目录
					PROJECT_PATH."public/images/",   //系统公共图片目录
					APP_PATH,                   //当前的应用目录
					APP_PATH."models/",         //当前应用的模型目录
					APP_PATH."controls/",       //当前应用的控制器目录
					APP_PATH."views/",          //当前应用的视图目录
					APP_PATH."views/".TPLSTYLE, //当前应用的模板目录
					APP_PATH."views/".TPLSTYLE."/public/",           //公用模板目录
					APP_PATH."views/".TPLSTYLE."/resource/",        //当前应用模板公用资源目录
					APP_PATH."views/".TPLSTYLE."/resource/css/",     //当前应用模板CSS目录
					APP_PATH."views/".TPLSTYLE."/resource/js/",      //当前应用模板js目录
					APP_PATH."views/".TPLSTYLE."/resource/images/"  //当前应用模板图标目录
				);
			
				self::mkdir($dirs);
				self::touch(PROJECT_PATH."commons/functions.inc.php", "<?php\n\t//全局可以使用的通用函数声明在这个文件中.");
				//创建统一的 消息 模板
				$success=APP_PATH."views/".TPLSTYLE."/public/success.".TPLPREFIX;
				if(!file_exists($success))
					copy(BROPHP_PATH."commons/success",$success);
			
				$str=<<<st
<?php
	class Common extends Action {
		function init(){

		}		
	}
st;

				self::touch(APP_PATH."controls/common.class.php", $str);
	
				$str=<<<st
<?php
	class Index {
		function index(){
			echo "<b>欢迎使用《细说PHP》中的BroPHP框架1.0, 第一次访问时会自动生成项目结构：</b><br>";
			echo '<pre>';
			echo file_get_contents('{$structFile}');
			echo '</pre>';
		}		
	}
st;

				self::touch(APP_PATH."controls/index.class.php", $str);

				self::touch($structFile, implode("\n", self::$mess));
				
			}	
			self::runtime();
		}
		/**
		 * 父类控制器的生成
		 * @param	string	$srccontrolerpath	原基类控制器的路径
		 * @param	string	$controlerpath		目标基类控制器的路径
		 */ 
		static function commoncontroler($srccontrolerpath,$controlerpath){
			$srccommon=$srccontrolerpath."common.class.php";
			$common=$controlerpath."common.class.php";
			//如果新控制器不存在， 或原控制器有修改就重新生成
			if(!file_exists($common) || filemtime($srccommon) > filemtime($common)){
				copy($srccommon, $common); 	
			}	
		}

		static function controler($srccontrolerfile,$controlerpath,$m){
			$controlerfile=$controlerpath.strtolower($m)."action.class.php";
			//如果新控制器不存在， 或原控制器有修改就重新生成
			if(!file_exists($controlerfile) || filemtime($srccontrolerfile) > filemtime($controlerfile)){
				//将控制器类中的内容读出来
				$classContent=file_get_contents($srccontrolerfile);	
				//看类中有没有继承父类
				$super='/extends\s+(.+?)\s*{/i'; 
				//如果已经有父类
				if(preg_match($super,$classContent, $arr)) {
					$classContent=preg_replace('/class\s+(.+?)\s+extends\s+(.+?)\s*{/i','class \1Action extends \2 {',$classContent,1);
					//新生成控制器类
					file_put_contents($controlerfile, $classContent);
				//没有父类时
				}else{ 
					//继承父类Common
					$classContent=preg_replace('/class\s+(.+?)\s*{/i','class \1Action extends Common {',$classContent,1);
					//生成控制器类
					file_put_contents($controlerfile,$classContent);	
				}
			}
	
	
		}

		static function model($className, $app){
			$driver="D".DRIVER; //父类名
			$path=PROJECT_PATH."runtime/models/".TMPPATH;
			if($app==""){
				$src=APP_PATH."models/".strtolower($className).".class.php";
				$psrc=APP_PATH."models/___.class.php";
				$className=ucfirst($className).'Model';
				$parentClass='___model';
				$to=$path.strtolower($className).".class.php";
				$pto=$path.$parentClass.".class.php";
				
			}else{
				$src=PROJECT_PATH.$app."/models/".strtolower($className).".class.php";
				$psrc=PROJECT_PATH.$app."/models/___.class.php";
				$className=ucfirst($app).ucfirst($className).'Model';
				$parentClass=ucfirst($app).'___model';
				$to=$path.strtolower($className).".class.php";
				$pto=$path.$parentClass.".class.php";
			}

			
			//如果有原model存在
			if(file_exists($src)) {	
				$classContent=file_get_contents($src);											     $super='/extends\s+(.+?)\s*{/i'; 
				//如果已经有父类
				if(preg_match($super,$classContent, $arr)) {
					$psrc=str_replace("___", strtolower($arr[1]), $psrc);
					$pto=str_replace("___", strtolower($arr[1]), $pto);
					
					if(file_exists($psrc)){
						if(!file_exists($pto) || filemtime($psrc) > filemtime($pto)){
							$pclassContent=file_get_contents($psrc);
							$pclassContent=preg_replace('/class\s+(.+?)\s*{/i','class '.$arr[1].'Model extends '.$driver.' {',$pclassContent,1);
				
							file_put_contents($pto, $pclassContent);
				
						}
				
					}else{
						Debug::addmsg("<font color='red'>文件{$psrc}不存在!</font>");
					} 
					$driver=$arr[1]."Model";
					include_once $pto;
				}
				if(!file_exists($to) || filemtime($src) > filemtime($to) ) {	
					$classContent=preg_replace('/class\s+(.+?)\s*{/i','class '.$className.' extends '.$driver.' {',$classContent,1);
					//生成model
					file_put_contents($to,$classContent);
				}	
			}else{
				if(!file_exists($to)){
					$classContent="<?php\n\tclass {$className} extends {$driver}{\n\t}";
					//生成model
					file_put_contents($to,$classContent);	
				}	
			}

			include_once $to;
			return $className;
		}

	}
