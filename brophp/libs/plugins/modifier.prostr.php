<?php
function smarty_modifier_prostr($var, $act="", $num=0) {
	$str="";
	switch($act){
		case 'u':
			$str=strtoupper($var);
			break;
		case 'l':
			$str=strtolower($var);
			break;
		case 's':
			$str=strrev($var);
	}

	if($num==0){
		return $str;
	}else{
		return substr($str, 0, $num);
	}
}
