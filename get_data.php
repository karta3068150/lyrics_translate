
<?php

$db=mysqli_connect('120.105.160.7','104b','3FVxDOkr');
mysqli_select_db($db,'104b');
mysqli_query($db,"SET NAMES UTF8");
ini_set('max_execution_time', 30000);
ini_set('memory_limit', '1024M');
//header('Content-type: application/x-www-form-urlencoded; charset=UTF-8');
header('Content-type: text/html; charset=UTF-8');
//與資料庫做連結 text/html

//echo $_POST["type"]."=".$_POST["name"];

	//$result=mysqli_query($db,"SELECT * FROM ".$_POST["type"]." WHERE name='$_POST[name]'");
	$result=mysqli_query($db,"SELECT * FROM music WHERE name='payphone'");
	$re = mysqli_fetch_array($result);
	$word = explode(",",$re["cut"]); // 翻譯前文字
	$word_tk = explode (",",$re["TK"]); // tk值
	$language = $re["Language"] ; // 媒材語言
	$tran_word = Array(); // 翻譯後文字
	//$tran_word_ex = Array();
	$pinyin = Array();  // 翻譯後的拼音
	$translate_string = "";
	for ( $i =0 ; $i < sizeof($word_tk) ; $i++){
		$cu = curl_init();

		//curl_setopt($cu,CURLOPT_URL,$_POST["web"]);
		//716170.842238														  sl(source language)="來源語言" tl(target language)="翻譯標的語言"
		//curl_setopt($cu,CURLOPT_URL,"http://translate.google.cn/translate_a/single?client=t&sl=zh-TW&tl=en&hl=en&dt=bd&dt=ex&dt=ld&dt=md&dt=qc&dt=rw&dt=rm&dt=ss&dt=t&dt=at&ie=UTF-8&oe=UTF-8&source=sel&tk=372792.230476&q=窗外");
		$word[$i] = str_replace("'","\'",$word[$i]);

		$search_db_word_result = mysqli_query($db,"SELECT Chinese FROM en_zh WHERE English='$word[$i]'");
		//echo "SELECT Chinese FROM en_zh WHERE English=".$word[$i]."";
		$translate_str_num = mysqli_num_rows($search_db_word_result);
		if ($translate_str_num != 0){
			$translate_str = "";
			$translate_str_count=0;
			while ($get_db_word_result_translate = mysqli_fetch_array($search_db_word_result) ){
				$translate_str = $translate_str.$get_db_word_result_translate["Chinese"];
				$translate_str_count++;
				if ($translate_str_num != $translate_str_count) $translate_str = $translate_str.";";		
			}
			if ($language == "en"){
				$pinyin[$i] = $word[$i];
			}	
			$tran_word[$i] = $translate_str;
			$translate_string = $translate_string.$translate_str.",";
			continue;
		}
		
		curl_setopt($cu,CURLOPT_URL,"http://translate.google.cn/translate_a/single?client=t&sl=en&tl=zh-TW&hl=zh-CN&dt=bd&dt=ex&dt=ld&dt=md&dt=qc&dt=rw&dt=rm&dt=ss&dt=t&dt=at&ie=UTF-8&oe=UTF-8&source=sel&tk=".$word_tk[$i]."&q=".$word[$i]);
		//echo "http://translate.google.cn/translate_a/single?client=t&sl=en&tl=zh-TW&hl=zh-CN&dt=bd&dt=ex&dt=ld&dt=md&dt=qc&dt=rw&dt=rm&dt=ss&dt=t&dt=at&ie=UTF-8&oe=UTF-8&source=sel&tk=".$word_tk[$i]."&q=".$word[$i]."\n";
		//設定URL網址的HEADER							
		//curl_setopt($cu,CURLOPT_HEADER,false); header('Content-type: text/html; charset=UTF-8');
		//若有開啟HEADER才須執行 
		
		curl_setopt($cu, CURLOPT_HTTPHEADER, array(                                                                          
		   "Content-type: text/html; charset=UTF-8"  )                                                                       
		);
		//設定以文件的形式返回而不是輸出(顯示網頁)
		curl_setopt($cu,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($cu, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($cu, CURLOPT_TIMEOUT, 30);

		//執行並接收回傳結果
		$result = curl_exec($cu);
		if ($result === FALSE) {
			echo "cURL Error: " . curl_error($cu);
		}
		
		$r = explode("\"",$result);
		$tran_word[$i] = $r[1];
		$translate_string = $translate_string.$r[1].",";
		$re = explode("[[",$result);
		//$re[2] = str_replace("\"","",str_replace("[","",$re[2])); 
		$tmp = explode("[",str_replace("\"","",$re[1])); 
		
		//拼音部分
		if ($language == "en"){
			$pinyin[$i] = $word[$i];
		}else{
			$pin = explode(",",str_replace("]]","",$tmp[2]));
			$pinyin[$i] = $pin[3];
		}
		
		$tran_word_ex[$i] = explode("\"",$re[3])[1];
		curl_close($cu);
	}
	mysqli_query($db,"UPDATE music SET Translate='$translate_string' WHERE name='payphone'");
	$result = mysqli_query($db,"SELECT * FROM music WHERE name='payphone'");
	$re = mysqli_fetch_array($result);
	$exam = explode("\r\n",$re[3]);
	
	//echo "SELECT * FROM $_POST[type] WHERE name='$_POST[name]'";
	
	echo "\n";
	echo "{"." \"language\" : \"en\" ,"." \"word\" : [";
	foreach ($word as $value){
		echo "\"".$value."\",";
	}
	echo "\"\"";
	/*echo "],\"word_ex\" : [";
	foreach ($tran_word_ex as $value){
		echo "\"".$value."\",";
	}
	echo "\"\"";  */ 
	echo "],\"word_tran\" : [";
	foreach ($tran_word as $value){
		echo "\"".$value."\",";
	}
	echo "\"\"";
	echo "],\"pinyin\" : [";
	foreach ($pinyin as $value){
		echo "\"".$value."\",";
	}
	echo "\"\"";
	//傳輸歌詞、電子書內容以便於題材使用
	echo "],\"exam\" : [";
	foreach ($exam as $value){
		echo "\"".$value."\",";
	}
	echo "\"\""; 
	echo "]}";
	
mysqli_close($db);
	
?>
