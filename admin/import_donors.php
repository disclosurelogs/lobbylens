<?php

include "../libs/config.php";
 set_time_limit(60);
  $dbConn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT );
$donations = json_decode(getPage('https://api.scraperwiki.com/api/1.0/datastore/sqlite?format=json&name=au-federal-electoral-donations&query=select+*+from+`swdata`&apikey='));
$stmt = $dbConn->prepare('insert into political_donations ("DonationDt","AmountPaid","RecipientClientNm","DonorClientNm") VALUES (:DonationDt,:AmountPaid,:RecipientClientNm,:DonorClientNm)');
foreach ($donations as $donation) {
    $donation = object_to_array($donation);
    $paid = explode(".", $donation["AmountPaid"]);
    $donation["AmountPaid"] = $paid[0];
    $donation["DonorClientNm"] = str_replace("Australasia Ltd (formally called Rothma", "", $donation["DonorClientNm"]);
    $donation["DonorClientNm"] = str_replace(" & ", " and ", $donation["DonorClientNm"]);

    $donation["DonorClientNm"] = cleanseName($donation["DonorClientNm"]);
    if (endsWith($donation["DonorClientNm"], "Australia")) {
        $donation["DonorClientNm"] = trim(str_replace("Australia", "", $donation["DonorClientNm"]));
    }
    if (startsWith($donation["DonorClientNm"], "CEPU")) {
        $donation["DonorClientNm"] = "CEPU";
    }
     if (startsWith($donation["DonorClientNm"], "CFMEU")) {
        $donation["DonorClientNm"] = "CFMEU";
    }
   if (startsWith($donation["DonorClientNm"],  "Electrical Trades Union")) {
        $donation["DonorClientNm"] = "Electrical Trades Union";
    }
    if (startsWith($donation["DonorClientNm"],  "Emily's List")) {
        $donation["DonorClientNm"] = "Emily's List";
    }
    if (startsWith($donation["DonorClientNm"], "Health Services Union")) {
        $donation["DonorClientNm"] = "Health Services Union";
    }
    if (startsWith($donation["DonorClientNm"],"Maritime Union of Australia")) {
        $donation["DonorClientNm"] = "Maritime Union of Australia";
    }
    if ($donation["DonationDt"] == "")
        $donation["DonationDt"] = "1/1/1999 12:00:00 AM";
    if (!strstr($donation["DonationDt"], "AM"))
        $donation["DonationDt"] = "1/1/1970 12:00:00 AM";
    $insertValues = Array("DonationDt" => $donation["DonationDt"], "AmountPaid" => $donation["AmountPaid"], "RecipientClientNm" => $donation["RecipientClientNm"], "DonorClientNm" => $donation["DonorClientNm"]);

    $stmt->execute($insertValues);
    $err = $dbConn->errorInfo();
    if ($err[2] != "" && strpos($err[2], "duplicate key") === false) {
        print_r($donation);
        print_r($err);
    } else {
        echo ".";
        set_time_limit(10);
    }
}
$dbConn->exec("update datasets set \"lastUpdated\" = NOW() where title = 'Annual Financial Disclosure Returns (Political Donations)'");
?>
