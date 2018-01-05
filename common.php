<?php
/*====================================
  PHP常用函数方法
  Author: ©夜凝
  Emial：635132141@qq.com
 =====================================*/

 /*
	substrExt  		截取字符串 Common::substrExt("劳力士Rolex蚝式恒动系列",0,10);
	getCity    		根据IP获取城市名称 Common::getCity(222.222.31.114);
	getIp      		获取客户端IP Common::getIp();
	randomStr  		生成随机字符串 Common::RandomStr()
	getExcel   		PHPExcel 数据导出
	fileImport 		PHPExcel 数据导入
	logs       		日志记录函数 Common::logs("日志测试文件","_cron","logs");
 */

class Common {

	/**
	 * [ajaxUploadImg 上传缩略图]
	 * @param  [type] $folder   [文件夹名称]
	 * @param  [type] $filename [文件名，file的name]
	 * @param  [int]  $water 是否开启水印，默认开启
	 * @param  [int]  $thumb 是否生成缩略图，默认不生成
	 */
	function ajaxUploadImg($folder,$filename,$water=1,$thumb=0){

		//初始化数据
		$img_type = array("image/gif","image/jpeg","image/pjpeg","image/png","image/jpg");
		$maxsize = 2000000;
		$filesize = $_FILES[$filename]['size'];
		$filetype = $_FILES[$filename]['type'];


		if($filesize > $maxsize){

			$data = array("status" => 4,'msg'=>'上传文件超出限制！');
			output_json($data);
			return false;
		}

		if(!in_array($filetype, $img_type)){
			$data = array("status" => 3,'msg'=>'上传类型不允许！');
			output_json($data);
			return false;
		}

		$suffix = explode(".",$_FILES[$filename]['name']);
		$new_filename = date('YmdHis').rand(1000,9999).'.'.$suffix[1];
		$Upload_dir = "Upload/" .$folder."/".date("Ymd")."/";
		if(!file_exists($Upload_dir))mkdir($Upload_dir,0777);

		if(move_uploaded_file($_FILES[$filename]["tmp_name"],$Upload_dir.$new_filename)){

			if($water){
				//添加水印
				$image = new \Think\Image();
				$image->open($Upload_dir.$new_filename)->water('./Public/images/watermark.png',\Think\Image::IMAGE_WATER_CENTER,70)->save($Upload_dir.$new_filename);
			}
			if($thumb){
				//生成缩略图
				$image = new \Think\Image();
				$image->open($Upload_dir.$new_filename);
				$image->thumb(200, 91,\Think\Image::IMAGE_THUMB_SCALE)->save("Upload/thumbs/".$new_filename);
			}
			//返回数据
			$data = array(
				"status" => 1,
				"imgurl" => $Upload_dir.$new_filename,
				'size' => $filesize
			);

			output_json($data);
		}else{

			$data = array("status" => 2,'msg'=>"上传失败");	//上传失败
			output_json($data);
		}

	}
	
	/**
	 * [ajaxUploadFile 上传文件]
	 * @param  [type] $folder   [文件夹名称]
	 * @param  [type] $filename [文件名，file的name]
	 * 
	 */
	public function ajaxUploadFile($folder,$filename){
		header('Content-Type:application/json; charset=utf-8');
		//初始化数据
		$suffix = strtolower(pathinfo($_FILES[$filename]['name'],PATHINFO_EXTENSION));//文件后缀
		$allowType = array('png','jpg','gif','jpeg','pjpeg','doc','docx','xls','xlsx');//允许的上传类型
		$maxsize = 2000000;//最大上传值 2*1024*1024 = 2M
		$filesize = $_FILES[$filename]['size']; //获取上传文件的大小
		$filetype = $_FILES[$filename]['type'];

		if($filesize > $maxsize){

			$data = array("status" => 4,'msg'=>'上传文件超出限制！');
			exit(json_encode($data));
			return false;
		}

		if(!in_array($suffix, $allowType)){
			$data = array("status" => 3,'msg'=>'上传类型不允许！');
			exit(json_encode($data));
			return false;
		}

		$suffix = explode(".",$_FILES[$filename]['name']);
		$new_filename = date('YmdHis').rand(1000,9999).'.'.$suffix[1];
		$Upload_dir = "Upload/" .$folder."/".date("Ymd")."/";
		if(!file_exists($Upload_dir))mkdir($Upload_dir,0777);

		if(move_uploaded_file($_FILES[$filename]["tmp_name"],$Upload_dir.$new_filename)){
			//返回数据
			$data = array(
				"status" => 1,
				"imgurl" => $Upload_dir.$new_filename,
				'size' => $filesize
			);
			exit(json_encode($data));
		}else{

			$data = array("status" => 2,'msg'=>"上传失败");	//上传失败
			exit(json_encode($data));
		}

	}
	/*
	 * 删除目录及文件
	 * @param string $path 目录地址
	 * @param bool $delDir 是否删除目录
	 *
	*/
	function delDirAndFiles($path, $delDir = FALSE) {
		$handle = opendir($path);
		if ($handle) {
			while (false !== ( $item = readdir($handle) )) {
				if ($item != "." && $item != "..")
					is_dir("$path/$item") ? delDirAndFiles("$path/$item", $delDir) : unlink("$path/$item");
			}
			closedir($handle);
			if ($delDir) return rmdir($path);
		}else {
			if (file_exists($path)) {
				return unlink($path);
			} else {
				return FALSE;
			}
		}
	}
	/**
	 *  生成缩略图
	 *	@param string $im 		图片地址
	 *  @param int $maxwidth  	图片最大宽度
	 *  @param int $maxheight   图片最大高度
	 *  @param string $name     缩略图名称
	 *
	 */
	
	function ResizeImage($im,$maxwidth,$maxheight,$name){
		//取得当前图片大小
		$width = imagesx($im);
		$height = imagesy($im);
		//生成缩略图的大小
		if(($width > $maxwidth) || ($height > $maxheight)){
			$widthratio = $maxwidth/$width;
			$heightratio = $maxheight/$height;
			if($widthratio < $heightratio){
				$ratio = $widthratio;
			}else{
				$ratio = $heightratio;
			}
			$newwidth = $width * $widthratio;
			$newheight = $height * $heightratio;

			if(function_exists("imagecopyresampled")){
				$newim = imagecreatetruecolor($newwidth, $newheight);
				imagecopyresampled($newim, $im, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
			}else{
				$newim = imagecreate($newwidth, $newheight);
				imagecopyresized($newim, $im, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
			}
			ImageJpeg ($newim,$name,100);//--------按100质量输出
			//ImageDestroy ($newim);
		}else{
			ImageJpeg ($im,$name,100);
		}

	}

	/**
	 * [output_json 输入json格式]
	 * @param  [type] $data [数组]
	 *
	 */
	function output_json($data){

		header('Content-Type:application/json; charset=utf-8');
		exit(json_encode($data));
	}
	/*
	 * 字符串截取，支持中文和其他编码
	 *
	 * @param string $str 需要转换的字符串
	 * @param string $start 开始位置
	 * @param string $length 截取长度
	 * @param string $charset 编码格式 默认utf-8
	 * @param string $suffix 截断字符串后缀
	 * @return string
	 *
	 */

	public function substrExt($str, $start=0, $length,$suffix="",$charset="utf-8"){

		if(function_exists("mb_substr")){
			 return mb_substr($str, $start, $length, $charset).$suffix;
		}
		elseif(function_exists('iconv_substr')){
			 return iconv_substr($str,$start,$length,$charset).$suffix;
		}

		$re['utf-8']  = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
		$re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
		$re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
		$re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";

		preg_match_all($re[$charset], $str, $match);
		$slice = join("",array_slice($match[0], $start, $length));
		return $slice.$suffix;

	}

	/*
	 * 通过淘宝IP接口获取IP地理位置
	 * @param string $ip
	 * @return: string
	 *
	 */

	public function getCity($ip){

		$url="http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
		$ipinfo=json_decode(file_get_contents($url));

		if($ipinfo->code=='1'){
			return false;
		}
			// $cityAll = $ipinfo->data->region.$ipinfo->data->city;
		$city = $ipinfo->data->city;
		return $city;
	}

	/*
	 * 获取客户端ip地址
	 * @return: string
	 */

	public function getIp(){

		if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
			$ip = getenv("HTTP_CLIENT_IP");
		else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
			$ip = getenv("HTTP_X_FORWARDED_FOR");
		else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
			$ip = getenv("REMOTE_ADDR");
		else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
			$ip = $_SERVER['REMOTE_ADDR'];
		else
			$ip = "unknown";
		return $ip;

	}

	/*
	 * 随机字符串
	 * @parm 字符串的长度，默认10
	 *
	 */

	public function randomStr($length = 10) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, strlen($characters) - 1)];
		}
		return $randomString;
	 }

	/*
	 * PHPExcel 数据导出
	 * @parm $fileName 文件名称
	 * @parm $headArr Excel表头字段值 array()
	 * @parm $data 导出的数据 array()
	 *
	 */

	public function getExcel($fileName,$headArr,$data){
        //导入PHPExcel类库，因为PHPExcel没有用命名空间，只能inport导入
        require_once('framework/ext/PHPExcel.php');
		require_once('framework/ext/PHPExcel/Writer/Excel5.php');
		require_once('framework/ext/PHPExcel/IOFactory.php');

        $date = date("Y_m_d",time());
        $fileName .= "_{$date}.xls";

        //创建PHPExcel对象，注意，不能少了\
        $objPHPExcel = new \PHPExcel();
        $objProps = $objPHPExcel->getProperties();

        //设置表头
        $key = ord("A");
        //print_r($headArr);exit;
        foreach($headArr as $v){
            $colum = chr($key);
            $objPHPExcel->setActiveSheetIndex(0) ->setCellValue($colum.'1', $v);
            $objPHPExcel->setActiveSheetIndex(0) ->setCellValue($colum.'1', $v);
            $key += 1;
        }

        $column = 2;
        $objActSheet = $objPHPExcel->getActiveSheet();

		$idString = '';
        foreach($data as $key => $rows){ //行写入
            $span = ord("A");
            foreach($rows as $keyName=>$value){// 列写入
                $j = chr($span);
                $objActSheet->setCellValue($j.$column, $value);
                $span++;
            }
            $column++;
			$idString .= ','.$rows['id'];
        }

        $fileName = iconv("utf-8", "gb2312", $fileName);

        //重命名表
        //$objPHPExcel->getActiveSheet()->setTitle('test');

        //设置活动单指数到第一个表,所以Excel打开这是第一个表
        $objPHPExcel->setActiveSheetIndex(0);
        ob_end_clean();//清除缓冲区,避免乱码
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=\"$fileName\"");
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output'); //文件通过浏览器下载

		//导出成功后在数据库做一个标识
		target('Meitiku')->upData($idString);
		exit;
	}

	/*
     *
     * PHPExcel 导入数据
     * @prarm $filename 上传后生成的文件地址
     * @parm $exts 文件格式
     * @return: array()   返回一个数组，插入数据库即可
     */

    public function fileImport($filename, $exts='xls')
    {
		//也可以通过post方式获取相关参数
		$exts = request("post.exts");
		$filename = substr(request("post.file_name"),1);

		//导入PHPExcel类库，因为PHPExcel没有用命名空间
        require_once('framework/ext/PHPExcel.php');
		require_once('framework/ext/PHPExcel/Writer/Excel5.php');
		require_once('framework/ext/PHPExcel/IOFactory.php');
        //创建PHPExcel对象，注意，不能少了\
        $PHPExcel=new \PHPExcel();
        //如果excel文件后缀名为.xls，导入这个类
        if($exts == 'xls'){

			require_once('framework/ext/PHPExcel/Reader/Excel5/Escher.php');
			$PHPReader = \PHPExcel_IOFactory::createReader('Excel5');

        }else if($exts == 'xlsx'){

			require_once('framework/ext/PHPExcel/Reader/Excel2007/Escher.php');
			$PHPReader = \PHPExcel_IOFactory::createReader('Excel2007');
        }

        //载入文件
		$PHPExcel= $PHPReader->load($filename);
        //获取表中的第一个工作表，如果要获取第二个，把0改为1，依次类推
        $currentSheet=$PHPExcel->getSheet(0);
        //获取总列数
        $allColumn=$currentSheet->getHighestColumn();
        //获取总行数
        $allRow=$currentSheet->getHighestRow();
        //循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
        for($currentRow=1;$currentRow<=$allRow;$currentRow++){
            // 从哪列开始，A表示第一列
            for($currentColumn='B';$currentColumn<=$allColumn;$currentColumn++){
                // 数据坐标
                $address=$currentColumn.$currentRow;
                //读取到的数据，保存到数组$arr中
                $data[$currentRow][$currentColumn]=$currentSheet->getCell($address)->getValue();
            }

        }

        return $data;

    }

	/*
	 * 简单的log文件记录函数
	 * @parm $message  日志内容
	 * @parm $sub 文件名副名称，用作区分日志类型,如cron、wx
	 * @parm $directory 指定log目录，不指定则在根目录下创建当前日期的文件夹
	 *
	 */

	public function logs($msg,$sub='',$directory=''){

			//$sub_name = "";//文件的扩展名
			$dir = date("Ymd");//按日期命名目录

			//日志目录
			$dir = empty($directory) ? $dir."/" :$directory."/".$dir."/";

			if(!is_dir($dir)){

				mkdir($dir,07777,true);

			}

			//写入文件
			$msg = "【".date("Y-m-d H:i:s")."】:".$msg;
			$file_name = date("Ymd").$sub;
			$fd = fopen($dir.$file_name.".log","a");
			fwrite($fd, $msg."\n");

			//关闭文件
			fclose($fd);

	}

	/*
	 *
	 * 删除某个目录下的所有文件，不删除目录
	 * @$parm $dir 目录地址
	 *
	 */

	function clearFile($dir) {
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $dir . "/" . $file;
                if (!is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                    deldir($fullpath);
                }
            }
        }
    }


	/*
	 * 删除目录及目录下所有文件或删除指定文件
	 * @param str $path   待删除目录路径
	 * @param int $delDir 是否删除目录，1或true删除目录，0或false则只删除文件保留目录（包含子目录）
	 * @return bool 返回删除状态
	 */

	function delDirAndFile($path, $delDir = FALSE) {

		if (is_array($path)) {
			foreach ($path as $subPath)
			delDirAndFile($subPath, $delDir);
		}
		if (is_dir($path)) {
			$handle = opendir($path);
			if ($handle) {
				while (false !== ( $item = readdir($handle) )) {
					if ($item != "." && $item != "..")
						is_dir("$path/$item") ? delDirAndFile("$path/$item", $delDir) : unlink("$path/$item");
				}
				closedir($handle);
				if ($delDir)
					return rmdir($path);
			}
		} else {
			if (file_exists($path)) {
				return unlink($path);
			} else {
				return FALSE;
			}
		}

		clearstatcache();

	}

	/*
	 *
	 * 求多少以内的质数：能除以1和自身的整数(不包括0)
	 * @$num 指定范围，默认100以内
	 *
	 */

	/**
	 * @param int $num
	 * @return string
     */
	public function primeNums($num = 100){

		$prime = "";

		for($i = 2; $i < $num; $i++) {
			$primes = 0;
			 for($k = 1; $k <= $i; $k++){
				if($i%$k === 0) $primes++;
			 }

			if($primes <= 2){

				$prime .= " ".$i;
			}

		}

		return $prime;
	}
	
	//获取制定日期的星期
	function   get_week($date){
		//强制转换日期格式
		$date_str=date('Y-m-d',strtotime($date));

		//封装成数组
		$arr=explode("-", $date_str);

		//参数赋值
		//年
		$year=$arr[0];

		//月，输出2位整型，不够2位右对齐
		$month=sprintf('%02d',$arr[1]);

		//日，输出2位整型，不够2位右对齐
		$day=sprintf('%02d',$arr[2]);

		//时分秒默认赋值为0；
		$hour = $minute = $second = 0;

		//转换成时间戳
		$strap = mktime($hour,$minute,$second,$month,$day,$year);

		//获取数字型星期几
		$number_wk=date("w",$strap);

		//自定义星期数组
		$weekArr=array("星期日","星期一","星期二","星期三","星期四","星期五","星期六");

		//获取数字对应的星期
		return $weekArr[$number_wk];
	}
	
	//获取制定月份的所有日期，默认为当月
	
	function getMonthDays($month = "this month", $format = "Y-m-d", $dateTimeZone = false) {
		if(!$dateTimeZone) $dateTimeZone = new DateTimeZone("Asia/Shanghai");
		$start = new DateTime("first day of $month", $dateTimeZone);
		$end = new DateTime("last day of $month", $dateTimeZone);

		$days = array();
		$i = 0;
		for($time = $start; $time <= $end; $time = $time->modify("+1 day")) {
			$days[$i]['sub'] = $time->format('m月d日')."  ".get_week($time->format($format));
			$days[$i]['unix'] =  strtotime($time->format('Y-m-d'));
			$i++;
		}
		return $days;
	}
	
	
	//组合多维数组
	 function unlimitedForLayer($arrdata,$name='child',$pid=0){
		$arr = array();
		foreach($arrdata as $v){
			if($v['pid'] == $pid){
				$v[$name] = unlimitedForLayer($arrdata,$name,$v['id']);
				$arr[] = $v;
			}
		}
		return $arr;
	}


	//传递一个父级分类ID返回所有子级分类
	function getChilds($cate,$pid){
		$arr = array();
		foreach($cate as $v){
			if($v['pid'] == $pid){
				$arr[] = $v;
				$arr = array_merge($arr,getChilds($cate,$v['id']));
			}
		}
		return $arr;
	}
	//下载文件
	function download($url,$fileName){
		ob_end_clean();
		$file = file_get_contents($url);
		header("Content-type: image/png");
		header('content-disposition:attachment;filename='.$fileName);
		header('content-length:'.strlen($file));
		readfile($url);
	}
	//组合出权限节点层级
	function node_merges($node,$access = null,$pid = 0){
		$arr = array();
		foreach ($node as $v){
			if(is_array($access)){
				$v['access'] = in_array($v['id'],$access) ? 1:0;
			}
			if($v['pid'] == $pid){
				$v['child'] = node_merges($node, $access,$v['id']);
				$arr[] = $v;
			}
		}
		return $arr;
	}
	//生成二维码
	function php_code($link,$filename,$errorCorrectionLevel='L',$size=8,$isLogo=fale,$logo=''){
	    error_reporting(E_ERROR);
		//require_once 'phpqrcode/phpqrcode.php';
		vendor("Phpqrcode.qrcode");
		\QRcode::png($link,$filename,$errorCorrectionLevel,$size,2,true);
	    //添加logo
	    if($isLogo && $logo !== ""){
	        $QR = $filename;
	        $QR = imagecreatefromstring(file_get_contents($QR));
	        $logo = imagecreatefromstring(file_get_contents($logo));
	        $QR_width = imagesx($QR);//二维码图片宽度
	        $QR_height = imagesy($QR);//二维码图片高度
	        $logo_width = imagesx($logo);//logo图片宽度
	        $logo_height = imagesy($logo);//logo图片高度
	        $logo_qr_width = $QR_width / 4;
	        $scale = $logo_width/$logo_qr_width;
	        $logo_qr_height = $logo_height/$scale;
	        $from_width = ($QR_width - $logo_qr_width) / 2;
	        //重新组合图片并调整大小
	        imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
	        $logo_qr_height, $logo_width, $logo_height);
	        //输出图片
	        imagepng($QR, $filename);
	    }

	    return $filename;

	}
	//https请求（GET、POST、DELETE）
    function https_request($url, $data = null,$token=null,$method=null)
    {
		$curl = curl_init();
		if(!empty($token)){
			$headr = array();
   			$headr[] = 'Authorization: Bearer '.$token;
			curl_setopt($curl, CURLOPT_HTTPHEADER,$headr);
		 }
		if($method == "DELETE"){
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
		}
		if($method == "PUT"){
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
		}

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
	
	function httpsRequest($url, $data){

      $curl = curl_init();

      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

      curl_setopt($curl, CURLOPT_POST, 1);
      curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));

      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      $output = curl_exec($curl);
      curl_close($curl);
      return $output;
    }
	/**
	 * 二维数组冒泡排序法
	 *  $arr 要排序的数组
	 *  $field 要排序的字段
	 *  $rule 排序规则，asc或desc
 	 */
	function bubbleSort($arr,$field,$rule){
		//$tmp = array();
		for($i=0;$i<count($arr);$i++){
			for($j=1;$j<count($arr)-$i;$j++){
				//从大到小排序
				if($rule == 'desc'){
					if($arr[$j][$field] > $arr[$j-1][$field]){
						$tmp = $arr[$j-1];
						$arr[$j-1] = $arr[$j];
						$arr[$j] = $tmp;
					}
				}elseif($rule == 'asc'){
					//从小到大排序
					if($arr[$j][$field] < $arr[$j-1][$field]){
						$tmp = $arr[$j-1];
						$arr[$j-1] = $arr[$j];
						$arr[$j] = $tmp;
					}
				}
			}
		}

		return $arr;
	}
	/*下载*/
	function downCode($url,$fileName){	
		ob_end_clean();
		$file = file_get_contents($url);
		header("Content-type: image/png"); 
		header('content-disposition:attachment;filename='.$fileName); 
		header('content-length:'.strlen($file)); 
		readfile($url);
	}

	/**
	 *  移动文件(已测试可用)
	 *  file原始文件（包括路径）
	 *  $newfile新文件（包括路径）
	 */

	function movefile($file,$newfile){
		
		$newdir = dirname($newfile);
		if(!is_dir($newdir)){
				 mkdir('.'.$newdir, 0777, true);
		}

		if(rename('.'.$file,'.'.$newfile)){
			 return true;
		}else{
			 return false;
		}
	}
	
	/**
	  * 数字金额转换成中文大写金额的函数
      * String Int  $num  要转换的小写数字或小写字符串
      * return 大写字母
      * 小数位为两位
      */
    function get_amount($num){
        $c1 = "零壹贰叁肆伍陆柒捌玖";
        $c2 = "分角元拾佰仟万拾佰仟亿";
        $num = round($num, 2);
        $num = $num * 100;
        if (strlen($num) > 10) {
            return "数据太长，没有这么大的钱吧，检查下";
        }
        $i = 0;
        $c = "";
        while (1) {
            if ($i == 0) {
                $n = substr($num, strlen($num)-1, 1);
            } else {
                $n = $num % 10;
            }
            $p1 = substr($c1, 3 * $n, 3);
            $p2 = substr($c2, 3 * $i, 3);
            if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
                $c = $p1 . $p2 . $c;
            } else {
                $c = $p1 . $c;
            }
            $i = $i + 1;
            $num = $num / 10;
            $num = (int)$num;
            if ($num == 0) {
                break;
            }
        }
        $j = 0;
        $slen = strlen($c);
        while ($j < $slen) {
            $m = substr($c, $j, 6);
            if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
                $left = substr($c, 0, $j);
                $right = substr($c, $j + 3);
                $c = $left . $right;
                $j = $j-3;
                $slen = $slen-3;
            }
            $j = $j + 3;
        }

        if (substr($c, strlen($c)-3, 3) == '零') {
            $c = substr($c, 0, strlen($c)-3);
        }
        if (empty($c)) {
            return "零元整";
        }else{
            return $c . "整";
        }
    }
	
	/*
	 * API接口输入json格式数据
	 * @param $datas  输出的数据
	 * @param $error  数据状态 ture or false
	 * @param $msg    状态描述
	 * @param $extend_data 其他数据
	 * Return  json
	*/
	
	function json_data($datas,$error = false,$msg = '',$extend_data = array()) {
		$data = array();
		$data['code'] = 200;
		if($error) {
			$data['code'] = 400;
			$data['errmsg'] = $msg;
		}

		if(!empty($extend_data)) {
			$data = array_merge($data, $extend_data);
		}
		
		if(!$error){
			$data["datas"] = $datas;
		}

		$jsonFlag = 0 && version_compare(PHP_VERSION, '5.4.0') >= 0
			? JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
			: 0;

		if ($jsonFlag) {
			header('Content-type: text/plain; charset=utf-8');
		}

		if (!empty($_GET['callback'])) {
			echo $_GET['callback'].'('.json_encode($data, $jsonFlag).')';die;
		} else {
			header("Access-Control-Allow-Origin:*");
			echo json_encode($data, $jsonFlag);die;
		}
	}
	
	/**
	 * 传递一个父级分类ID返回所有子级分类
	 *
	 */	
	function getChilds($user,$pid){
		$arr = array();
		foreach($user as $v){
			if($v['pid'] == $pid){
				$arr[] = $v;
				$arr = array_merge($arr,getChilds($user,$v['id']));
			}
		}
		return $arr;
	}
	
	/**
	 * 从远程地址中下载图片
	 *
	 */
	function getImage($url, $filename='', $dirName, $fileType, $type=0)
	{
		if($url == ''){return false;}
		//获取文件原文件名
		$defaultFileName = basename($url);
		//获取文件类型
		$suffix = substr(strrchr($url,'.'), 1);
		if(!in_array($suffix, $fileType)){
			return false;
		}
		//设置保存后的文件名
		$filename = $filename == '' ? time().rand(0,9).'.'.$suffix : $defaultFileName;

		//获取远程文件资源
		if($type){
			$ch = curl_init();
			$timeout = 5;
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			$file = curl_exec($ch);
			curl_close($ch);
		}else{
			ob_start();
			readfile($url);
			$file = ob_get_contents();
			ob_end_clean();
		}
		//设置文件保存路径
		$dirName = $dirName.'/'.date('Y', time()).'/'.date('m', time()).'/'.date('d',time()).'/';
		if(!file_exists($dirName)){
			mkdir($dirName, 0777, true);
		}
		//保存文件
		$res = fopen($dirName.$filename,'a');
		fwrite($res,$file);
		fclose($res);
		return $dirName.$filename;
	}
	
	function CalculDistance($gd_lat,$gd_lng,$baidu_y,$baidu_x){
		$url = "http://api.map.baidu.com/geoconv/v1/?coords=".$gd_lat.",".$gd_lng."&from=3&to=5&ak=ObQHhCrt09i4G5NNAbdVDcoO";
		$baiduzuobiao = (array)json_decode($this->http($url));
		$aBaidu = (array)$baiduzuobiao['result'][0];

		$distance = $this->distanceBetween($aBaidu['y'], $aBaidu['x'],$baidu_y,$baidu_x);

		return $distance;
	}
	//curl请求
	function http($url, $cookie, $headerArr){
          $curl = curl_init();//初始化curl模块
          curl_setopt($curl, CURLOPT_URL, $url);//登录提交的地址
          //curl_setopt ($curl, CURLOPT_HTTPHEADER , $headerArr );  //构造IP
          curl_setopt ($curl, CURLOPT_REFERER, "http://api.map.baidu.com");   //构造来路
          curl_setopt($curl, CURLOPT_HEADER, 0);//是否显示头信息
          curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//是否自动显示返回的信息
          curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie); //设置Cookie信息保存在指定的文件中
          $rs = curl_exec($curl);//执行cURL
          curl_close($curl);//关闭cURL资源，并且释放系统资源
          return $rs;
    }
	//计算百度两坐标间的距离
	function distanceBetween($fP1Lat, $fP1Lon, $fP2Lat, $fP2Lon){
		 $fEARTH_RADIUS = 6378137;
		 //角度换算成弧度
		$fRadLon1 = deg2rad($fP1Lon);
		 $fRadLon2 = deg2rad($fP2Lon);
		 $fRadLat1 = deg2rad($fP1Lat);
		 $fRadLat2 = deg2rad($fP2Lat);
		 //计算经纬度的差值
		$fD1 = abs($fRadLat1 - $fRadLat2);
		 $fD2 = abs($fRadLon1 - $fRadLon2);
		 //距离计算
		$fP = pow(sin($fD1/2), 2) +
			   cos($fRadLat1) * cos($fRadLat2) * pow(sin($fD2/2), 2);
		 return intval($fEARTH_RADIUS * 2 * asin(sqrt($fP)) + 0.5);
	}
	
	//图片b64位处理
	function base64EncodeImage ($image_file) {
	  $base64_image = '';
	  $image_info = getimagesize($image_file);
	  $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
	  $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
	  return $base64_image;
	}

}

?>
