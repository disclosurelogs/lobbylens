<?php
include "../libs/config.php";
createMySQLlink();
// lobbyists
$row = 0;
$success = 0;
if (($handle = fopen("AnalysisDonor0809.csv", "r")) !== FALSE) {
  while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if ($row != 0) {
            $date = date ("Y-m-d",strtotime(str_replace("/","-",$data[2])));
        $query = "INSERT INTO political_donations (DonorClientNm, RecipientClientNm, DonationDt, AmountPaid)
VALUES ('" . mysql_real_escape_string($data[0]) . "','" . mysql_real_escape_string($data[1]) . "','$date'," . (float)$data[3] . ");";
        
        $result = mysql_query($query);
        if ($result) $success++;
        else echo $query . " failed insert.<br>" . mysql_error();
        }
        $row++;
  }
echo $success;
}
