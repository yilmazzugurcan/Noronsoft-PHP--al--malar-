<?php
include_once("../functions2.php");
include_once("lang.php");

$simdi = strtotime(date('Y-m-d H:i:s'));

 
$farki = (date("H") + ($SAATFARKI));
$gerial = mktime( $farki , date("i"), date("s"), date("m"), date("d"), date("Y"));
$tarih = date("Y-m-d",$gerial);
$zaman = date("H:i:s",$gerial);
$suan= convert_datetime("$tarih $zaman");
define('ZAMANTARIH', $suan);



//Gelen bilgiler
$arac_Plaka = $_POST['arac_Plaka'];
$kamera_ID = $_POST['kamera_ID'];
$sistem_AD = $_POST['sistem_AD'];
$sistem_Parola = $_POST['sistem_Parola'];
$sebep = $_POST['log_Sebep'];
$resim_yolu = $_POST['resim_Yolu'];

/*
$arac_Plaka = "61VF624";
$kamera_ID = '1';
$sistem_AD = 'sistem';
$sistem_Parola = '123123';
//$sorgu_tur = "1";
*/

function permayap($deger) {
$turkce=array("ş","Ş","ı","(",")","'","ü","Ü","ö","Ö","ç","Ç","/","*","?","ş","Ş","ı","ğ","Ğ","İ","ö","Ö","Ç","ç","ü","Ü");
$duzgun=array("s","S","i","","","","u","U","o","O","c","C","-","-","","s","S","i","g","G","I","o","O","C","c","u","U");
$deger=str_replace($turkce,$duzgun,$deger);
return $deger;
}

$sebep=permayap($sebep);

$db = new Db();

function notBilgisiAl($aracplaka)
{
    $db = new Db();
    $aracbilgisi = $db->select("SELECT * FROM db_araclar WHERE aracPlaka='" . $aracplaka . "'");
    if (!empty($aracbilgisi[0]["notbilgi"])) {
        return $aracbilgisi[0]["notbilgi"];
    } else {
        return null;
	}
}
function AracYeniMiCikti($a_Plaka, $cam_id)
{
    $maks_sure=GIRIS_LOG_TIMEOUT_LOG;
    $lastSql = "SELECT * from `db_loglar` WHERE plaka = '$a_Plaka' AND kameraid='$cam_id' order by `kayittarihi` DESC LIMIT 0,1";
    $db = new Db();
    $sonbilgiler = $db->select($lastSql);
    $sayi = count($sonbilgiler);
    //if (empty($maks_sure)) {
    //    $maks_sure = GIRIS_LOG_TIMEOUT_LOG;
    //}
    if ($sayi > 0) {
        $simdi = ZAMANTARIH;
        if ($simdi - strtotime($sonbilgiler[0]["kayittarihi"]) > $maks_sure) {
            return 0;
        } else {
            return 1;
        }
    } else {
        return 0;
    }
}

//143
if (sistemkontrol($sistem_AD, $sistem_Parola)) {

    $kam=$db->select("select * from db_kameralar where kamera_ID='$kamera_ID' and sistem_ID='$sistem_AD'")[0];
    //Kamerayı kontrol et;
    if(!$kam){
        //Kamera yok.
        echo "[RESULT]
e_yok";
    } 
    else{
        //Kamera var.
        $kapi=$kam['kamera_ID'];
        $camgrup=$kam['camgrup'];
        $yon=$kam['kamera_Yon'];
        $kapi_sistem=$kam['sistem_ID'];
        ($yon=='c') ? $konum='i' : $konum='d'; //($yon=='c') ? $konum='i' : $konum='d';
        $aracDurum='e_yok';

		$notBilgisi = notBilgisiAl($arac_Plaka);

		if($yon=='g'){
            $sebep=$sebep.$lang['vlogphpautoexit']."-".	$notBilgisi; //" - OTOMATIK CIKIS";
        }else
		{
			$sebep=$sebep.$lang['vlogphpautoenter']."-". $notBilgisi; //" - OTOMATIK GIRIS";
		}
		$aream1="";
		$aream2="";
		$aream3="";
		$aracdurumum=0;
		$userim=0;
		
		$aracbilgileri=aracbilgisial($arac_Plaka);
		if($aracbilgileri[0]["id"]>0) //araç var
		{
			$aracdurumum=$aracbilgileri[0]["aracTuru"];
			
			$kul_id=$aracbilgileri[0]["aracSahibiKullanici"];
			$userim=$kul_id;
			$kul_bilgileri=kullanicibilgisial($kul_id);
			if($kul_bilgileri[0]["id"]>0) //araç bir kullanıcı ile ilişkili
			{
				$aream1=$kul_bilgileri[0]["karea1"];
				$aream2=$kul_bilgileri[0]["karea2"];
				$aream3=$kul_bilgileri[0]["karea3"];
			}
			
		
						
			$log_ekle="INSERT INTO `db_loglar` (`plaka` ,`kayittarihi` ,`kameraid` ,`sebep` ,`resimyolu`, `kullanici`,  `yerdurum` , `area1` ,	`area2` ,`area3` ,`aracdurum` ,	`aracKonum` ,`soncamgrupid` ,`sistemid`	)
				VALUES ('$arac_Plaka', '$tarih $zaman', '".$kapi."', '$sebep', '$resim_yolu','$userim', '$aracdurumum', '$aream1','$aream2','$aream3','1','$konum','$camgrup','$sistem_AD'
				)";
				
			if (AracYeniMiCikti($arac_Plaka, $kamera_ID)==0) 
			{
				$db->query($log_ekle);

				$db->query("update db_araclar set aracKonum='$konum' where aracPlaka='$arac_Plaka'");
				$db->query("update db_araclar set sondegisim='$tarih $zaman' where aracPlaka='$arac_Plaka'");
				$db->query("update db_araclar set soncamid='$kapi' where aracPlaka='$arac_Plaka'");
				$db->query("update db_araclar set soncamgrupid='$camgrup' where aracPlaka='$arac_Plaka'");
			}
			
			echo '[RESULT]
	OK';
		}else
		{
			if (VEHICLE_COUNTING==1)
			{
				addvehicleifnotexists($arac_Plaka);
				logaction($plaka .$lang['vlogphpvehicleautoadded']);
				$log_ekle="INSERT INTO `db_loglar` (`plaka` ,`kayittarihi` ,`kameraid` ,`sebep` ,`resimyolu`, `kullanici`,`area1` ,	`area2` ,`area3` ,`aracdurum` ,	`aracKonum` ,`soncamgrupid` ,`sistemid`	)
				VALUES ('$arac_Plaka', '$tarih $zaman', '".$kapi."', '$sebep', '$resim_yolu','','','','','1','$konum','$camgrup','$sistem_AD')";
				
				$db->query($log_ekle);

				$db->query("update db_araclar set aracKonum='$konum' where aracPlaka='$arac_Plaka'");
				$db->query("update db_araclar set sondegisim='$tarih $zaman' where aracPlaka='$arac_Plaka'");
				$db->query("update db_araclar set soncamid='$kapi' where aracPlaka='$arac_Plaka'");
				$db->query("update db_araclar set soncamgrupid='$camgrup' where aracPlaka='$arac_Plaka'");
				
				
				echo '[RESULT]
		OK';
			}
			
		}

    }

} else 
{
    echo '[RESULT]
access denied';
}

