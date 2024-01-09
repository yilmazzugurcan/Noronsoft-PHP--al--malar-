<?php
include_once("../functions2.php");
include_once("../timeframes.php");
include_once("lang.php");

$simdi = strtotime(date('Y-m-d H:i:s'));


$farki = (date("H") + ($SAATFARKI));
$gerial = mktime( $farki , date("i"), date("s"), date("m"), date("d"), date("Y"));
$tarih = date("Y-m-d",$gerial);
$zaman = date("H:i:s",$gerial);
$suan= convert_datetime("$tarih $zaman");
define('ZAMANTARIH', $suan);

$BAGYAKA_ID=733;
$BAGYAKA_SURE=30;
$BAGYAKA_UCRET_ID=1;

// BİR ARACI VIP YAPMAK İÇİN ARACIN SAHİBİNİN SOYAD KISMINA HERHANGİ BİR YERİNE "VIP" YAZILMALIDIR

//Gelen bilgiler
$arac_Plaka = $_POST['arac_Plaka'];
$kamera_ID = $_POST['kamera_ID'];
$sistem_AD = $_POST['sistem_AD'];
$sistem_Parola = $_POST['sistem_Parola'];
$sebep = $_POST['log_Sebep'];
$resim_yolu = $_POST['resim_Yolu'];
//Debug

/*
$arac_Plaka = "34TEST22";
$kamera_ID = '2';
$sistem_AD = 'sistem';
$sistem_Parola = '123123';
$sorgu_tur = "2";
*/

//Default value- değiştirme
$saatliabonelik = false;
//!Debug
$db = new Db();


function aracBagyakaMi($a_Plaka)
{
	global $BAGYAKA_ID;
	$lastSql = "SELECT * FROM db_araclar WHERE plaka = '$a_Plaka' ";
    $db = new Db();
	$sonbilgiler = $db->select($lastSql);
    if ($sonbilgiler[0]["kullaniciid"]==$BAGYAKA_ID) return 1;
	else return 0;
}

function AracYeniMiCikti($a_Plaka, $cam_id)
{
	$maks_sure = GIRIS_LOG_TIMEOUT_SORGU;
    $lastSql = "SELECT * from `db_loglar` WHERE plaka = '$a_Plaka' order by `kayittarihi` DESC LIMIT 0,1";
    $db = new Db();
    $sonbilgiler = $db->select($lastSql);
    $sayi = count($sonbilgiler);
    //if (empty($maks_sure)) {
    //    $maks_sure = GIRIS_LOG_TIMEOUT_SORGU;
    //}
    if ($sayi > 0) {
        if (ZAMANTARIH - strtotime($sonbilgiler[0]["kayittarihi"]) > $maks_sure) {
			return 0;
        } else {
            if($sonbilgiler[0]["kameraid"]==$cam_id)
				return 0;
			else 
				return 1;
        }
    } else {
        return 0;
    }
}

function AracDahaOnceCiktiMi($a_Plaka,$bugun)
{
	$zamanbas="00:00:00";
	$zamanbit="23:59:59";
	$lastSql="SELECT * from `db_loglar` WHERE plaka = '$a_Plaka' AND kayittarihi>'$bugun $zamanbas' AND kayittarihi<'$bugun $zamanbit' order by `kayittarihi` ASC";
	$db = new Db();
  	
	$lastResult = $db->select($lastSql);
    $sayi = count($lastResult);
	
	
	$dizi_zaman;
	$dizi_arac_yon;
	$sure=0;
	$k=0;
	if ($sayi>0)
	{
		//while($listeid=mysql_fetch_array($lastResult))
		for($k=0;$k<$sayi;$k++)
		{
			$dizi_zaman[$k]=$lastResult[$k]["kayittarihi"];
			$dizi_arac_yon[$k]=$lastResult[$k]["aracKonum"]; //$listeid[aracKonum];
			//++$k;
		}
		for ($i = 0; $i < $k; $i++) 
		{
			if($dizi_arac_yon[$i]=="i" && $dizi_arac_yon[$i+1]=="d") 
			{	
				$sure_arasi=strtotime($dizi_zaman[$i+1]) - strtotime($dizi_zaman[$i]);
				$sure=$sure+$sure_arasi;
			}
		}
		return $sure;
	
	}else return 0;
}


function LatinIngilizce($string)
{
	$en_str="";
	$en_str = $string;
	$en_str = str_replace("Ö","O",$en_str);
	$en_str = str_replace("ö","o",$en_str);
	$en_str = str_replace("Ü","U",$en_str);
	$en_str = str_replace("ü","u",$en_str);
	$en_str = str_replace("İ","I",$en_str);
	$en_str = str_replace("ı","i",$en_str);
	$en_str = str_replace("Ş","S",$en_str);
	$en_str = str_replace("ş","s",$en_str);
	$en_str = str_replace("Ç","C",$en_str);
	$en_str = str_replace("ç","c",$en_str);
	$en_str = str_replace("Ğ","G",$en_str);
	$en_str = str_replace("ğ","g",$en_str);
	return $en_str;
}

function OtoparkDoluGecisKontrol()
{
	$lastSql = "SELECT * from `db_siteayarlari` WHERE ayar = 'dolu_arac_durdur' ";
    $db = new Db();
    $sonbilgiler = $db->select($lastSql);
    return $sonbilgiler[0]["deger"];
}

function GrupIzniVarMi($grupid,$cam_id)
{
	if ($grupid==0) return 0; //1
	$db = new Db();
	$lastSql="SELECT * from `db_musterigruplari` WHERE id = ".$grupid." ";
	
	$sonbilgiler = $db->select($lastSql);
    
	$secilim=explode(',',$sonbilgiler[0]["izinliturler"]); 
	if(in_array($cam_id,$secilim)) return 1;
	else return 0;
	
  	
	return 0; //1
}

function ekrana_bas($girisdurum, $girissebebi, $karalistedurum, $karalistesebep, $plakadurum, $ad, $soyad, $plakam, $ledmesaj=null, $ledmesaj2=null, $abonekalangun=null)
{
		global $lang;
		
		$ad = LatinIngilizce($ad);
		$soyad = LatinIngilizce($soyad);
		
        $bas = "";
		
        $bas1 = "ARAC_GIRIS_DURUM:" . $girisdurum . "\n";
		
		if ($ledmesaj==null) $bas2 = "";
		else $bas2 = "LED_MESSAGE:" . $ledmesaj . "\n";
		
		if ($ledmesaj2==null) $bas3 = "";
		else $bas3 = "LED_MESSAGE2:" . $ledmesaj2 . "\n";
		
		if ($karalistedurum==1) $bas4 = "BLACK_LIST:" . $karalistesebep . "\n";
		else $bas4 = "";
		
		if ($abonekalangun==null) $bas5 = "";
		else $bas5 = "SUBS_DAYS:" . $abonekalangun . "\n";
		
		$bas6 = "<<<\n" . $plakam . "\n";
        $bas7 = "" . $girissebebi . "\n";

        if ($karalistedurum==1) $bas8 = $lang['vcheckphpblacklistreason'] . $karalistesebep . "\n";
		else $bas8 ="";
		
		if ($plakadurum!="") $bas9 = $lang['vcheckphpplatestatus']  . $plakadurum . "\n";
		
        if ($ad!="") $bas10 =  $lang['vcheckphpname'].$ad." ".$lang['vcheckphplastname'].$soyad."\n";
		
        $bas11 = ">>>";
        
		$bas = $bas . $bas1;
		$bas = $bas . $bas2;
        $bas = $bas . $bas3;
        $bas = $bas . $bas4;
		$bas = $bas . $bas5;
        $bas = $bas . $bas6;
		$bas = $bas . $bas7;
		$bas = $bas . $bas8;
		$bas = $bas . $bas9;
		$bas = $bas . $bas10;
		$bas = $bas . $bas11;

        echo $bas;
}

if (sistemkontrol($sistem_AD, $sistem_Parola)) {
    $kam = $db->select("select * from db_kameralar where kamera_ID='$kamera_ID' and sistem_ID='$sistem_AD'")[0];
    //Kamerayı kontrol et;
    if (!$kam) {
        //Kamera yok.
        echo "[RESULT]
e_yok";
    } else {
        //Kamera var.
        $kapi = $kam['kamera_ID'];
        $camgrup = $kam['camgrup'];
        $yon = $kam['kamera_Yon'];
        $kapi_sistem = $kam['sistem_ID'];
		$kam_izin_tipi=$kam['gecisizni'];
        ($yon == 'c') ? $konum = 'i' : $konum = 'd'; //($yon=='c') ? $konum='i' : $konum='d';
        //TEK-Kapı Giriş Çıkış
        //echo "Kamera var";
		//ayni_kapi_giris_cikis
		if(getsetting("ayni_kapi_giris_cikis")==1)
		{
			if (AracYeniMiCikti($arac_Plaka,$kamera_ID )==1)
			{
				ekrana_bas("3",$lang['vcheckphpvehiclenewenterexit'],0,"","","","",$arac_Plaka);	
				exit();
			}
		}
        if ($yon == "c") {
            //Giriş kamerasındayız, aracı içeriye sokalım.
            //addvehicleifnotexists($arac_Plaka);
            if (arackaralistedemi($arac_Plaka)) {
                //TODO: ARAÇ KARA LİSTEDE UYARISI ekrana_bas();
                //echo "Araç kara listede";
				ekrana_bas("3",$lang['vcheckphpvehicleinblacklist'],1,arackaralistesebep($arac_Plaka),"","","",$arac_Plaka);	
				exit();
            } else 
			{
				if (getsetting("otopark_dolu_bos")==1)
				{
					if(iceraracsayisi()>getsetting("maxarac"))
					{
						ekrana_bas("3",$lang['vcheckphpparkisfull'],0,"","","","",$arac_Plaka);	
						exit();
					}
				}
				
				if(arackayittavarmi($arac_Plaka))
				{
					$aracbilgileri=aracbilgisial($arac_Plaka);
					$kul_id=$aracbilgileri[0]["kullaniciid"];
					$aboneliksuresivarmi=0;
					
					$arac_giris_zaman=$aracbilgileri[0]["sondegisim"];
					$arac_gunluk_zaman=$aracbilgileri[0]["soncikis"];
					$giriszamani = strtotime($arac_giris_zaman);
					$cikiszamani = date("Y-m-d", strtotime($arac_gunluk_zaman));
					
					$ledmesaj="";
					$ledmesaj2="";
					if (getsetting("led_mesaj_uyari")==1) {
						if($aracbilgileri[0]["ledmesajaktif"]==1)
						{
							$mesajbittah=$aracbilgileri[0]["ledmesajbitis"];
							$mesajbitzam="23:59:59";
							$mesajabit= convert_datetime("$mesajbittah $mesajbitzam");
							$mesajbit_sure = $mesajabit - $simdi;
							if($mesajbit_sure>0)
							{
								$ledmesaj=$aracbilgileri[0]["ledmesaj"];
								$ledmesaj2=$aracbilgileri[0]["ledmesaj2"];
							}else
							{
								sorguDb("update db_araclar set ledmesajaktif='0' where plaka='$arac_Plaka'");
							}
						}
					}
					
					if($cikiszamani==$tarih)
					{
						ekrana_bas("2",$lang['vcheckphpnopaymentdailyoperation'],0,"","","GUNLUK","ARAC",$arac_Plaka,$ledmesaj,$ledmesaj2);
						exit();
					}
					
					if ($kul_id>0) 
					{
						$kul_bilgileri=kullanicibilgisial($kul_id);
						
						if($kam_izin_tipi==4) //SADECE GRUPLARIN GIREBILECEGI YER ISE, BUNA BAKACAK
						{
							if (!GrupIzniVarMi($kul_bilgileri[0]["grup"],$kam['id']))
							{
								ekrana_bas("3",$lang['vcheckphpnogroupparkingpermission'],0,"","",$kul_bilgileri[0]["kAdi"],$kul_bilgileri[0]["kSoyadi"],$arac_Plaka);
								exit();
							}
						}
						
						$abonelik_bitisi=$aracbilgileri[0]["abonelikbitis"];
						$simdi = ZAMANTARIH;
						$cikan_zaman = strtotime($abonelik_bitisi) - $simdi;
						$cikan_gun = ceil ( $cikan_zaman/60/60/24 );
						if($cikan_gun<0){
							// abonelik bitmiş
						}else {
							$aboneliksuresivarmi=1; //aracin abonelik suresi var
						
						}
						
						if (OtoparkDoluGecisKontrol()==1) 
						{
							ekrana_bas("3",$lang['vcheckphpparkisfullfornotvipvehicles'],0,"","",$kul_bilgileri[0]["kAdi"],$kul_bilgileri[0]["kSoyadi"],$arac_Plaka);
							exit();
						}else if (OtoparkDoluGecisKontrol()==2)
						{
							if (strpos($kul_bilgileri[0]["kSoyadi"], 'VIP' ) === FALSE) {
								ekrana_bas("3",$lang['vcheckphpparkisfullfornotvipvehicles'],0,"","",$kul_bilgileri[0]["kAdi"],$kul_bilgileri[0]["kSoyadi"],$arac_Plaka);
								exit();
							}
						}
					
						if($kam_izin_tipi==3)
						{
							if (strpos($kul_bilgileri[0]["kSoyadi"], 'VIP' ) === FALSE) {
								ekrana_bas("3",$lang['vcheckphpnopermissionfornotvipvehicles'],0,"","",$kul_bilgileri[0]["kAdi"],$kul_bilgileri[0]["kSoyadi"],$arac_Plaka);
								exit();
							}else
							{
								ekrana_bas("2",$lang['vcheckphpvipvehicheenterance'],0,"","",$kul_bilgileri[0]["kAdi"],$kul_bilgileri[0]["kSoyadi"],$arac_Plaka);
								sorguDb("update db_araclar set tipID='1' , odeme_durum=0, barkod='$suan' where plaka='$arac_Plaka'");
								exit();
							}
						}else if($kam_izin_tipi==2)
						{
							if($aboneliksuresivarmi==0) // abonelik bitmiş
							{
								ekrana_bas("3",$lang['vcheckphpnotenterancefornotsubscriptions'],0,"","",$kul_bilgileri[0]["kAdi"],$kul_bilgileri[0]["kSoyadi"],$arac_Plaka);
								exit();
							}else
							{
								//aboneliği var, birşey yapma devam etsin
							}
						}
						
						if ($aboneliksuresivarmi==0)
						{
							ekrana_bas("1",$lang['vcheckphpnosubscriptionentrancewithpayment'],0,"","",$kul_bilgileri[0]["kAdi"],$kul_bilgileri[0]["kSoyadi"],$arac_Plaka,$ledmesaj,$ledmesaj2);
							sorguDb("update db_araclar set tipID='0' , odeme_durum=0, barkod='$suan' where plaka='$arac_Plaka'");
							exit();
						}
						
						$maxarac=$kul_bilgileri[0]["maxarac"];
						if(getsetting("eszamanli")==1 && $maxarac>0)
						{
							$icerdeki_araclar=kullaniciceraracsayisi($kul_id);
							if($aracbilgileri[0]["aracKonum"]=="i" && $aracbilgileri[0]["tipID"]>0) $icerdeki_araclar=$icerdeki_araclar-1; //giris yapacak arac icerde gorunurse; 1 azalt
							
							if ($maxarac>$icerdeki_araclar)
							{
								ekrana_bas("2",$lang['vcheckphpsubsvehiclelimitokremainday'].$cikan_gun,0,"","",$kul_bilgileri[0]["kAdi"],$kul_bilgileri[0]["kSoyadi"],$arac_Plaka,$ledmesaj,$ledmesaj2,$cikan_gun);
								sorguDb("update db_araclar set tipID='1' , odeme_durum=0, barkod='$suan' where plaka='$arac_Plaka'");
								exit();
							}else
							{
								ekrana_bas("2",$lang['vcheckphpsubsvehicleenretancewithpayment'],0,"","",$kul_bilgileri[0]["kAdi"],$kul_bilgileri[0]["kSoyadi"],$arac_Plaka,$ledmesaj,$ledmesaj2);
								sorguDb("update db_araclar set tipID='0' , odeme_durum=0, barkod='$suan' where plaka='$arac_Plaka'");
								exit();
							}
							
						}
						else {
							ekrana_bas("2",$lang['vcheckphpsubsvehicleremainday'].$cikan_gun,0,"","",$kul_bilgileri[0]["kAdi"],$kul_bilgileri[0]["kSoyadi"],$arac_Plaka,$ledmesaj,$ledmesaj2,$cikan_gun);
							sorguDb("update db_araclar set tipID='1' , odeme_durum=0, barkod='$suan' where plaka='$arac_Plaka'");
							exit();
						}
					}
					else 
					{
						if (OtoparkDoluGecisKontrol()==0 || OtoparkDoluGecisKontrol()==3)
						{
							if($kam_izin_tipi==0)
							{
								ekrana_bas("1",$lang['vcheckphpbeforecommingvehicle'],0,"","","","",$arac_Plaka,$ledmesaj,$ledmesaj2);	
							}else
							{
								ekrana_bas("3",$lang['vcheckphpthisvehiclecannotenter'],0,"","","","",$arac_Plaka);	
							}
						}else
						{
							ekrana_bas("3",$lang['vcheckphpparkisfull'],0,"","","","",$arac_Plaka);
						}
						exit();
					}
				}else
				{
					if (OtoparkDoluGecisKontrol()==0 || OtoparkDoluGecisKontrol()==3) // $kam_izin_tipi==3 buna bak
					{
						if($kam_izin_tipi==0)
						{
							ekrana_bas("1",$lang['vcheckphpvehiclefirstenterance'],0,"","","","",$arac_Plaka);	
						}else
						{
							ekrana_bas("3",$lang['vcheckphpthisvehiclecannotenter'],0,"","","","",$arac_Plaka);	
						}
					}
					else
					{
						ekrana_bas("3",$lang['vcheckphpparkisfull'],0,"","","","",$arac_Plaka);
					}
					exit();
				}
                
            }
        } else if ($yon = "g") {
				

            if (arackaralistedemi($arac_Plaka)) {
            	ekrana_bas("3",$lang['vcheckphpvehicleinblacklist'],1,arackaralistesebep($arac_Plaka),"","","",$arac_Plaka);	
				exit();
            } 
			
			if(arackayittavarmi($arac_Plaka))
			{
				$arac_ucreti_var=0;
				if (1) {
					$aracbilgileri=aracbilgisial($arac_Plaka);
					$arac_giris_zaman=$aracbilgileri[0]["sondegisim"];
					$arac_gunluk_zaman=$aracbilgileri[0]["soncikis"];
					$giriszamani = strtotime($arac_giris_zaman);
					$cikiszamani = date("Y-m-d", strtotime($arac_gunluk_zaman));
					
					if($cikiszamani==$tarih)
					{
						ekrana_bas("2",$lang['vcheckphpnopaymentforvehicledailyoperation'],0,"","",$lang['vcheckphpdaily'],$lang['vcheckphpvehicle'],$arac_Plaka);
						exit();
					}
					
					//$msimdi = date('Y-m-d H:i:s');
					$simdi = $suan;
					
					$sure = $simdi - $giriszamani;
					$suredk = round($sure / 60);
					//$suredk=45;bagyaka test

					//tipid direk olarak 1 kabul edilerek işlem yapılacak
					$aractipi = 1;
					
					$fiyat = $db->select("select * from db_fiyatlar WHERE tipID=" . $aractipi . " AND baslangic<=" . $suredk . " AND bitis>=" . $suredk . " ORDER BY id DESC LIMIT 1")[0];

					// if ($aractipi == 3) {
					// 	$aractipi=1;
					// 	$suredk=30;						
					// 	$fiyat = $db->select("select * from db_fiyatlar WHERE tipID=" . $aractipi . " AND baslangic<=" . $suredk . " AND bitis>=" . $suredk . " ORDER BY id DESC LIMIT 1")[0];
					// }

					
					if ($fiyat["carpan"] != 0) {
						$carpan=ceil(($suredk - $fiyat["baslangic"]) / $fiyat["carpan"])*$fiyat["ucret"];
						$ucret = $fiyat["baslangicucreti"] + $carpan;
					} else {
						$ucret =  $fiyat["ucret"];
					}
					
					/*
					if($aracbilgileri[0]["odeme_durum"]==1)
					{
						
					}*/
					
					//ucretsiz_oto_cikis
					if(getsetting("ucretsiz_oto_cikis")>0)
					{
						if($ucret>0)
						{
							$arac_ucreti_var=1;
						}else
						{
							$arac_ucreti_var=0; //ücretsiz çıkış - süreaşımı yok
						}
					}else
					{
						$arac_ucreti_var=1;
					}
				}
				
				$gunluk_limit_asim=0;
				$toplamsure=$suredk+( AracDahaOnceCiktiMi($arac_Plaka,$tarih)/60 );
				if($toplamsure>intval(getsetting("gunlukzamanlimiti"))) 
				{	
					$gunluk_limit_asim=1;
				}
			
				//araç abonelik süresi var mı?
				$aracbilgileri=aracbilgisial($arac_Plaka);
				$kul_id=$aracbilgileri[0]["kullaniciid"];
				$kul_bilgileri=kullanicibilgisial($kul_id);
				
				if ($kul_id>0)  //müşteri ile ilişkili - aboneliği var olabilir.
				{
					$maks_kalma_suresi=intval(getsetting("maxcikis"));  //intval(getsetting("gunlukzamanlimiti"));
					if ($maks_kalma_suresi>0 && $maks_kalma_suresi>=$suredk && $aracbilgileri[0]["aracKonum"]=='d')
					{
						ekrana_bas("2",$lang['vcheckphpnopaymentforvehiclevaleytime'],0,"","",$kul_bilgileri[0]["kAdi"],$kul_bilgileri[0]["kSoyadi"],$arac_Plaka);
						exit();
					}
					if ($maks_kalma_suresi>0 && $maks_kalma_suresi>=$suredk && $aracbilgileri[0]["aracKonum"]=='i' && $aracbilgileri[0]["odeme_durum"]==1 )
					{
						
						ekrana_bas("2",$lang['vcheckphpnopaymentforvehiclepaymentok'],0,"","",$kul_bilgileri[0]["kAdi"],$kul_bilgileri[0]["kSoyadi"],$arac_Plaka);
						sorguDb("update db_araclar set odeme_durum=0 where plaka='$arac_Plaka'");
						exit();
					}
					if(getsetting("eszamanli")==1 && $aracbilgileri[0]["tipID"]==0)
					{
						if($arac_ucreti_var==1)
							ekrana_bas("1",$lang['vcheckphpsubsvehiclesametimepayment'],0,"","",$kul_bilgileri[0]["kAdi"],$kul_bilgileri[0]["kSoyadi"],$arac_Plaka);	
							//ezamanlıya göre abone araç ücret ödeyecek
						else
							ekrana_bas("2",$lang['vcheckphpsubsvehiclenosametimepayment'],0,"","",$kul_bilgileri[0]["kAdi"],$kul_bilgileri[0]["kSoyadi"],$arac_Plaka);
						exit();
					}

					
					
					$abonelik_bitisi=$aracbilgileri[0]["abonelikbitis"];
					$simdi = $suan;
					$cikan_zaman = strtotime($abonelik_bitisi) - $simdi;
					$cikan_gun = ceil ( $cikan_zaman/60/60/24 );
					//$cikan_gun=$SQL_tarih_ekle[0];

					if($cikan_gun<0) // abonelik bitmiş
					{
						
						//aboneligi bitmis arac - asagida ucret hesabı yap ona göre gödner
						if($arac_ucreti_var==1)
						{
							ekrana_bas("1",$lang['vcheckphpfinishsubsvehicletakepayment'],0,"","",$kul_bilgileri[0]["kAdi"],$kul_bilgileri[0]["kSoyadi"],$arac_Plaka);
						}else
						{
							if($gunluk_limit_asim==1)
							{
								ekrana_bas("1",$lang['vcheckphpfinishsubsdailytimeexceed'],0,"","",$kul_bilgileri[0]["kAdi"],$kul_bilgileri[0]["kSoyadi"],$arac_Plaka);
							}else
							{
								ekrana_bas("2",$lang['vcheckphpfinishsubsnopayment'],0,"","",$kul_bilgileri[0]["kAdi"],$kul_bilgileri[0]["kSoyadi"],$arac_Plaka);
							}
						}
					}else
					{
						if(aracBagyakaMi($arac_Plaka)==1)
						{
							if($suredk>$BAGYAKA_SURE)
							{								
								sorguDb("update db_araclar set tipID='".$BAGYAKA_UCRET_ID."' , odeme_durum=0 where plaka='$arac_Plaka'");
								ekrana_bas("1","Bagyaka Arac 30dk Ustu-Ucretli",0,"","",$lang['vcheckphpdaily'],$lang['vcheckphpvehicle'],$arac_Plaka);
								exit();
							}else
							{
								//gonder gitsin
							}
						}
						
						//saatlikabonelik durumuna bakılacak.
						ekrana_bas("2",$lang['vcheckphpsubsvehicleremaindays'].$cikan_gun,0,"","",$kul_bilgileri[0]["kAdi"],$kul_bilgileri[0]["kSoyadi"],$arac_Plaka,"","",$cikan_gun);	
					}
					
					exit();
				}else //bir müşteri ile ilişkilendirilmemiş
				{
					$maks_kalma_suresi=intval(getsetting("maxcikis"));
					if ($maks_kalma_suresi>0 && $maks_kalma_suresi>=$suredk && $aracbilgileri[0]["aracKonum"]=='d')
					{
						ekrana_bas("2",$lang['vcheckphpnovehiclepaymentinvaleytime'],0,"","","","",$arac_Plaka);
						exit();
					}
					if ($maks_kalma_suresi>0 && $maks_kalma_suresi>=$suredk && $aracbilgileri[0]["aracKonum"]=='i' && $aracbilgileri[0]["odeme_durum"]==1 )
					{
						ekrana_bas("2",$lang['vcheckphpnopaymentforvehiclepaymentok'],0,"","","","",$arac_Plaka);
						sorguDb("update db_araclar set odeme_durum=0 where plaka='$arac_Plaka'");
						exit();
					}
					if($arac_ucreti_var==1)
					{							
						ekrana_bas("1",$lang['vcheckphppaymentforvehicle'],0,"","",$kul_bilgileri[0]["kAdi"],$kul_bilgileri[0]["kSoyadi"],$arac_Plaka);
					}
					else
					{
						if($gunluk_limit_asim==1)
						{
							ekrana_bas("1",$lang['vcheckphpdailyfreetimeexceed'],0,"","",$kul_bilgileri[0]["kAdi"],$kul_bilgileri[0]["kSoyadi"],$arac_Plaka);
						}else
						{
							ekrana_bas("2",$lang['vcheckphpnopaymentforvehicle'],0,"","",$kul_bilgileri[0]["kAdi"],$kul_bilgileri[0]["kSoyadi"],$arac_Plaka);
						}
					}
					
					exit();
				}
				
			}else
			{
				ekrana_bas("3",$lang['vcheckphpnovehiclerecord'],0,"","","","",$arac_Plaka);	
				exit();
			}
			
        }

    }

} else {
    echo '[RESULT]
access denied';
}
