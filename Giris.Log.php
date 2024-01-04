<?php
include_once("functions.php");
$simdi = strtotime(date('Y-m-d H:i:s'));


$farki = (date("H") + ($SAATFARKI));
$gerial = mktime( $farki , date("i"), date("s"), date("m"), date("d"), date("Y"));
$tarih = date("Y-m-d",$gerial);
$zaman = date("H:i:s",$gerial);
$suan= convert_datetime("$tarih $zaman");
define('ZAMANTARIH', $suan);

// BİR ARACI VIP YAPMAK İÇİN ARACIN SAHİBİNİN SOYAD KISMINA HERHANGİ BİR YERİNE "VIP" YAZILMALIDIR

//Gelen bilgiler

$kisi_AD = $_POST['kisi_AD'];
$kisi_CONF = $_POST['kisi_CONF'];
$kisi_TARIH = $_POST['kisi_TARIH'];
$kisi_RESIM = $_POST['kisi_RESIM'];
$benzer_LISTE = $_POST['benzer_LISTE'];
$benzer_SAYI = $_POST['benzer_SAYI'];

$sonuc_Tipi = $_POST['sonuc_Tipi'];
$kamera_ID = $_POST['kamera_ID'];
$sistem_AD = $_POST['sistem_AD'];
$sistem_Parola = $_POST['sistem_Parola'];

$kisi_YAS = $_POST['kisi_YAS'];
$kisi_CINSIYET = $_POST['kisi_CINSIYET'];

/*
$kisi_AD = "B20230512125815_7";
$kisi_CONF = "80";
$kisi_TARIH = "2018-04-05 15:00:12";
$kisi_RESIM = "20231205105034261_B20230512125815_7_3.jpg";
//$benzer_LISTE = $_POST['benzer_LISTE'];
//$benzer_SAYI = $_POST['benzer_SAYI'];

$sonuc_Tipi='3';
$kamera_ID = '1';
$sistem_AD = 'sistem';
$sistem_Parola = '123123';
*/

$sistem_ID = sistemid ( $sistem_AD );
$kisi_AD_JPG = $kisi_AD . ".jpg";
$db = new Db();

//fotoğraf birlestirme fonksiyonu
function fotobirlestir_ve_kaydet($foto1, $foto2, $kayitDosyaAdi) {

    // Fotoğrafları alır
    $image1 = imagecreatefromjpeg($foto1);
    $image2 = imagecreatefromjpeg($foto2);

    // Birleştirilecek fotoğrafların genişliğini ve yüksekliğini alır
    $genislik1 = imagesx($image1);
    $yukseklik1 = imagesy($image1);

    $genislik2 = imagesx($image2);
    $yukseklik2 = imagesy($image2);

    // boş resim oluşturma
    $birlesikGenislik = $genislik1 + $genislik2;
    $birlesikYukseklik = max($yukseklik1, $yukseklik2);
    $birlesikResim = imagecreatetruecolor($birlesikGenislik, $birlesikYukseklik);

    // İlk resmi yeni resime kopyalayın
    imagecopy($birlesikResim, $image1, 0, 0, 0, 0, $genislik1, $yukseklik1);

    // İkinci resmi yeni resime kopyalayın
    imagecopy($birlesikResim, $image2, $genislik1, 0, 0, 0, $genislik2, $yukseklik2);

    // Birleştirilmiş resmi kaydeder
    imagejpeg($birlesikResim, $kayitDosyaAdi, 100);

    // Belleği temizler
    imagedestroy($image1);
    imagedestroy($image2);
    imagedestroy($birlesikResim);
}

 // resim adresleri
 $foto1 = 'http://78.186.47.23/face/public/uploads/workersphotos/'.$kisi_AD_JPG;
 $foto2 = 'http://78.186.47.23/face/public/uploads/reportsphotos/' . $kisi_RESIM;

 $kayitDosyaAdi = 'C:/xampp/htdocs/face/public/uploads/birlesenresim/'.$kisi_RESIM ;




function SMSGonder($cepno, $kisi_AD_JPG , $kisi_RESIM)
{
	$curl = curl_init();
	$msg= "KARA LIST tespit! Resim: ";
	$msg_content1 = "http://78.186.47.23/face/public/uploads/workersphotos/{$kisi_AD_JPG} ";
	$msg_content2 = "http://78.186.47.23/face/public/uploads/reportsphotos/{$kisi_RESIM} ";
	$msg3="http://78.186.47.23/face/public/uploads/birlesenresim/{$kisi_RESIM} ";
	$msg=$msg.$msg3;

	curl_setopt_array($curl, array(
		CURLOPT_URL => 'http://soap.netgsm.com.tr:8080/Sms_webservis/SMS?wsdl/',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => '<?xml version="1.0"?>
		<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"
					 xmlns:xsd="http://www.w3.org/2001/XMLSchema"
		  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
			<SOAP-ENV:Body>
				<ns3:smsGonder1NV2 xmlns:ns3="http://sms/">
					<username>8503099153</username>
					<password>2430066Aa.</password>
					<header>AKDENZALARM</header>
					<msg>'. $msg . '</msg>
					<gsm>'. $cepno . '</gsm>
					<filter>0</filter>
					<encoding>TR</encoding>
				</ns3:smsGonder1NV2>
			</SOAP-ENV:Body>
		</SOAP-ENV:Envelope>',
		CURLOPT_HTTPHEADER => array(
			'Content-Type: text/xml'
		),
	));
	
	$response = curl_exec($curl);

	curl_close($curl);
	//echo $response;
}

//143
if (sistemkontrol($sistem_AD, $sistem_Parola)) 
{

    $kam = $db->select("select * from cameras where camera_no='$kamera_ID' and system_id='$sistem_ID'")[0];
    //Kamerayı kontrol et;
    if(!$kam){
        //Kamera yok.
        echo "[RESULT]
e_yok";
    } 
    else{
        //Kamera var.
        $zone_id = $kam['zone_id'];
        $geo_location = $kam['geo_location'];
		$permissions_id=$kam['permissions_id'];
		
		$kisi_id=kisilistedemi($kisi_AD_JPG);
		$blacklistreason="";
		if ($kisi_id>0)
		{
			$kisi_bilgileri=kisibilgisial($kisi_id);
			$blacklistreason=$kisi_bilgileri[0]["reason"];
		}
        
		if ($sonuc_Tipi==3) //kara liste
		{
			 fotobirlestir_ve_kaydet($foto1, $foto2, $kayitDosyaAdi);
			$kname=$lang['qryinblacklist'];
		
			if(strlen($kisi_TARIH)<1) $kisi_TARIH="$tarih $zaman";
			
			$log_ekle="INSERT INTO `reports` (`name` ,`surname` ,`subarea1` ,`subarea2` ,`subarea3`, `camera_no`,`system_name` ,`date` ,`image` ,`log_image` ,`conf` ,`age` ,`gender` ,`created_at` ,`updated_at`	)
			VALUES ('".$kname."', '".$blacklistreason."', '', '', '','".$kamera_ID."','$sistem_AD', '$kisi_TARIH','$kisi_AD_JPG','$kisi_RESIM','$kisi_CONF', '$kisi_YAS' , '$kisi_CINSIYET', '$kisi_TARIH','$kisi_TARIH' )";
			
			$db->query($log_ekle);
			
			$telefon_numarasi = telefon_numarasi($kamera_ID);

			

			SMSGonder($telefon_numarasi,$kisi_AD_JPG,$kisi_RESIM);

			
		}else if($sonuc_Tipi==0) //TANIMSIZ
		{
			$kname=$lang['qrynotknown'];
			$kisi_AD_JPG = "default.jpg";
		
			if(strlen($kisi_TARIH)<1) $kisi_TARIH="$tarih $zaman";
			
			$log_ekle="INSERT INTO `reports` (`name` ,`surname` ,`subarea1` ,`subarea2` ,`subarea3`, `camera_no`,`system_name` ,`date` ,`image` ,`log_image` ,`conf` , `age` ,`gender` ,`created_at` ,`updated_at`	)
			VALUES ('".$kname."', '".$kname."', '', '', '','".$kamera_ID."','$sistem_AD', '$kisi_TARIH','$kisi_AD_JPG','$kisi_RESIM','$kisi_CONF', '$kisi_YAS' , '$kisi_CINSIYET', '$kisi_TARIH','$kisi_TARIH' )";
			
			$db->query($log_ekle);
			
		}else if($kisi_id>0)
		{
			$kisi_bilgileri=kisibilgisial($kisi_id);
			if(strlen($kisi_TARIH)<1) $kisi_TARIH="$tarih $zaman";
			$log_ekle="INSERT INTO `reports` (`name` ,`surname` ,`subarea1` ,`subarea2` ,`subarea3`, `camera_no`,`system_name` ,`date` ,`image` ,`log_image` ,`conf` , `age` ,`gender` ,`created_at` ,`updated_at`	)
			VALUES ('".$kisi_bilgileri[0]["name"]."', '".$kisi_bilgileri[0]["surname"]."', '".$kisi_bilgileri[0]["sub_area1s"]."', '".$kisi_bilgileri[0]["sub_area2s"]."', '".$kisi_bilgileri[0]["sub_area3s"]."','".$kamera_ID."','$sistem_AD', '$kisi_TARIH','$kisi_AD_JPG','$kisi_RESIM','$kisi_CONF','$kisi_YAS' , '$kisi_CINSIYET', '$kisi_TARIH','$kisi_TARIH' )";
			
			$db->query($log_ekle);

			$db->query("update workers set lastlogin='$kisi_TARIH' where id='$kisi_id'");
			$db->query("update workers set geo_location='$geo_location' where id='$kisi_id'");
		}	
	
		
		echo '[RESULT]
OK';

    }

} else {
    echo '[RESULT]
access denied';
}


?>