<?php
/** ******************************************************************************
 * brophp.com 文件件上传类，可以上传一个或同时上传多个文件。                     *
 * *******************************************************************************
 * 许可声明：专为《细说PHP》读者及LAMP兄弟连学员提供的“学习型”超轻量级php框架。*
 * *******************************************************************************
 * 版权所有 (C) 2011-2013 北京易第优教育咨询有限公司，并保留所有权利。           *
 * 网站地址: http://www.lampbrother.net (LAMP兄弟连)                             *
 * *******************************************************************************
 * $Author: 高洛峰 (skygao@lampbrother.net) $                                    *
 * $Date: 2011-07-18 10:00:00 $                                                  * 
 * ******************************************************************************/

	class FileUpload extends Image{	
		private $allowtype = array('jpg','gif','png'); //限制上传文件的类型,可以使用set()设置，使用小字母
		private $maxsize = 1000000;  //限制文件上传大小，单位是字节,可以使用set()设置
		private $israndname = true;   //设置是否随机重命名 false为不随机,可以使用set()设置
		private $thumb=array();      //设置缩放图片,可以使用set()设置
		private $watermark=array();  //设置为图片加水印,可以使用set()设置
		private $originName;   	     //源文件名
		private $tmpFileName;        //临时文件名
		private $fileType; 	     //文件类型(文件后缀)
		private $fileSize;            //文件大小
		private $newFileName; 	      //新文件名
		private $errorNum = 0;        //错误号
		private $errorMess="";       //错误报告消息
		
		/**
		 * 用于设置成员属性（$path, $allowtype,$maxsize, $israndname, $thumb,$watermark）
		 * 可以通过连贯操作一次设置多个属性值
		 *@param	string	$key	成员属性名(不区分大小写)
		 *@param	mixed	$val	为成员属性设置的值
		 *@return	object		返回自己对象$this
		 */
		function set($key, $val){
			$key=strtolower($key); 
			if(array_key_exists($key,get_class_vars(get_class($this)))){
				$this->setOption($key, $val);
			}

			return $this;
		}

		/**
		 * 调用该方法上传文件
		 * @param	string	$fileFile	上传文件的表单名称 例如：<input type="file" name="myfile"> 参数则为myfile
		 * @return	bool			 如果上传成功返回数true 
		 */
		
		function upload($fileField) {
			$return=true;
			if(!$this->checkFilePath()) {//检查文件路径
				$this->errorMess=$this->getError();
				return false;
			}
			$name=$_FILES[$fileField]['name'];
			$tmp_name=$_FILES[$fileField]['tmp_name'];
			$size=$_FILES[$fileField]['size'];
			$error=$_FILES[$fileField]['error'];

		
		
			if(is_Array($name)){  //如果是多个文件上传则$file["name"]会是一个数组
				$errors=array();
				for($i = 0; $i < count($name); $i++){ 
					if($this->setFiles($name[$i],$tmp_name[$i],$size[$i],$error[$i] )) {//设置文件信息
						if(!$this->checkFileSize() || !$this->checkFileType()){
							$errors[]=$this->getError();
							$return=false;	
						}
					}else{
						$errors[]=$this->getError();
						$return=false;
					}

					if(!$return)  // 如果有问题，则重新初使化属性
						$this->setFiles();
				}
			
				if($return){
					$fileNames=array();   //存放所有上传后文件名的变量数组
		
					for($i = 0; $i < count($name);  $i++){ 
						if($this->setFiles($name[$i],$tmp_name[$i],$size[$i],$error[$i] )) {//设置文件信息
							$this->setNewFileName(); //设置新文件名
							if(!$this->copyFile()){
								$errors[]=$this->getError();
								$return=false;
							}
							$fileNames[]=$this->newFileName;
							//设置缩放
							if(!empty($this->thumb)){
								if(empty($this->thumb["prefix"]))
									$this->thumb["prefix"]="";
							
								$this->newFileName=$this->thumb($this->newFileName, $this->thumb["width"], $this->thumb["height"], $this->thumb["prefix"]);						
							}
							//设置水印
							if(!empty($this->watermark)){
								if(empty($this->watermark["prefix"]))
									$this->watermark["prefix"]="";
								$this->newFileName=$this->watermark($this->newFileName, $this->watermark["water"], $this->watermark["position"],$this->watermark["prefix"], $this->watermark["path"]);
							}
						}
											
					}
					$this->newFileName=$fileNames;
					
				}
				$this->errorMess=$errors;
				return $return;
				
			} else {
				if($this->setFiles($name,$tmp_name,$size,$error)) {//设置文件信息
					if($this->checkFileSize() && $this->checkFileType()){	
						$this->setNewFileName(); //设置新文件名
						if($this->copyFile()){ //上传文件   返回0为成功， 小于0都为错误
							if(!empty($this->thumb)){
								if(empty($this->thumb["prefix"]))
									$this->thumb["prefix"]="";
								$this->newFileName=$this->thumb($this->newFileName, $this->thumb["width"], $this->thumb["height"], $this->thumb["prefix"]);						
							}
							if(!empty($this->watermark)){
								if(empty($this->watermark["prefix"]))
									$this->watermark["prefix"]="";
								$this->newFileName=$this->watermark($this->newFileName, $this->watermark["water"], $this->watermark["position"],$this->watermark["prefix"],$this->watermark["path"]);
							}

							return true;
						}else{
							$return=false;
						}
					}else{
						$return=false;
					}
				} else {
					$return=false;	
				}

				if(!$return)
					$this->errorMess=$this->getError();

				return $return;
			}
		
		}

		/** 
		 * 获取上传后的文件名称
		 * @param	void	 没有参数
		 * @return	string 	上传后，新文件的名称
		 */
		public function getFileName(){
			return $this->newFileName;
		}

		/**
		 * 上传失败后，调用该方法则返回，上传出错信息
		 * @param	void	 没有参数
		 * @return	string 	 返回上传文件出错的信息提示
		 */
		public function getErrorMsg(){
			return $this->errorMess;
		}
		
		//设置上传出错信息
		private function getError() {
			$str = "上传文件<font color='red'>{$this->originName}</font>时出错 : ";
			switch ($this->errorNum) {
				case 4: $str .= "没有文件被上传"; break;
				case 3: $str .= "文件只有部分被上传"; break;
				case 2: $str .= "上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值"; break;
				case 1: $str .= "上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值"; break;
				case -1: $str .= "未允许类型"; break;
				case -2: $str .= "文件过大,上传的文件不能超过{$this->maxsize}个字节"; break;
				case -3: $str .= "上传失败"; break;
				case -4: $str .= "建立存放上传文件目录失败，请重新指定上传目录"; break;
				case -5: $str .= "必须指定上传文件的路径"; break;
				default: $str .= "未知错误";
			}
			return $str.'<br>';
		}

		
		//设置和$_FILES有关的内容
		private function setFiles($name="", $tmp_name="", $size=0, $error=0) {
			$this->setOption('errorNum', $error);
			if($error)
				return false;
			$this->setOption('originName', $name);
			$this->setOption('tmpFileName',$tmp_name);
			$aryStr = explode(".", $name);
			$this->setOption('fileType', strtolower($aryStr[count($aryStr)-1]));
			$this->setOption('fileSize', $size);
			return true;
		}
    
		//为单个成员属性设置值
		private function setOption($key, $val) {
			$this->$key = $val;
		}

		//设置上传后的文件名称
		private function setNewFileName() {
			if ($this->israndname) {
				$this->setOption('newFileName', $this->proRandName());	
			} else{ 
				$this->setOption('newFileName', $this->originName);
			} 
		}
 		
		//检查上传的文件是否是合法的类型
		private function checkFileType() {
			if (in_array(strtolower($this->fileType), $this->allowtype)) {
				return true;
			}else {
				$this->setOption('errorNum', -1);
				return false;
			}
		}
    		//检查上传的文件是否是允许的大小
		private function checkFileSize() {
			if ($this->fileSize > $this->maxsize) {
				$this->setOption('errorNum', -2);
				return false;
			}else{
				return true;
			}
		}

		//检查是否有存放上传文件的目录
		private function checkFilePath() {
			if(empty($this->path)){
				$this->setOption('errorNum', -5);
				return false;
			}
			if (!file_exists($this->path) || !is_writable($this->path)) {
				if (!@mkdir($this->path, 0755)) {
					$this->setOption('errorNum', -4);
					return false;
				}
			}

			return true;
		}
		//设置随机文件名
		private function proRandName() {		
			$fileName=date('YmdHis')."_".rand(100,999);   //获取随机文件名	
			return $fileName.'.'.$this->fileType;    //返回文件名加原扩展名
		}
		

		//复制上传文件到指定的位置
		private function copyFile() {
			if(!$this->errorNum) {
				$path = rtrim($this->path, '/').'/';
				$path .= $this->newFileName;
				if (@move_uploaded_file($this->tmpFileName, $path)) {
					return true;
				}else{
					$this->setOption('errorNum', -3);
					return false;
				}
			} else {
				return false;
			}
		
		}
		
	}
