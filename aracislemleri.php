<?php
session_start();
include_once "functions.php";
?>
<html lang="tr">
<!--<![endif]-->
<!-- BEGIN HEAD -->
<head>
    <meta charset="utf-8"/>
    <title><?=getsetting("baslik");?></title>
    <?php
include "p-head.php";
?>
    <style>
        body {
            background-color: transparent;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>
<body>


<!--
    //debug için izinleri geri döndürmesinin testi
    <?php
$aracid = 45;
$izinler = getExistingPermissionIds($aracid);

// Dönen değeri ekrana yazdır
echo "Araç ID: $aracid\n";
echo "İzinler: " . implode(', ', $izinler) . "\n";
?>
-->



<?php
$id = $_GET["musteriid"];
$aracid = $_GET["aracid"];
$db = new Db();
$arac = $db->select("select * from db_araclar WHERE id=" . $aracid)[0];
$simdi = strtotime(date('Y-m-d H:i:s'));
$bitis = strtotime($arac["abonelikbitis"]);

$tur = $_GET["type"];

if (!empty($_GET['action']) && $_GET['action'] == "aracsil") {
    $aracplakasi = $db->select("select * from db_araclar where id=" . $aracid)[0]["aracPlaka"];
    $sql = "DELETE FROM db_araclar WHERE id=" . $aracid;
    $db->query($sql);
    logaction($aracplakasi . " " . $lang['vehopidvehiclecarddeleted']);
    echo $lang['vehopvehicledeleted'];
    echo '<script>parent.location.href=parent.location.href</script>';
    exit();
} else if (!empty($_GET['action']) && $_GET['action'] == "aracguncelle") {

    $selected_permission_ids = []; //secilen idleri tutan array. javascript içerisinden geliyor
    if (isset($_GET['selected_permission_ids'])) {
        $selected_permission_ids = explode(',', $_GET['selected_permission_ids']);
        sort($selected_permission_ids); //secilen idleri kucukten buyuge sıralar
    }

    //idleri diziye dönüştürür.
    $permission_ids_string = implode(',', $selected_permission_ids);
    $permission_ids_string = ',' . $permission_ids_string . ',';

    $sql = "UPDATE db_araclar SET";

    $sql = addOperatorIfNotEmptyNoWhere($sql, "aracPlaka", $db->escapethis($_GET['plaka']), "=");
    $sql = addOperatorIfNotEmptyNoWhere($sql, "aracTuru", $db->escapethis($_GET['tipID']), "=");
    $sql = addOperatorIfNotEmptyNoWhere($sql, "aracSahibiKullanici", $db->escapethis($_GET['kullaniciid']), "=");
    $sql = addOperatorIfNotEmptyNoWhere($sql, "notbilgi", $db->escapethis($_GET['notbilgi']), "=");
    $sql = addOperatorIfNotEmptyNoWhere($sql, "tip", $db->escapethis($_GET['tip']), "=");
    $sql = addOperatorIfNotEmptyNoWhere($sql, "bitistarihi", localdatetomysql($_GET['bitistarihi']) . " 00:00:00", "=");
    $sql = addOperatorIfNotEmptyNoWhere($sql, "ledmesajaktif", $db->escapethis($_GET['ledmesajaktif']), "=");
    $sql = addOperatorIfNotEmptyNoWhere($sql, "ledmesaj", $db->escapethis($_GET['ledmesaj']), "=");
    $sql = addOperatorIfNotEmptyNoWhere($sql, "ledmesaj2", $db->escapethis($_GET['ledmesaj2']), "=");
    $sql = addOperatorIfNotEmptyNoWhere($sql, "ledmesajbitis", $db->escapethis($_GET['ledmesajbitis']), "=");
    $sql = addOperatorIfNotEmptyNoWhere($sql, "izinliotoparklar", $permission_ids_string, "=");

    $sql = $sql . " WHERE `db_araclar`.`id` ='" . $aracid . "'";
    debugmsg($sql);
    $db->query($sql);
    logaction($db->escapethis($_GET['plaka']) . " " . $lang['vehopidplatevehicleupdated']);
    if (empty($_GET["optype"]) or $_GET["optype"] != "vale") {
        echo $lang['vehopvehicleupdated'];
        echo '<script>parent.location.href=parent.location.href</script>';

    } else {
        echo $lang['vehopvehicleinfoupdated'] . "<br>";
        echo "<br>";
        echo '<br><a class="btn btn-primary form-control" onclick="parent.location.href=parent.location.href">' . $lang['vehoplistupdated'] . '</a>';

    }
    exit();
} elseif (!empty($_GET['action']) && $_GET['action'] == "add") {
    //TODO: araç pakete eklenecek.
    $plaka = plakatemizle($_GET['plaka']);
    $simdi = date('Y-m-d H:i:s');
    $musteri = musteriAl($_GET["kullaniciid"]);

    if ($db->numrows("select id from db_araclar where aracPlaka='" . $plaka . "'") < 1) {
        if ($db->numrows("select id from db_araclar where aracSahibiKullanici=" . $_GET["kullaniciid"]) < $musteri["maxaracekleme"] || $_GET["tip"] == 'k') {
            if (!empty($plaka)) {

                $selected_permission_ids = [];//secilen idleri tutan array. javascript içerisinden geliyor
                if(isset($_GET['selected_permission_ids'])) {
                    $selected_permission_ids = explode(',', $_GET['selected_permission_ids']);
                    sort($selected_permission_ids);//secilen idleri kucukten buyuge sıralar
                }

                //idleri diziye dönüştürür.
                $permission_ids_string = implode(',', $selected_permission_ids);
                $permission_ids_string = ',' . $permission_ids_string . ',';

                /*
                if (count($selected_permission_ids) == 0) {
                $permission_ids_string = '';
                }*/

                $sql = "INSERT INTO db_araclar (aracPlaka, aracSahibiKullanici, aracTuru,eklenme,sondegisim,bitistarihi,tip,notbilgi,ledmesajaktif,ledmesaj,ledmesaj2,ledmesajbitis,izinliotoparklar) VALUES (";

                $sql .= "'" . thisOrEmpty($db->escapethis($plaka)) . "'";
                $sql .= ",'" . thisOrEmpty($db->escapethis($_GET["kullaniciid"])) . "'";
                $sql .= ",'" . thisOrEmpty($db->escapethis($_GET["tipID"])) . "'";

                $sql .= ",'" . thisOrEmpty($db->escapethis($simdi)) . "'";
                $sql .= ",'" . thisOrEmpty($db->escapethis($simdi)) . "'";
                $sql .= ",'" . thisOrEmpty($db->escapethis(localdatetomysql($_GET['bitistarihi']) . " 00:00:00")) . "'";
                $sql .= ",'" . thisOrEmpty($db->escapethis($_GET["tip"])) . "'";
                $sql .= ",'" . thisOrEmpty($db->escapethis($_GET["notbilgi"])) . "'";
                $sql .= ",'" . thisOrEmpty($db->escapethis($_GET['ledmesajaktif'])) . "'";
                $sql .= ",'" . thisOrEmpty($db->escapethis($_GET['ledmesaj'])) . "'";
                $sql .= ",'" . thisOrEmpty($db->escapethis($_GET['ledmesaj2'])) . "'";
                $sql .= ",'" . thisOrEmpty($db->escapethis($_GET['ledmesajbitis'])) . "'";
                $sql .= ",'" . thisOrEmpty($db->escapethis($permission_ids_string)) . "'";

                $sql = $sql . ")";
                logaction($plaka . " " . $lang['vehopvehiclecardadded']);
                $db->query($sql);
                debugmsg($sql);
                echo $lang['vehopvehicleadded'];

                echo '<script>parent.location.href=parent.location.href</script>';
            } else {
                echo $lang['vehopnotblankplatecardno'];
                exit();
            }

        } else {
            echo $lang['vehopthisplatecardoverlimit'];
        }
    } else {
        echo $lang['vehopthisplatecardrecorded'];
    }
}
?>

<div style="text-align: center;">

    <strong><?php echo $arac["plaka"]; ?></strong>
</div>
<?php
if ($_GET['action'] == "carenterence") {
    $plaka = plakatemizle($_GET['plaka']);

    if (!empty($plaka)) {
        //PLAKAYI ÖNCELİKLE VERİTABANINA EKLİYORUZ.

        addvehicleifnotexists($plaka);

        //PLAKAYI ÖNCELİKLE VERİTABANINA EKLİYORUZ.
        //ELLE GİRİŞ İŞLEMİNİ YAPIYORUZ.
        //t
        $sql = "INSERT INTO db_loglar (plaka, kayittarihi, kameraid, sebep, aracKonum sistemid,aracdurum) VALUES (";
        $sql .= "'" . thisOrEmpty($db->escapethis(plakatemizle($plaka))) . "'";
        $sql .= ",'" . thisOrEmpty($db->escapethis(localdatetomysql($_GET['kayittarihi']) . " " . $_GET['kayitsaati']) . "'");
        $sql .= ",'" . thisOrEmpty($db->escapethis($_GET["kameraid"])) . "'";
        $sql .= ",'" . thisOrEmpty($db->escapethis("Elle giriş yapıldı.")) . "'";
        $sql .= ",'" . thisOrEmpty($db->escapethis("i")) . "'";
        $sql .= ",'" . thisOrEmpty($db->escapethis(kamerabilgileri($_GET["kameraid"]))) . "'";
        $sql .= ",-1";
        $sql = $sql . ")";
        logaction($plaka . " " . $lang['vehopvehicleaddedok']);
        $db->query($sql);
        //Araç ile ilgili son değişiklik tarihini değiştirip barkod ekliyoruz.
        $sql = "UPDATE `db_araclar` SET";
        $sql = addOperatorIfNotEmptyNoWhere($sql, "songiris", $db->escapethis(localdatetomysql($_GET['kayittarihi']) . " " . $_GET['kayitsaati']), "=");
        $sql = addOperatorIfNotEmptyNoWhere($sql, "sondegisim", $db->escapethis(localdatetomysql($_GET['kayittarihi']) . " " . $_GET['kayitsaati']), "=");

        $sql = addOperatorIfNotEmptyNoWhere($sql, "barkod", $db->escapethis(date('ymdHis')), "=");
        $sql = addOperatorIfNotEmptyNoWhere($sql, "aracKonum", "i", "=");
        $sql = $sql . " WHERE `db_araclar`.`plaka` ='" . $plaka . "'";
        $db->query($sql);
        echo $lang['vehopvehiclecardenterancesuccess'];
        logaction($plaka . " " . $lang['vehopplatemanualenterance']);
        $pdfurl = aracgirisbelgesi($plaka);
        echo '<br><a href="' . $pdfurl . '" class="btn btn-info"  style="width:100%">PDF İndir</a>';
        echo '<p></p><a onclick="parent.location.href=\'valearacara.php\';"  class="btn btn-info"  style="width:100%">' . $lang['vehoprefereshlist'] . '</a>';
    } else {
        echo $lang['vehopcannotbeplatenumberblank'];
        exit();
    }
}
?>
<?php

if ($_GET['intent'] == "remove") {
    if (!iznimvarmi("aracsil")) {
        echo $lang['vehopnopermissiontoaddcardplate'];
        exit();
    }
    ?>
    <form method="get" action="aracislemleri.php" style="text-align: center">
        <?=$lang['vehopdelinfo1'];?><br>
        <?=$lang['vehopdelinfo2'];?>
        <input type="hidden" name="aracid" value="<?php echo $aracid; ?>">
        <input type="hidden" name="action" value="aracsil">
        <p></p>
        <input class="btn btn-error btn-lg" type="submit" name="gonder" value="<?=$lang['vehopdel'];?>">
    </form>
<?php } else if ($_GET['intent'] == "edit" || $_GET['intent'] == "add") {
    ?>
    <form method="get" action="aracislemleri.php" style="text-align: center">
        <?php if ($_GET['intent'] == "edit") {?>
        <?=$lang['vehopclient'];?><br>
        <strong><?php
if ($_GET['intent'] == "edit") {
        $musteri = musteriAl($arac["aracSahibiKullanici"]);} else {
        $musteri = musteriAl($_GET["musteriid"]);
    }
        if (is_null($musteri)) {
            echo $lang['vehopnoclient'];
        } else {
            echo $musteri["kAdi"] . " " . $musteri["kSoyadi"];
            echo '<input type="hidden" name="kullaniciid", value="' . $arac['kullaniciid'] . '">';
        }
        ?></strong><br>
<?php }?>
    <?=$lang['vehopplatecardno'];?><br>
            <input name="plaka" class="form-control" value="<?php echo $arac["aracPlaka"]; ?>">


        <?php
if ($_GET["intent"] == "edit") {
        $date = date_create_from_format('Y-m-d H:i:s', $arac["bitistarihi"]); // Your original format
        $nowdate = date_format($date, 'd/m/Y');
    } else {
        $nowdate = "31/12/2099";
    }

    $nowtime = date('H:i');
    ?>
        <?=$lang['vehopenddate'];?><br>
        <div style="" class="input-group date-picker input-daterange"  data-date-format="dd/mm/yyyy" data-date="<?php echo $nowdate; ?>">
            <input type="text" class="form-control" name="bitistarihi" value="<?php echo $nowdate; ?>" readonly="readonly">
            <span><i>gg/aa/yyyy <?=$lang['vehopmustformat'];?></i></span>
        </div>
		<?php
if ($tur == "kart" || $arac["tip"] == "k") {
        echo $lang['vehopcardtypeselect'];
    } else {
        echo $lang['vehopvehicletypeselect'];
    }

    ?>
		<br>
        <select name="tipID" class="form-control">

            <?php
if ($tur == "kart" || $arac["tip"] == "k") {
        $mgliste = $db->select("select id, turadi from db_kartturu");
    } else {
        $mgliste = $db->select("select id, turadi from db_kullanicituru");
    }

    foreach ($mgliste as $m) {
        if ($arac["aracturu"] == $m["id"]) {
            echo '<option value="' . $m["id"] . '" selected="selected">' . $m["turadi"] . "</option>";
        } else {
            echo '<option value="' . $m["id"] . '">' . $m["turadi"] . "</option>";
        }
    }
    ?>
        </select>
        <?php if (!empty($_GET["mid"])) {
        echo '<input type="hidden" name="kullaniciid" value="' . $_GET['mid'] . '">';
    } else {
        echo $lang['vehopclientselect'] . '<br>
        <select name="kullaniciid" class="form-control">
            <option value="0">' . $lang['vehopnoclient'] . '</option>';

        $mgliste = $db->select("select * from db_kullanicilar");
        foreach ($mgliste as $m) {
            if ($arac["aracSahibiKullanici"] == $m["id"]) {
                echo '<option value="' . $m["id"] . '" selected="selected">' . $m["kAdi"] . " " . $m["kSoyadi"] . "</option>";
            } else {
                echo '<option value="' . $m["id"] . '">' . $m["kAdi"] . " " . $m["kSoyadi"] . "</option>";
            }
        }
    }?>
        </select>
        <?php
if (empty($_GET["type"]) or $_GET["type"] != "kart") {
        echo '<input  type="hidden" name="type" value="plaka">';
    } else {
        echo '<input type="hidden" name="type" value="kart">';
    }
    ?>

<?=$lang['vehopnoteinfo'];?><br>
<input type="text" name="notbilgi" value="<?php echo $arac["notbilgi"]; ?>" class="form-control">
<?=$lang['vehoptype'];?><br>
<select name="tip" class="form-control">
    <?php if (isset($arac["tip"])) {?>
        <?php if ($arac["tip"] == "k") {?>
            <option value="k" selected="selected"><?=$lang['vehopcard'];?></option>
        <?php } else {?>
            <option value="p" selected="selected"><?=$lang['vehopplate'];?></option>
        <?php }?>
    <?php } else {?>
        <?php if ($tur == "kart") {?>
            <option value="k" selected="selected"><?=$lang['vehopcard'];?></option>
        <?php } else {?>
            <option value="p" selected="selected"><?=$lang['vehopplate'];?></option>
        <?php }?>
    <?php }?>
</select>
<br>

<style>
        .izin-listesi-container {
            display: flex;
            justify-content: space-between;
            width: 400px;
            margin: 20px auto;
        }

        .izin-grup {
            border: 1px solid #ccc;
            padding: 10px;
            max-height: 250px;
            overflow-y: auto;
            flex: 1;
        }

        .izin-grup label {
            display: block;
            margin-bottom: 5px;
        }

        #secilenIzinler {
            margin-left: 20px;
        }
    </style>


<div class="izin-listesi-container">
    <div class="izin-grup" id="izinGruplari">
        <!-- Sol taraftaki izin grupları burada listelenecek -->
        <?php
$izinliOtoparklar = isset($arac["izinliotoparklar"]) ? explode(',', $arac["izinliotoparklar"]) : array();

    // Grupları Listeletme
    $izinler = getDbGroupNames();
    $izinler = array_combine(range(1, count($izinler)), $izinler);
    foreach ($izinler as $izinID => $izinAdi) {

        $izinAdiGoster = isset($lang[$izinAdi]) ? $lang[$izinAdi] : $izinAdi;
        echo '<label data-izin-id="' . $izinID . '">' . $izinAdi . '</label>';
    }
    ?>
    </div>

    <div class="izin-grup" id="secilenIzinler">
        <!-- Sağ taraftaki seçilen izinler burada listelenecek -->
    </div>

    <input type="hidden" name="selected_permissions" id="selected_permissions" value="">
    <input type="hidden" id="hiddenPreviousSelectedPermissionIds" value="<?=implode(',', getExistingPermissionIds($aracid))?>">

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    var izinGruplari = document.getElementById('izinGruplari');
    var secilenIzinler = document.getElementById('secilenIzinler');
    var selectedPermissionIds = [];

    var previousSelectedPermissionIds = document.getElementById('hiddenPreviousSelectedPermissionIds').value.split(',');

    // Populate the selected permissions on the right side
    previousSelectedPermissionIds.forEach(function (izinID) {
        // Find the corresponding label in the left side
        var izinLabel = izinGruplari.querySelector('label[data-izin-id="' + izinID + '"]');

        if (izinLabel) {
            // Clone the label and append it to the right side
            var clonedLabel = izinLabel.cloneNode(true);
            secilenIzinler.appendChild(clonedLabel);

            // Add the izinID to the selectedPermissionIds array
            selectedPermissionIds.push(izinID);

            // Hide the original label on the left side
            izinLabel.style.display = 'none';
        }
    });

    izinGruplari.addEventListener('click', function (event) {
        if (event.target.tagName === 'LABEL') {
            var izinID = event.target.getAttribute('data-izin-id');
            var izinAdi = event.target.textContent;

            // Sağdaki izinler listesine ekleme
            var label = document.createElement('label');
            label.appendChild(document.createTextNode(izinAdi));
            label.setAttribute('data-izin-id', izinID);

            secilenIzinler.appendChild(label);

            // Seçilen izinlerin ID'lerini saklama
            selectedPermissionIds.push(izinID);

            // Sağdaki izinleri ID'lerine göre sıralama
            sortSecilenIzinler();

            // Seçilen izni sol taraftaki listeden kaldırma
            event.target.style.display = 'none';
        }
    });

    secilenIzinler.addEventListener('click', function (event) {
        if (event.target.tagName === 'LABEL') {
            var izinID = event.target.getAttribute('data-izin-id');
            var izinAdi = event.target.textContent;

            // Sol taraftaki izinler listesine ekleme
            var label = document.createElement('label');
            label.appendChild(document.createTextNode(izinAdi));
            label.setAttribute('data-izin-id', izinID);

            izinGruplari.appendChild(label);

            // Seçilen izinlerin ID'lerini saklama
            var index = selectedPermissionIds.indexOf(izinID);
            if (index > -1) {
                selectedPermissionIds.splice(index, 1);
            }

            // Seçilen izni sağ taraftaki listeden kaldırma
            event.target.style.display = 'none';

            // Sol taraftaki izinleri ID'lerine göre sıralama
            sortIzinGruplari();
        }
    });

    // Formun gönderilmesi sırasında seçilen izinleri eklemek için ek kod
    var form = document.querySelector('form');
    form.addEventListener('submit', function () {
        var selectedPermissions = [];
        var selectedLabels = secilenIzinler.querySelectorAll('label');

        selectedLabels.forEach(function (label) {
            selectedPermissions.push(label.textContent);
        });

        // Seçilen izinleri saklamak için gizli bir input alanı oluşturun
        var hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'selected_permissions';
        hiddenInput.value = selectedPermissions.join(',');

        // Seçilen izinlerin ID'lerini saklamak için başka bir gizli input alanı oluşturun
        var hiddenInputForIds = document.createElement('input');
        hiddenInputForIds.type = 'hidden';
        hiddenInputForIds.name = 'selected_permission_ids';
        hiddenInputForIds.value = selectedPermissionIds.join(',');

        // Gizli inputları forma ekleyin
        form.appendChild(hiddenInput);
        form.appendChild(hiddenInputForIds);
    });

    // Sağdaki izinleri ID'lerine göre sıralayan fonksiyon
    function sortSecilenIzinler() {
        var labels = Array.from(secilenIzinler.getElementsByTagName('label'));
        labels.sort(function (a, b) {
            var idA = a.getAttribute('data-izin-id');
            var idB = b.getAttribute('data-izin-id');
            return idA - idB;

        });
        secilenIzinler.innerHTML = '';
        labels.forEach(function (label) {
            secilenIzinler.appendChild(label);
        });
    }

    // Sol taraftaki izinleri ID'lerine göre sıralayan fonksiyon
    function sortIzinGruplari() {
        var labels = Array.from(izinGruplari.getElementsByTagName('label'));
        labels.sort(function (a, b) {
            var idA = a.getAttribute('data-izin-id');
            var idB = b.getAttribute('data-izin-id');
            return idA - idB;
        });
        izinGruplari.innerHTML = '';
        labels.forEach(function (label) {
            izinGruplari.appendChild(label);
        });
    }

});
</script>

		<br>
		<?php if (getsetting("led_mesaj_uyari") == 1) {?>
		<strong><?php echo $lang["vehopmessagesettings"]; ?></strong><br>
		<input type="checkbox" class="icheck" data-checkbox="icheckbox_square-blue"  <?php if ($arac["ledmesajaktif"]) {echo 'checked="checked"';}?> name="ledmesajaktif" value="1" class="form-control">&nbsp;<?php echo $lang["vehopmessageactive"]; ?>
		<br><?php echo $lang["vehopmessage1"]; ?><br><input name="ledmesaj" class="form-control" value="<?php echo $arac["ledmesaj"]; ?>">
		<br><?php echo $lang["vehopmessage2"]; ?><br><input name="ledmesaj2" class="form-control" value="<?php echo $arac["ledmesaj2"]; ?>">
		<br>
		<strong><?php echo $lang["vehopmessageexpiredate"]; ?></strong><br>
			<div class="date-picker date input-daterange" style="text-align: left;" data-date-format="yyyy-mm-dd"  >
			<?php
$messageend = date("Y-m-d", time());
        if (!isset($arac["ledmesajbitis"])) {
            $arac["ledmesajbitis"] = $messageend;
        }

        ?>
				<input type="text" class="form-control" name="ledmesajbitis" value="<?php echo $arac["ledmesajbitis"]; ?>">
		</div>
		<?php }?>

        <input type="hidden" name="aracid" value="<?php echo $aracid; ?>">
        <input type="hidden" name="musteriid" value="<?php echo $id; ?>">
        <?php if ($_GET['intent'] == "edit") {?>
        <input type="hidden" name="action" value="aracguncelle">
            <input type="hidden" name="optype" value="vale">
            <br>
            <input class="btn btn-success" style="width: 100%" type="submit" name="gonder" value="<?=$lang['vehopupdate'];?>">
            <?php
if ($_GET["optype"] == "vale") {
        echo '<p></p><a class="btn btn-danger" style="width: 100%" href="araccikarvale.php?aracid=' . $aracid . '">' . $lang['vehopchagevehicleoutside'] . '</a>';
    }
        ?>
        <?php } else {?>
        <input type="hidden" name="action" value="add">
            <br>
            <input class="btn btn-success" style="width: 100%" type="submit" name="gonder" value="<?=$lang['vehopplateadd'];?>">
        <?php }?>
        <p></p>

    </form>
<?php
}

?>
<?php
$nobranding = true;
include "p-footer.php";
?>

</body>

</html>
