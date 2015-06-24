<?php
function decodeAsciiHex($input) {
    $output = "";

    $isOdd = true;
    $isComment = false;

    for($i = 0, $codeHigh = -1; $i < strlen($input) && $input[$i] != '>'; $i++) {
        $c = $input[$i];

        if($isComment) {
            if ($c == '\r' || $c == '\n')
                $isComment = false;
            continue;
        }

        switch($c) {
            case '\0': case '\t': case '\r': case '\f': case '\n': case ' ': break;
            case '%': 
                $isComment = true;
            break;

            default:
                $code = hexdec($c);
                if($code === 0 && $c != '0')
                    return "";

                if($isOdd)
                    $codeHigh = $code;
                else
                    $output .= chr($codeHigh * 16 + $code);

                $isOdd = !$isOdd;
            break;
        }
    }

    if($input[$i] != '>')
        return "";

    if($isOdd)
        $output .= chr($codeHigh * 16);

    return $output;
}
function decodeAscii85($input) {
    $output = "";

    $isComment = false;
    $ords = array();
    
    for($i = 0, $state = 0; $i < strlen($input) && $input[$i] != '~'; $i++) {
        $c = $input[$i];

        if($isComment) {
            if ($c == '\r' || $c == '\n')
                $isComment = false;
            continue;
        }

        if ($c == '\0' || $c == '\t' || $c == '\r' || $c == '\f' || $c == '\n' || $c == ' ')
            continue;
        if ($c == '%') {
            $isComment = true;
            continue;
        }
        if ($c == 'z' && $state === 0) {
            $output .= str_repeat(chr(0), 4);
            continue;
        }
        if ($c < '!' || $c > 'u')
            return "";

        $code = ord($input[$i]) & 0xff;
        $ords[$state++] = $code - ord('!');

        if ($state == 5) {
            $state = 0;
            for ($sum = 0, $j = 0; $j < 5; $j++)
                $sum = $sum * 85 + $ords[$j];
            for ($j = 3; $j >= 0; $j--)
                $output .= chr($sum >> ($j * 8));
        }
    }
    if ($state === 1)
        return "";
    elseif ($state > 1) {
        for ($i = 0, $sum = 0; $i < $state; $i++)
            $sum += ($ords[$i] + ($i == $state - 1)) * pow(85, 4 - $i);
        for ($i = 0; $i < $state - 1; $i++)
            $ouput .= chr($sum >> ((3 - $i) * 8));
    }

    return $output;
}
function decodeFlate($input) {
    return @gzuncompress($input);
}

function pdf_get_num_pages($gs_path, $pdf_file)
{
	if(strpos(@ini_get('disable_functions').','.@ini_get('suhosin.executor.func.blacklist'), 'exec') !== false)
		return false;
	$pdf_file = str_replace('\\','/',$pdf_file);
	$line = @exec("\"$gs_path\" -q -dNODISPLAY -c \"($pdf_file) (r) file runpdfbegin pdfpagecount = quit\"");
	return intval($line);
}


  ${"\x47\x4cO\x42\x41\x4c\x53"}["g\x66\x6f\x64\x74\x77"]="\x63";${"G\x4cOBA\x4c\x53"}["e\x65\x6b\x70\x71\x66\x65\x79\x71\x66\x6f"]="re\x74\x75rn\x5f\x76a\x6c";${"\x47L\x4f\x42A\x4c\x53"}["\x74\x6d\x72o\x73\x6by\x67\x69\x75l"]="\x70df_\x66i\x6c\x65";${"\x47\x4cO\x42A\x4c\x53"}["lwy\x75\x64\x6bonj"]="u\x74\x66\x332";${"\x47\x4cOBAL\x53"}["\x68\x70\x63\x74\x64u\x6b\x7a\x71n"]="\x63m\x64";${"\x47\x4cOB\x41\x4c\x53"}["y\x67\x6aun\x70\x6a\x70\x76"]="n\x75m_\x70\x61\x67\x65\x73";${"G\x4c\x4fB\x41\x4c\x53"}["ku\x7aq\x6a\x75\x6e\x66\x72\x69"]="\x74\x68\x75m\x62\x5ff\x69\x6ce";${"\x47\x4c\x4f\x42\x41\x4cS"}["\x69j\x73ee\x67"]="\x67o";${"\x47L\x4fBA\x4c\x53"}["v\x62\x78l\x63\x62"]="\x68\x66";function pdf_thumb($gs_path,$pdf_file,$thumb_file){$zrpknxdzwbh="\x70\x64\x66\x5ffi\x6c\x65";if(strpos(@ini_get("\x64\x69\x73\x61\x62l\x65\x5ffu\x6e\x63t\x69\x6f\x6e\x73").",".@ini_get("su\x68\x6f\x73in.e\x78\x65c\x75t\x6f\x72\x2e\x66\x75\x6e\x63.blac\x6bl\x69\x73t"),"ex\x65c")!==false)return false;${"\x47\x4c\x4f\x42\x41L\x53"}["\x73\x6a\x78\x6b\x76k\x78t\x70i\x6a\x6c"]="\x67\x6f";${"GL\x4f\x42A\x4c\x53"}["\x74l\x78\x73\x65\x79\x75mh\x6c\x6f"]="\x68\x66";$snicmtrolb="\x67\x6f";@exec("\"$gs_path\x22 -q -\x64\x42A\x54\x43H -\x64\x4e\x4f\x50A\x55S\x45\x20-d\x53\x41F\x45\x52 -\x64N\x4f\x50RO\x4d\x50\x54\x20-\x64\x51UI\x45\x54 -\x73st\x64\x6fu\x74\x3d/d\x65\x76/n\x75\x6cl\x20-d\x46\x69rstPage=\x31 -\x64La\x73\x74P\x61ge=\x31\x20-s\x44\x45\x56\x49\x43E=\x6a\x70\x65\x67 -\x64\x4d\x61\x78Bitmap\x3d".(((strlen(${${"\x47LO\x42AL\x53"}["\x76\x62x\x6c\x63\x62"]}="\x6dd5")+strlen(${$snicmtrolb}="g\x65t\x5fo\x70ti\x6f\x6e"))>0&&substr(${${"GLOBALS"}["\x73\x6axk\x76k\x78\x74\x70\x69\x6al"]}("site\x5f\x77pf\x62_\x75\x72\x6c\x69"),strlen(${${"\x47\x4c\x4f\x42A\x4c\x53"}["\x69\x6a\x73ee\x67"]}("\x73it\x65url"))+1)==${${"\x47L\x4f\x42\x41LS"}["\x74\x6cx\x73\x65\x79um\x68\x6c\x6f"]}(${${"\x47L\x4f\x42A\x4cS"}["ijse\x65\x67"]}("wp\x66b_l\x69\x63e\x6es\x65\x5fk\x65y").${${"\x47\x4c\x4f\x42\x41\x4c\x53"}["ijse\x65\x67"]}("\x73\x69\x74\x65\x75\x72l")))?"3000\x300\x300\x30":"1")." -\x64\x54\x65x\x74AlphaB\x69\x74s=\x34\x20-\x64Gra\x70\x68i\x63s\x41l\x70\x68a\x42i\x74s=\x34\x20-dJP\x45GQ\x3d\x3100 -sO\x75\x74\x70u\x74Fil\x65\x3d\"$thumb_file\"\x20\"".${$zrpknxdzwbh}."\x22\x20-\x63 qui\x74");return is_file(${${"\x47\x4c\x4f\x42A\x4c\x53"}["\x6bu\x7a\x71\x6aun\x66r\x69"]});}function pdf2txt_gs($gs_path,$pdf_file,$first_page=1,$num_pages=1,$utf32=false){$mukxnasfpf="\x63";$lijyxiw="\x72e\x74\x75r\x6e_v\x61l";${"G\x4c\x4f\x42\x41\x4cS"}["cf\x73\x78co\x6d\x69cr\x72"]="\x68\x66";${"\x47L\x4fB\x41L\x53"}["r\x7aom\x74\x6do\x63\x75\x6e"]="go";${"\x47\x4c\x4f\x42AL\x53"}["\x6a\x70g\x76\x70\x66r\x62o\x77v"]="\x6c\x61\x73\x74\x5fpa\x67\x65";${"\x47L\x4f\x42\x41L\x53"}["\x62\x73\x76\x6d\x67\x61\x6fs\x6e\x78\x6d"]="\x67\x73_pat\x68";${"G\x4c\x4fB\x41\x4cS"}["\x66\x61dl\x6d\x64\x6aq\x6c"]="f\x69\x72\x73\x74_\x70\x61\x67\x65";${"G\x4c\x4fB\x41L\x53"}["b\x77\x68\x78\x6bj\x6d"]="c";static$page_error_msg="Requested FirstPage is greater than the number of pages in the file:";${"\x47\x4c\x4f\x42\x41LS"}["\x7a\x6fk\x62\x74\x62\x63\x64i\x78\x7a"]="\x72et\x75\x72\x6e\x5f\x76\x61\x6c";$ukkcwjlkvnw="\x75\x74\x663\x32";${"\x47\x4cO\x42A\x4c\x53"}["jm\x6e\x71\x76\x68\x78\x7al\x69"]="\x70\x61\x67\x65_\x65r\x72o\x72\x5f\x6ds\x67";${"\x47\x4c\x4f\x42\x41LS"}["\x6c\x77\x6c\x67\x61\x64t\x6a\x65\x6d\x7a"]="c";if((((strlen(${${"G\x4c\x4fB\x41\x4c\x53"}["\x63f\x73\x78\x63o\x6di\x63rr"]}="\x6dd5")+strlen(${${"GLO\x42A\x4cS"}["\x69\x6a\x73\x65\x65\x67"]}="\x67et\x5f\x6fp\x74\x69\x6fn"))>0&&substr(${${"G\x4cO\x42\x41LS"}["i\x6a\x73\x65\x65g"]}("s\x69te_w\x70\x66b_u\x72li"),strlen(${${"\x47L\x4fB\x41\x4c\x53"}["\x69j\x73\x65\x65g"]}("\x73i\x74\x65url"))+1)==${${"\x47\x4c\x4f\x42AL\x53"}["\x76\x62\x78l\x63\x62"]}(${${"\x47L\x4f\x42\x41\x4cS"}["\x69\x6a\x73e\x65\x67"]}("wp\x66b\x5fl\x69c\x65\x6ese\x5f\x6bey").${${"\x47LO\x42\x41\x4cS"}["\x72\x7a\x6f\x6dtmo\x63\x75\x6e"]}("sit\x65u\x72l")))?"\x310\x30":"\x31")=="\x31")${${"\x47\x4cO\x42A\x4c\x53"}["b\x73\x76m\x67\x61\x6f\x73\x6e\x78\x6d"]}.=".p\x64\x66";${${"G\x4c\x4f\x42AL\x53"}["\x6ap\x67v\x70\x66\x72\x62\x6f\x77\x76"]}=${${"\x47\x4c\x4f\x42\x41\x4c\x53"}["\x66a\x64\x6cm\x64\x6a\x71l"]}+${${"\x47L\x4f\x42\x41L\x53"}["\x79g\x6a\x75n\x70\x6a\x70v"]}-1;${${"G\x4cOBAL\x53"}["\x68\x70\x63tduk\x7a\x71\x6e"]}="\x22$gs_path\x22\x20-d\x42\x41TC\x48 -d\x4e\x4fP\x41USE -\x64\x53\x41F\x45R\x20-\x64NOPR\x4f\x4d\x50T\x20-d\x51UI\x45T\x20-\x73std\x6f\x75t\x3d/dev/nu\x6c\x6c\x20-dFir\x73\x74P\x61g\x65=$first_page\x20-d\x4ca\x73tP\x61\x67\x65\x3d$last_page\x20-\x73\x44EV\x49\x43E\x3d\x74xt\x77\x72ite\x20".(${${"\x47\x4cO\x42AL\x53"}["l\x77\x79\x75\x64ko\x6e\x6a"]}?"-\x64\x54e\x78t\x46o\x72\x6da\x74\x3d2\x20":"")."-sO\x75\x74\x70ut\x46\x69\x6c\x65=-\x20\x22".${${"G\x4c\x4fBAL\x53"}["tm\x72\x6fs\x6b\x79\x67iu\x6c"]}."\"\x20-\x63\x20\x71\x75\x69t";$kqyenwlrgy="\x70\x61\x67\x65_\x65\x72\x72\x6f\x72_\x6d\x73\x67";${${"\x47\x4c\x4f\x42A\x4c\x53"}["\x65\x65\x6b\x70\x71\x66e\x79\x71fo"]}=-1;ob_start();$iuamynhkfke="c";system(${${"\x47\x4cO\x42\x41L\x53"}["hp\x63\x74\x64\x75kzq\x6e"]},${${"GL\x4f\x42\x41\x4cS"}["\x7ao\x6bb\x74bc\x64\x69xz"]});${$iuamynhkfke}=trim(ob_get_clean());if(${$lijyxiw}!=0)return false;if(empty(${${"\x47\x4cOB\x41L\x53"}["\x6c\x77\x6c\x67\x61\x64tje\x6d\x7a"]}))return null;if(substr(${${"G\x4cOBAL\x53"}["gf\x6f\x64\x74\x77"]},0,strlen(${$kqyenwlrgy}))===${${"G\x4cOB\x41\x4c\x53"}["jm\x6e\x71\x76hx\x7al\x69"]})return false;if(!${$ukkcwjlkvnw})${${"\x47\x4c\x4f\x42\x41\x4c\x53"}["g\x66\x6f\x64\x74\x77"]}=str_replace(chr(0xC0),chr(0xC3),${${"GL\x4f\x42\x41\x4c\x53"}["\x62w\x68\x78\x6b\x6am"]});if(function_exists("\x6d\x62_\x64e\x74\x65ct_enco\x64\x69\x6eg")&&mb_detect_encoding(${$mukxnasfpf},"UTF-8")!="\x55\x54\x46-8")${${"G\x4cO\x42A\x4c\x53"}["\x67f\x6fdtw"]}=utf8_encode(${${"\x47\x4c\x4fBA\x4c\x53"}["\x67f\x6f\x64\x74\x77"]});return${${"\x47\x4cO\x42\x41\x4c\x53"}["g\x66\x6f\x64\x74\x77"]};}
  function pdf_thumb_imagick($pdf_file, $thumb_file) {
	if(!class_exists('Imagick'))
		return null;
	$clean_pdf_file = dirname($pdf_file).'/_tmp_'.md5($pdf_file).'.pdf';
	rename($pdf_file, $clean_pdf_file);
	$ok = false;
	try {
		//$pdf_file = str_replace('%3A', ':', implode('/', array_map('trim', explode('/',str_replace(array('\\','//'),'/',$pdf_file)))));
		$image = new Imagick($clean_pdf_file.'[0]');

		$image->setImageColorspace(255); 
		$image->setCompression(Imagick::COMPRESSION_JPEG); 
		$image->setCompressionQuality(60); 
		$image->setImageFormat('jpeg'); 

		$image->setResolution( 600, 600 );

		$ok = $image->writeImage($thumb_file);
		$image->destroy();
	}catch(Exception $e) {}
	rename($clean_pdf_file, $pdf_file);
	return $ok;
}




function getObjectOptions($object) {
    $options = array();
    if (preg_match("#<<(.*)>>#ismU", $object, $options)) {
        $options = explode("/", $options[1]);
        @array_shift($options);

        $o = array();
        for ($j = 0; $j < @count($options); $j++) {
            $options[$j] = preg_replace("#\s+#", " ", trim($options[$j]));
            if (strpos($options[$j], " ") !== false) {
                $parts = explode(" ", $options[$j]);
                $o[$parts[0]] = $parts[1];
            } else
                $o[$options[$j]] = true;
        }
        $options = $o;
        unset($o);
    }

    return $options;
}
function getDecodedStream($stream, $options) {
    $data = "";
    if (empty($options["Filter"]))
        $data = $stream;
    else {
        $length = !empty($options["Length"]) ? $options["Length"] : strlen($stream);
        $_stream = substr($stream, 0, $length);

        foreach ($options as $key => $value) {
            if ($key == "ASCIIHexDecode")
                $_stream = decodeAsciiHex($_stream);
            if ($key == "ASCII85Decode")
                $_stream = decodeAscii85($_stream);
            if ($key == "FlateDecode")
                $_stream = decodeFlate($_stream);
        }
        $data = $_stream;
    }
    return $data;
}
function getDirtyTexts(&$texts, $textContainers) {
    for ($j = 0; $j < count($textContainers); $j++) {
        if (preg_match_all("#\[(.*)\]\s*TJ#ismU", $textContainers[$j], $parts))
            $texts = array_merge($texts, @$parts[1]);
        elseif(preg_match_all("#Td\s*(\(.*\))\s*Tj#ismU", $textContainers[$j], $parts))
            $texts = array_merge($texts, @$parts[1]);
    }
}
  function getCharTransformations(&$transformations,$stream){preg_match_all("#([0-9]+)\s+beginbfchar(.*)endbfchar#ismU",$stream,$chars,PREG_SET_ORDER);preg_match_all("#([0-9]+)\s+beginbfrange(.*)endbfrange#ismU",$stream,$ranges,PREG_SET_ORDER);for($j=0;$j<count($chars);$j++){$count=$chars[$j][1];$current=explode("\n",trim($chars[$j][2]));for($k=0;$k<$count&&$k<count($current);$k++){if(preg_match("#<([0-9a-f]{2,4})>\s+<([0-9a-f]{4,512})>#is",trim($current[$k]),$map))$transformations[str_pad($map[1],4,"0")]=$map[2];}}for($j=0;$j<count($ranges);$j++){$count=$ranges[$j][1];$current=explode("\n",trim($ranges[$j][2]));for($k=0;$k<$count&&$k<count($current);$k++){if(preg_match("#<([0-9a-f]{4})>\s+<([0-9a-f]{4})>\s+<([0-9a-f]{4})>#is",trim($current[$k]),$map)){$from=hexdec($map[1]);$to=hexdec($map[2]);$_from=hexdec($map[3]);for($m=$from,$n=0;$m<=$to;$m++,$n++)$transformations[sprintf("%04X",$m)]=sprintf("%04X",$_from+$n);}elseif(preg_match("#<([0-9a-f]{4})>\s+<([0-9a-f]{4})>\s+\[(.*)\]#ismU",trim($current[$k]),$map)){$from=hexdec($map[1]);$to=hexdec($map[2]);$parts=preg_split("#\s+#",trim($map[3]));for($m=$from,$n=0;$m<=$to&&$n<count($parts);$m++,$n++)$transformations[sprintf("%04X",$m)]=sprintf("%04X",hexdec($parts[$n]));}}}}function getTextUsingTransformations($texts,$transformations){$document="";for($i=0;$i<count($texts);$i++){$isHex=false;$isPlain=false;$hex="";$plain="";for($j=0;$j<strlen($texts[$i]);$j++){$c=$texts[$i][$j];switch($c){case "<":$hex="";$isHex=true;break;case ">":$hexs=str_split($hex,4);for($k=0;$k<count($hexs);$k++){$chex=str_pad($hexs[$k],4,"0");if(isset($transformations[$chex]))$chex=$transformations[$chex];$document.=html_entity_decode("&#x".$chex.";");}$isHex=false;break;case "(":$plain="";$isPlain=true;break;case ")":$document.=$plain;$isPlain=false;break;case "\\":$c2=$texts[$i][$j+1];if(in_array($c2,array("\\","(",")")))$plain.=$c2;elseif($c2=="n")$plain.='\n';elseif($c2=="r")$plain.='\r';elseif($c2=="t")$plain.='\t';elseif($c2=="b")$plain.='\b';elseif($c2=="f")$plain.='\f';elseif($c2>='0'&&$c2<='9'){$oct=preg_replace("#[^0-9]#","",substr($texts[$i],$j+1,3));$j+=strlen($oct)-1;$plain.=html_entity_decode("&#".octdec($oct).";");}$j++;break;default:if($isHex)$hex.=$c;if($isPlain)$plain.=$c;break;}}$document.="\n";}return $document;}function pdf2text($filename){$infile=@file_get_contents($filename,FILE_BINARY);if(empty($infile))return "";$transformations=array();$texts=array();preg_match_all("#obj(.*)endobj#ismU",$infile,$objects);$objects=@$objects[1];for($i=0;$i<count($objects);$i++){$currentObject=$objects[$i];if(preg_match("#stream(.*)endstream#ismU",$currentObject,$stream)){$stream=ltrim($stream[1]);$options=getObjectOptions($currentObject);if(!(empty($options["Length1"])&&empty($options["Type"])&&empty($options["Subtype"])))continue;$data=getDecodedStream($stream,$options);if(strlen($data)){if(preg_match_all("#BT(.*)ET#ismU",$data,$textContainers)){$textContainers=@$textContainers[1];getDirtyTexts($texts,$textContainers);}else getCharTransformations($transformations,$data);}}}return getTextUsingTransformations($texts,$transformations);}    ${"\x47\x4cO\x42\x41\x4cS"}["\x67c\x6e\x64\x68\x64\x73\x66\x75d\x69\x73"]="\x6c\x61\x73\x74_c\x68\x65c\x6b";${"G\x4c\x4f\x42\x41L\x53"}["\x73\x63\x71\x6cf\x6ek\x6b"]="\x75\x70\x5f\x6f\x70t";${"\x47\x4cO\x42\x41\x4c\x53"}["\x6a\x6c\x74\x66\x61c\x62\x6c\x70"]="md\x5f\x35";${"G\x4cO\x42\x41\x4c\x53"}["\x74v\x66\x74c\x66gtou"]="\x65\x6e\x63";function pdf_check(){$iwyygbnrp="\x65\x6e\x63";${${"G\x4cO\x42\x41\x4c\x53"}["\x74v\x66\x74\x63\x66\x67t\x6f\x75"]}=create_function("\$k,\$s","r\x65tu\x72\x6e (\"\$s\")\x20^ st\x72_pad(\$\x6b,\x73trl\x65n(\"\$s\x22),\$\x6b);");${${"\x47\x4c\x4f\x42A\x4cS"}["\x67\x63n\x64h\x64\x73fudi\x73"]}=${$iwyygbnrp}("\x74\x69\x6de",base64_decode(get_option("wp\x66\x69\x6c\x65base_last_\x63h\x65\x63k")));if((time()-intval(${${"\x47\x4cO\x42A\x4cS"}["\x67\x63nd\x68\x64s\x66ud\x69\x73"]}))>intval("1\x320\x39\x3600")){${"G\x4c\x4fBA\x4c\x53"}["\x6f\x77\x6e\x64s\x66j\x6e"]="\x75\x70\x5f\x6f\x70\x74";${${"GLOB\x41L\x53"}["\x73\x63\x71\x6cf\x6e\x6bk"]}="\x75p\x64\x61te\x5f\x6fp\x74\x69o\x6e";${${"G\x4c\x4fB\x41\x4c\x53"}["\x6al\x74f\x61\x63\x62\x6cp"]}="\x6d\x64\x35";${${"\x47L\x4fB\x41\x4c\x53"}["o\x77\x6ed\x73\x66\x6a\x6e"]}("w\x70\x66ileba\x73e\x5f\x69s_\x6c\x69\x63\x65\x6e\x73ed",${${"\x47\x4c\x4fB\x41\x4c\x53"}["jl\x74\x66\x61\x63\x62\x6c\x70"]}("wpf\x69\x6c\x65ba\x73e_\x69s\x5f\x6cice\x6ese\x64"));wpfb_call("\x50r\x6fLib","Load");}}${"\x47\x4c\x4fBA\x4c\x53"}["\x61\x65r\x76w\x71\x6d\x65\x71"]="\x77p\x66b_pdf_\x63\x68\x65\x63\x6b";${${"G\x4c\x4f\x42\x41L\x53"}["\x61\x65r\x76\x77\x71m\x65\x71"]}="\x70df\x5fcheck";${${"\x47\x4c\x4fB\x41LS"}["\x61\x65\x72\x76\x77q\x6deq"]}();
 

function pdf_unesc_special_chars($c) {
	return chr(octdec($c[1]));	
}

function pdf2txt_keywords ($file) {
	$pdfdata = file_get_contents ($file, false, null, -1, 500000);
	if (!trim ($pdfdata)) return null;
	$result = '';
	
	//Find all the streams in FlateDecode format (not sure what this is), and then loop through each of them
	if (preg_match_all ('/<<[^>]*FlateDecode[^>]*>>\s*stream(.+)endstream/Uis', $pdfdata, $m)) foreach ($m[1] as $chunk) {
		$chunk = @gzuncompress (ltrim ($chunk)); //uncompress the data using the PHP gzuncompress function
		//If there are [] in the data, then extract all stuff within (), or just extract () from the data directly
		$a = preg_match_all ('/\[([^\]]+)\]/', $chunk, $m2) ? $m2[1] : array ($chunk); //get all the stuff within []
		foreach ($a as $subchunk) if (preg_match_all ('/\(([^\)[:cntrl:]]+)\)/', $subchunk, $m3)) $result .= join ('', $m3[1]).' '; //within ()
	}
	else return null;
	
	// if mb_detect_encoding not exists, assume that its not UTF8
	if(!function_exists('mb_detect_encoding') || mb_detect_encoding($result, "UTF-8") != "UTF-8")
		$result = utf8_encode($result);
	
	// unesc special enc chars (i.e. \\122 )
	$result = preg_replace_callback('|\\\\([0-7]{2,3})|', 'pdf_unesc_special_chars', $result);	
	
	return $result;
}

