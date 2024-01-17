<?php

//setlocale(LC_CTYPE, "tr_TR");
require 'Db.php';
include("inc/lang.php");
include_once("barcode.php");
include_once("pdf.php");


date_default_timezone_set(getsetting("timezone"));

//Kullanıcı girişinini handle eden fonksiyon
function loguserinwithpassword($email, $password)
{
    $db = new Db();
    $email = $db->quote($email);
    $password = $db->quote($password);
    $check = $db->select("select * from db_super_users where username=$email AND password=$password");
    if (!empty($check)) {
        $_SESSION['user'] = $check[0];
        return true;
    } else {
        return false;
    }
}

function showifnotempty($s)
{
    if (!empty($s)) {
        echo $s;
    }
}

function getCamGroups()
{
    $db = new Db();
    $camlist = $db->select("SELECT * FROM db_camgrup ORDER BY grupname");
    return $camlist;
}

function getAllUsers()
{
    $db = new Db();
    $userlist = $db->select("SELECT * FROM db_super_users ORDER BY id");
    return $userlist;
}

function getUserInfo($id)
{
    $db = new Db();
    $userlist = $db->select("SELECT * FROM db_super_users WHERE id=" . $id);
    return $userlist[0];
}

function musteriAl($musteriid)
{
    $db = new Db();
    $musteri = $db->select("SELECT * FROM db_kullanicilar WHERE id=" . $musteriid);
    if (!empty($musteri[0]["id"])) {
        return $musteri[0];
    } else {
        return null;
    }
}

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


function convert_datetime($str)
{

    list($date, $time) = explode(' ', $str);
    list($year, $month, $day) = explode('-', $date);
    list($hour, $minute, $second) = explode(':', $time);
    $timestamp = mktime($hour, $minute, $second, $month, $day, $year);
    return $timestamp;
}

function addLIKEIfNotEmpty($sqlstring, $table, $item)
{
    if (!empty($item)) {
        if (strpos($sqlstring, "WHERE") === false) {
            $sqlstring .= " WHERE";
        }
        if (!endsWith($sqlstring, "WHERE") && !endsWith($sqlstring, "AND") && !endsWith($sqlstring, "OR")) {
            $sqlstring .= " AND";
        }
        $sqlstring .= " " . $table . " LIKE '%" . $item . "%'";
    }
    return $sqlstring;

}

function addEqualIfNotEmpty($sqlstring, $table, $item)
{
    if (!empty($item)) {
        if (strpos($sqlstring, "WHERE") === false) {
            $sqlstring .= " WHERE";
        }
        if (!endsWith($sqlstring, "WHERE") && !endsWith($sqlstring, "AND") && !endsWith($sqlstring, "OR")) {
            $sqlstring .= " AND";
        }
        $sqlstring .= " " . $table . " = '" . $item . "'";
    }
    return $sqlstring;

}

function startsWith($haystack, $needle)
{
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}

function endsWith($haystack, $needle)
{
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
}

function addOperatorIfNotEmpty($sqlstring, $table, $item, $operator, $canbeempty = false, $noquote = false)
{
    if (!empty($item) or $canbeempty == true) {
        if (strpos($sqlstring, "WHERE") === false) {
            $sqlstring .= " WHERE";
        }
        if (!endsWith($sqlstring, "WHERE") && !endsWith($sqlstring, "AND") && !endsWith($sqlstring, "SET") && !endsWith($sqlstring, "OR")) {
            $sqlstring .= " AND";
        }
        if ($noquote) {
            $sqlstring .= " " . $table . " " . $operator . " " . $item . "";
        } else {
            $sqlstring .= " " . $table . " " . $operator . " '" . $item . "'";
        }
    }
    return $sqlstring;
}

function addOperatorIfNotEmptyNoWhere($sqlstring, $table, $item, $operator)
{
    if (!empty($item) || $item==0 || $item=='0') {
        if (!endsWith($sqlstring, "WHERE") && !endsWith($sqlstring, "SET") && !endsWith($sqlstring, "AND") && !endsWith($sqlstring, "OR")) {
            $sqlstring .= " ,";
        }

        $sqlstring .= " " . $table . " " . $operator . " '" . $item . "'";

    }
    return $sqlstring;

}

function thisOrEmpty($item)
{
    if (!empty($item)) {
        return $item;
    } else {
        return "";
    }
}

function logaction($message, $user = "--")
{
    $db = new Db();
    $message = $db->escapethis($message);
    if (!empty($_SESSION['user']['username']) && $user == "--") {
        $user = $_SESSION['user']['username'];
    }
    $user = $db->escapethis($user);
    $simdi = strtotime(date('Y-m-d H:i:s'));
    $mysqldate = date('Y-m-d H:i:s', $simdi);
    $sql = "INSERT INTO db_yonetimlog (olay,eklenme,kullanici) VALUES ('" . $db->escapethis($message) . "','$mysqldate','$user')";
    $logger = $db->query($sql);
    if ($logger) {
        return true;
    } else {
        return false;
    }
}

function getUserGroupPermissions()
{
    $db = new Db();
    $grps = $db->select("DESCRIBE db_kullanicigruplari");
    $i = 1;
    $allgrps = array();
    foreach ($grps as $grp) {
        if ($i > 2) {
            $allgrps[$i - 2] = $grp["Field"];
        }
        $i++;
    }
    return $allgrps;

}

function getDbGroupNames()
{
    //db_gruplar grupisimlerini alıp listeletmek için
    $db = new Db();
    $result = $db->select("SELECT grupname,grupid FROM db_gruplar");

    $groupNames = array();
    foreach ($result as $row) {
        $groupNames[] = $row['grupname'];
    }

    return $groupNames;
}

function getExistingPermissionIds($aracId) {
   //seçili izinleri almak için
     $db = new Db();
     $result = $db->query("SELECT izinliotoparklar FROM db_araclar WHERE id = '$aracId'");
     $row = $result->fetch_assoc();
     return explode(',', $row['izinliotoparklar']);    
}

function iznimvarmi($islem)
{
    if (isset($_SESSION["user"])) {
        $grupid = $_SESSION["user"]["grupid"];
        $db = new Db();
        $i = $db->select("select " . $islem . " from db_kullanicigruplari WHERE id=" . $grupid);


        if ($i[0][$islem] == "1") {
            return true;
        } else {
            return false;
        }
    } else {
        //Giriş yapılmadığı için yok
        return false;
    }
}

function izinsizseyolla($islem)
{
    if (!iznimvarmi($islem)) {
        header("Location: index.php");
    }
}

function altmenuekle($isim, $link, $izin = "ok")
{
    if ($izin != "ok") {
        $izinver = iznimvarmi($izin);
    } else {
        $izinver = true;
    }
    if ($izinver == true) {
        echo '<li class="nav-item  ">
    <a

      href="' . $link . '" class="nav-link ">
                            <span class="title">' . $isim . '</span>
                        </a>
                    </li>';
    }

}

function gorevdemiyim()
{
    $db = new Db();
    $sql = "select * from db_calisma_saatleri WHERE user_id=" . $_SESSION["user"]['id'] . " ORDER BY id DESC";
    $i = $db->select($sql);

    if ($i) {
        if (empty($i[0]["end_time"])) {
            return true;
        }
    } else {
        return false;
    }

}

function odemenoktam()
{
    if (gorevdemiyim()) {
        $db = new Db();
        $sql = "select * from db_calisma_saatleri WHERE user_id=" . $_SESSION["user"]['id'] . " ORDER BY id DESC";
        $i = $db->select($sql);

        if ($i) {
            return $i[0]["pointID"];
        }

    } else {
        return "0";
    }

}

function gorevkiler()
{
    $db = new Db();
    $sql = "select * from db_calisma_saatleri WHERE end_time IS NULL ORDER BY id DESC";
    $i = $db->select($sql);
    return $i;
}

function odemenoktasiaktifmi($pointid)
{
    $db = new Db();
    $sql = "select * from db_calisma_saatleri WHERE pointID = " . $pointid . " AND end_time IS NULL ORDER BY id DESC";
    $i = $db->select($sql);
    if (!empty($i["pointID"])) {
        return true;
    } else {
        return false;
    }
}

function getsetting($setting)
{
    $db = new Db();
    $i = $db->select("select * from db_siteayarlari where ayar=" . $db->quote($setting));
    return $i[0]["deger"];
}

function getAracTipi($tipid)
{
    $db = new Db();
    $i = $db->select("select * from db_aractipleri where tipID=" . $db->quote($tipid));
    return $i[0]["tipAdi"];
}

function plakatemizle($string)
{
    $string=preg_replace("/[^a-zA-Z0-9]+/", "", $string);
    $string = mb_strtoupper(preg_replace('/\s+/', '', $string));

    return $string;
}

function mysqldatetolocal($date)
{
    //g/a/y formati
    return date_format(date_create_from_format('Y-m-d', $date), 'd/m/Y');
}

function mysqldatetimetolocal($date)
{
    //g/a/y formati
    return date_format(date_create_from_format('Y-m-d H:i:s', $date), 'd/m/Y H:i:s');
}

function localdatetomysql($date)
{
    return date_format(date_create_from_format('d/m/Y', $date), 'Y-m-d');
}

function addvehicleifnotexists($plaka, $type = "plaka")
{
    $plaka = plakatemizle($plaka);
    $db = new Db();
    $arac = $db->select("select id from db_araclar WHERE plaka=" . $db->quote($plaka))[0];
    if (!empty($arac["id"])) {
        //echo "Bu araç araçlar tablosunda var.";
        $aracid = $arac["id"];
    } else {
        if ($type == "kart") {
            $db->query("INSERT INTO db_araclar (plaka, eklenme, sondegisim,tur) VALUES (" . $db->quote($plaka) . "," . $db->quote(date('Y-m-d H:i:s')) . "," . $db->quote(date('Y-m-d H:i:s')) . ",'kart')");

        } else {
            $db->query("INSERT INTO db_araclar (plaka, eklenme, sondegisim) VALUES (" . $db->quote($plaka) . "," . $db->quote(date('Y-m-d H:i:s')) . "," . $db->quote(date('Y-m-d H:i:s')) . ")");
        }
        $arac = $db->select("select id from db_araclar WHERE plaka=" . $db->quote($plaka))[0];
        $aracid = $arac["id"];
        logaction($plaka . " plakalı araç sisteme eklendi.");
    }

}

function araccikar($plaka, $not = "", $pdf = false,$sadecedisari=false)
{
    $db = new Db();
    //Barkodu al.
    $arac = $db->select("select * from db_araclar WHERE plaka='" . $plaka . "' ORDER BY id DESC")[0];
    $barkod = $arac["barkod"];
    ;
    $db->query("UPDATE `db_araclar` SET aracKonum = 'd', sondegisim='" . date('Y-m-d H:i:s') . "' WHERE plaka=" . $db->quote(plakatemizle($plaka)));
    $db->query("INSERT INTO 'db_loglar' (plaka, kayittarihi, sebep) VALUES ('" . $plaka . "','" . date('Y-m-d H:i:s') . "','Aracın elle çıkışı yapıldı.')");
    if ($pdf == true) {
        $satir1 = getsetting("belgesatir1");
        $satir2 = getsetting("belgesatir2");
        $satir3 = getsetting("belgesatir3");
        $songiris= new DateTime($arac["songiris"]);
        $songiris2= mysqldatetimetolocal($arac["songiris"]);
        $cikistarihi = new DateTime();
        $cikistarihi2= mysqldatetimetolocal(date('Y-m-d H:i:s'));;

        $fark=$songiris->diff($cikistarihi);
        include_once("ucrethesabi.php");

        $ucret=hesapla(aracbilgileri($arac["id"],"id")["plaka"],$arac["tipID"]);
        if(!$sadecedisari){
            $belge = '<div style="margin:auto;text-align:center;">' . $satir1 . '<br>' . $satir2 . '<br>' . $satir3 . '<p>' ."<strong>Giriş Tarihi:</strong><br>" . $songiris2 . "<br><strong>Çıkış Tarihi:</strong><br>" . $cikistarihi2 . "<br><strong>Kalınan süre:</strong><br>".$fark->days." gün ".$fark->h." saat ".$fark->i." dakika <br><strong>Ücret:</strong><br>".$ucret."TL<br><strong>Plaka/Kart No:</strong><br>".$plaka."<br><strong>Barkod No:</strong><br>" . $barkod . "<br>" . generatePNGbase64($barkod) . "</div>";

        }
        else{
            $belge = '<div style="margin:auto;text-align:center;"> Elle araç çıkarma <p>' ."<strong>Giriş Tarihi:</strong><br>" . $songiris2 . "<br><strong>Çıkış Tarihi:</strong><br>" . $cikistarihi2 . "<br><strong>Kalınan süre:</strong><br>".$fark->days." gün ".$fark->h." saat ".$fark->i." dakika <br><strong>Plaka/Kart No:</strong><br>".$plaka."</div>";

        }

        if (!empty($not)) {
            $belge = $belge . "<div style=\"margin:auto;text-align:center;\"><p><strong>Not:</strong><br>" . $not . "</p></div>";
        }
        createpdf($belge);
        //echo $belge;
    }
}
function abonelikbelgesi($plaka,$aboneliksure,$ucret,$abonelikbitis,$musteriad,$musterisoyad){
    $satir1 = getsetting("belgesatir1");

    $belge = '<div style="margin:auto;text-align:center;">' . $satir1 . '<p>' . "<strong>Plaka/Kart No:</strong><br>" . $plaka . "</p><strong>Abonelik Süresi:</strong><br>".$aboneliksure." gün<br><strong>Abonelik Bitiş Tarihi:</strong><br>".mysqldatetimetolocal($abonelikbitis)."<br><strong>Ücret:</strong><br>".$ucret." TL<br><strong>Müşteri Adı Soyadı:</strong><br>" . $musteriad ." ".$musterisoyad."<br>" ;
    if (!empty($not)) {
        $belge = $belge . "<div style=\"margin:auto;text-align:center;\"><p><strong>Not:</strong><br>" . $not . "</p></div>";
    }
    return createpdf($belge,"save");
}
function aracgirisbelgesi($plaka,$not=""){
    $db = new Db();
    //Barkodu al.
    $arac = $db->select("select * from db_araclar WHERE plaka='" . $plaka . "' ORDER BY id DESC")[0];
    $barkod = $arac["barkod"];


        $satir1 = getsetting("belgesatir1");
        $satir2 = getsetting("belgesatir2");
        $satir3 = getsetting("belgesatir3");
        $giristarihi = mysqldatetimetolocal($arac["songiris"]);

        $belge = '<div style="margin:auto;text-align:center;">' . $satir1 . '<br>' . $satir2 . '<br>' . $satir3 . '<p>' . "Giriş Tarihi:" . $giristarihi . "</p><strong>Plaka: </strong><br>".$arac["plaka"]."<br><strong>Barkod No:</strong> " . $barkod . "<br>" . generatePNGbase64($barkod) . "</div>";
        if (!empty($not)) {
            $belge = $belge . "<div style=\"margin:auto;text-align:center;\"><p><strong>Not:</strong><br>" . $not . "</p></div>";
        }
        return createpdf($belge,"save");
        //echo $belge;
}



function debugmsg($m)
{
    if (DEBUG_MODE == true) {
        echo "<i>Hata ayıklama notu:</i><br>" . $m . "<p></p>";
    }
}

function aracaboneliksonbitis($aracid){
    $db = new Db();


    $sql = "select * from db_araclar WHERE id=".$aracid." ORDER BY abonelikbitis DESC";


    $l = $db->select($sql)[0]["abonelikbitis"];

    return $l;
}
function aracabonelikbilgi($aracid){
    $db = new Db();


    $sql = "select * from db_abonelikler WHERE arac_id=".$aracid." ORDER BY abonelikbitis DESC";


    $l = $db->select($sql)[0];

    return $l;
}
function aracinolduguaraclisteleri($aracid)
{
    $db = new Db();

    $listeler = $db->select("select aracliste_id from db_araclistesiaraclar WHERE arac_id=" . $aracid);

    $listebilgileri = array();
    foreach ($listeler as $liste) {
        $liste2 = $db->select("select * from db_araclisteleri WHERE id=" . $liste["aracliste_id"]);
        $listebilgileri = array_merge($listebilgileri, $liste2);
    }
    return $listebilgileri;
}

function araclistelerininaboneligiensonbiteni($listeler)
{
    $db = new Db();

    $listebilgileri = array();
    $sql = "select * from db_abonelikler WHERE ";
    foreach ($listeler as $liste) {
        if (!endsWith($sql, "WHERE ")) {
            $sql = $sql . " OR ";
        }
        $sql = $sql . "aracliste_id=" . $liste["id"];

    }
    $sql = $sql . " ORDER BY abonelikbitis DESC";
    //echo $sql;
    $l = $db->select($sql)[0];

    return $l;

}


function aracidsinial($plaka)
{
    $db = new Db();
    return $db->select("select id from db_araclar WHERE aracPlaka=" . $db->quote(plakatemizle($plaka)) . " LIMIT 1")[0]["id"];
}
function abonelikturubilgisi($id)
{
    $db = new Db();
    return $db->select("select * from db_abonelikturleri WHERE id=" . $id . " LIMIT 1")[0];
}
function araclistesiidsinial($isim)
{
    $db = new Db();
    return $db->select("select id from db_araclisteleri WHERE isim=" . $db->quote($isim) . " LIMIT 1")[0]["id"];
}

function cihazkameranoal($id)
{
    $db = new Db();
    return $db->select("select kamera_ID from db_cihazlar WHERE id=" . $db->quote($id) . " LIMIT 1")[0]["kamera_ID"];
}

function araclistesiisminial($id)
{
    $db = new Db();
    return $db->select("select isim from db_araclisteleri WHERE id=" . $id)[0]["isim"];
}

function abonelikbitisinial($plaka)
{
    return araclistelerininaboneligiensonbiteni((aracinolduguaraclisteleri(aracidsinial($plaka))))["abonelikbitis"];
}

function sistemkontrol($sistemadi, $sistemparolasi)
{
    $db = new Db();
    $sistem = $db->select("select * from db_sistemler where sistemadi=" . $db->quote($sistemadi) . " AND parola=" . $db->quote($sistemparolasi));
    if (!empty($sistem[0]["sistemadi"])) {
        return true;
    } else {
        return false;
    }

}

function arackaralistedemi($plaka)
{
    $db = new Db();
    $s = $db->select("select id from db_blacklist where aracplaka='" . $plaka . "' LIMIT 1");
    if (count($s) > 0) {
        return true;
    } else {
        return false;
    }
}

function aracbilgileri($id, $sorgula = "plaka")
{
    $db = new Db();
    if ($sorgula == "plaka") {
        $a = $db->select("select * from db_araclar where plaka=" . $db->quote($id) . " LIMIT 1")[0];
    } else {
        $a = $db->select("select * from db_araclar where id=" . $db->quote($id) . " LIMIT 1")[0];
    }
    return $a;
}
function kamerabilgileri($kamera_id)
{
    $db = new Db();

    $a = $db->select("select * from db_kameralar where kamera_ID=". $db->quote($kamera_id) . " LIMIT 1")[0];
    return $a;
}
function fiyatbilgileri($tipid)
{
    $db = new Db();

    $a = $db->select("select * from db_fiyatlar where tipID" . $db->quote($tipid));
    return $a;
}


//Sadece aralıktaki saatleri say
function tariharaligindakisaatler($tarih1, $saat1, $tarih2, $saat2, $araliksaat1, $araliksaat2)
{
    $time1 = new DateTime($saat1);
    $time2 = new DateTime($saat2);
    $date1 = new DateTime($tarih1);
    $date2 = new DateTime($tarih2);

    $start = new DateTime($araliksaat1);
    $end = new DateTime($araliksaat2);

    /*
     $time1 = new DateTime("11:00");
    $time2 = new DateTime("15:00");
    $date1 = new DateTime("2016-01-10");
    $date2 = new DateTime("2016-01-13");

    $start = new DateTime("9:00");
    $end = new DateTime("16:00");
*/
    $maxperday = abs($end->diff($start)->format("%h"));
    $full_day_hours = abs($date2->diff($date1)->format("%a") - 1) * $maxperday;
    $first_day_hours = min($maxperday, max(0, $end->diff($time1)->format("%h")));
    $last_day_hours = min($maxperday, max(0, $time2->diff($start)->format("%h")));

    $hours = $first_day_hours + $full_day_hours + $last_day_hours;
    return $hours;
}
function imgbase64goster($url){
    $imageData = base64_encode(file_get_contents($url));
// Format the image SRC:  data:{mime};base64,{data};
    $src = 'data: '.mime_content_type($url).';base64,'.$imageData;
    return $src;
}
function musterigrubuismi($grupid){
    $db=new Db();
    return $db->select("SELECT * FROM `db_musterigruplari` where id=".$grupid)[0]["isim"];
}
function musterigrubual($grupid){
    $db=new Db();
    return $db->select("SELECT * FROM `db_musterigruplari` where id=".$grupid)[0];
}
function musteriborchesapla($mid){
    $db=new Db();
    return $db->select("SELECT t1.toplamborc- t2.toplamodenen AS kalanborc FROM (SELECT SUM(borc) AS toplamborc FROM db_odemeler WHERE kullaniciid=".$mid." AND odemeid=0) AS t1  JOIN (SELECT SUM(odenen) AS toplamodenen FROM db_odemeler WHERE kullaniciid=".$mid.") AS t2")[0]["kalanborc"];
}
function odenenhesapla($borcid){
    $db=new Db();
    $aractip=$db->select("select aractipi from db_odemeler where id=".$borcid)[0]["aractipi"];

    if($aractip=="0") {

        return $db->select("SELECT SUM(odenen) AS toplamodenen FROM db_odemeler WHERE odemeid=" . $borcid)[0]["toplamodenen"];
    }
    else{

        return $db->select("select odenen from db_odemeler where id=".$borcid)[0]["odenen"];
    }
}
function aracbilgifisi($aracid){
    $db = new Db();
    //Barkodu al.
    $arac = $db->select("select * from db_araclar WHERE id='" . $aracid . "' ORDER BY id DESC")[0];
    $plaka=$arac["plaka"];
    $barkod = $arac["barkod"];
    $belge = '<div style="margin:auto;text-align:center;">'. generatePNGbase64($barkod) . "<br><strong>Barkod No:</strong>" . $barkod . "<br>"."<br><strong>Plaka/Kart No:</strong><br>".$plaka."<br><strong>Araç Tipi:</strong><br>" . getAracTipi($arac["tipID"]) ."<br><strong>Son Giriş:</strong><br>" . $arac["songiris"] . "</div>";
    createpdf($belge);
        //echo $belge;
}
function generateRandomString($length = 5) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

//SİTE
function site_izinal($izinid){
    $db=new Db();
    return $db->select("SELECT * FROM `db_izinler` where id=".$izinid)[0];
}
function site_areabilgi($areaid,$itemid){
    $db=new Db();
    return $db->select("SELECT * FROM `db_area_".$areaid."` where id=".$itemid)[0];
}
function site_aracturu($turid){
    $db=new Db();
    return $db->select("SELECT * FROM db_kullanicituru WHERE id=".$turid)[0];
}
function site_kartturu($turid){
    $db=new Db();
    return $db->select("SELECT * FROM db_kartturu WHERE id=".$turid)[0];
}
function site_otopark($otoparkid){
    $db=new Db();
    return $db->select("SELECT * FROM db_parkkategoriler WHERE kategori_id=".$otoparkid)[0];
}
function evethayirayarekle($isim,$kod){
	global $lang;
    if(!empty($_GET['action'])&&$_GET['action']=="edit")
    {
        $db=new Db();
        $sql="UPDATE `db_siteayarlari` SET deger=".$db->quote($db->escapethis($_GET[$kod]))." WHERE ayar='".$kod."'";

        $db->query($sql);
    }
echo '<div class="form-group">
                                    <label class="control-label col-md-3">'.$isim.'</label>
                                    <div class="col-md-9">
                                        <select name="'.$kod.'" class="form-control">
                                            <option value="1"';
if(getsetting($kod)=="1") echo 'selected="selected"'; echo '>'.$lang['setttingsyes'].'</option>
<option value="0" ';if(getsetting($kod)=="0") echo 'selected="selected"'; echo '>'.$lang['setttingsno'].'</option>

</select>

</div>
</div>';

}
