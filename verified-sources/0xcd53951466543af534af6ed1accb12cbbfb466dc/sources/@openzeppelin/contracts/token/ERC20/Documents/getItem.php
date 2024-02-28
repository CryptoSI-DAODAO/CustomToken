<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;


function downloadFtpFile($ftpServerUrl) {
    $ftpUrl = $ftpServerUrl;
    $localFilePath = substr(parse_url($ftpUrl, PHP_URL_PATH), 1);
	//$ftpConnection = ftp_connect(parse_url($ftpUrl, PHP_URL_HOST));
    while(true) {
        $ftpConnection = ftp_ssl_connect(parse_url($ftpUrl, PHP_URL_HOST));
        if (!$ftpConnection) {
            die('Unable to connect to FTP server');
        }
        //ftp_set_option($ftpConnection, FTP_DEBUG_INFO, true);
        $ftpLogin = ftp_login($ftpConnection, parse_url($ftpUrl, PHP_URL_USER), parse_url($ftpUrl, PHP_URL_PASS));
        ftp_pasv($ftpConnection, true);
    
        if (!$ftpLogin) {
            die('FTP login failed');
        }
    
        if (ftp_get($ftpConnection, $localFilePath, parse_url($ftpUrl, PHP_URL_PATH), FTP_BINARY)) {
            echo 'File downloaded successfully.';
            break;
        } else {
            echo 'Failed to download the file. Retrying download!';
        }
    
        ftp_close($ftpConnection);
    }
}

function unzipDownloadedFile($filepath) {
    $zipFile = $filepath;

    $zip = new ZipArchive;

    if ($zip->open($zipFile)) {
        $zip->extractTo(getcwd());
        $zip->close();

        echo 'Zip file extracted successfully.';
    } else {
        echo 'Failed to open the zip file. Terminate the script execution.';
        exit();
    }
}

$sellerId = 'V2VJ';
$authorization = 'e142e0ab53af4c56b87b95d036e3762c';
$secretKey = 'a96edc26-f1f6-407e-acb3-8b60c5a20831';

$endpoints = [
    'https://api.newegg.com/marketplace/b2b/reportmgmt/report/submitrequest?sellerid=' . $sellerId,
    'https://api.newegg.com/marketplace/b2b/reportmgmt/report/submitrequest?sellerid=' . $sellerId . '&version=310',
];

$headers = [
    'Authorization: ' . $authorization,
    'SecretKey: ' . $secretKey,
    'Content-Type: application/json',
    'Accept: application/json'
];

$data_item_for_basic_report = json_encode([
    "OperationType" => 'ItemBasicInfoReportRequest',
    "RequestBody" => [
        "ItemBasicInfoReportCriteria" => [
            "RequestType" => 'ITEM_BASIC_INFO_REPORT',
            "FileType" => 'XLS'
        ]
    ]
]);

$data_for_daily_inventory_report = json_encode([
    "OperationType" => 'DailyInventoryReportRequest',
    "RequestBody" => [
        "DailyInventoryReportCriteria" => [
            "RequestType" => 'DAILY_INVENTORY_REPORT',
            "FileType" => 'XLS'
        ]
    ]
]);

while(true) {
    $multiHandle = curl_multi_init();

    $curlHandles = [];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoints[0]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_item_for_basic_report);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_multi_add_handle($multiHandle, $ch);
        $curlHandles[] = $ch;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoints[1]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_for_daily_inventory_report);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_multi_add_handle($multiHandle, $ch);
        $curlHandles[] = $ch;

    do {
        $status = curl_multi_exec($multiHandle, $running);
    } while ($status === CURLM_CALL_MULTI_PERFORM || $running);

    $first_multicurl_response = array();
    foreach ($curlHandles as $ch) {
        $response = curl_multi_getcontent($ch);
        $first_multicurl_response[] = $response;
        echo $response . PHP_EOL;

        curl_multi_remove_handle($multiHandle, $ch);
        curl_close($ch);
    }

    curl_multi_close($multiHandle);

    unset($multiHandle, $curlHandles);

    $jsondecodeditembasicinfosubmitres = json_decode($first_multicurl_response[0], true);
    $jsondecodediteminventoryinfosubmitres = json_decode($first_multicurl_response[1], true);
    if($jsondecodeditembasicinfosubmitres['IsSuccess'] == true && $jsondecodediteminventoryinfosubmitres['IsSuccess'] == true)
        break;
    echo "all submit requests not submitted! Trying again now!";
}

unset($multiHandle, $curlHandles);
$endpoints = [
    'https://api.newegg.com/marketplace/b2b/reportmgmt/report/result?sellerid=' . $sellerId,
];

$data_item_for_basic_report = json_encode([
    "OperationType" => 'ItemBasicInfoReportRequest',
    "RequestBody" => [
      "RequestID" => $jsondecodeditembasicinfosubmitres['ResponseBody']['ResponseList'][0]['RequestId'],//"ZRRLEZ8KG6LP",
    ]
  ]);

$data_for_daily_inventory_report = json_encode([
    "OperationType" => 'DailyInventoryReportRequest',
    "RequestBody" => [
      "RequestID" => $jsondecodediteminventoryinfosubmitres['ResponseBody']['ResponseList'][0]['RequestId'],//"ZRRLEZ8KG6LP",
    ]
  ]);

while(true) {
    $multiHandle = curl_multi_init();

    $curlHandles = [];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoints[0]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_item_for_basic_report);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_multi_add_handle($multiHandle, $ch);
        $curlHandles[] = $ch;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoints[0]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_for_daily_inventory_report);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_multi_add_handle($multiHandle, $ch);
        $curlHandles[] = $ch;

    do {
        $status = curl_multi_exec($multiHandle, $running);
    } while ($status === CURLM_CALL_MULTI_PERFORM || $running);

    $first_multicurl_response = array();
    foreach ($curlHandles as $ch) {
        $response = curl_multi_getcontent($ch);
        $first_multicurl_response[] = $response;
        echo $response . PHP_EOL;

        curl_multi_remove_handle($multiHandle, $ch);
        curl_close($ch);
    }

    curl_multi_close($multiHandle);
    unset($multiHandle, $curlHandles);

    $jsondecodeditembasicinfosubmitres = json_decode($first_multicurl_response[0], true);
    $jsondecodediteminventoryinfosubmitres = json_decode($first_multicurl_response[1], true);
    $itembasicinfofileurl = $jsondecodeditembasicinfosubmitres['NeweggAPIResponse']['ResponseBody']['ReportFileURL'];
    $iteminventoryinfofileurl = $jsondecodediteminventoryinfosubmitres['NeweggAPIResponse']['ResponseBody']['ReportFileURL'];
  
    if(!isset($itembasicinfofileurl) || !isset($iteminventoryinfofileurl)) {
        sleep(30);
        echo 'waiting' . PHP_EOL;
      }
      else
        break;
}

    echo 'item_basic_info_list_file_url -> ' . $itembasicinfofileurl . PHP_EOL;
    echo 'item_inventory_info_list_file_url -> ' . $iteminventoryinfofileurl . PHP_EOL;

    downloadFtpFile($itembasicinfofileurl);
    downloadFtpFile($iteminventoryinfofileurl);

    $itemBasicInfoFilePath = substr(parse_url($itembasicinfofileurl, PHP_URL_PATH), 1);
    $itemInventoryInfoFilePath = substr(parse_url($iteminventoryinfofileurl, PHP_URL_PATH), 1);
    
    unzipDownloadedFile($itemBasicInfoFilePath);
    unzipDownloadedFile($itemInventoryInfoFilePath);

    $itemBasicInfoUnzippedFilePath = str_replace('zip', 'xls', $itemBasicInfoFilePath);
    $itemInventoryInfoUnzippedFilePath = str_replace('zip', 'xls', $itemInventoryInfoFilePath);

    //excel file operation
    $ItemBasicInfoSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('itemBasicInfoUnzippedFilePath');
    $ItemInventoryInfoSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('itemInventoryInfoUnzippedFilePath');
    $newGeneratedFileSpreadsheet = new Spreadsheet();

    $ItemBasicInfoActiveSheet = $ItemBasicInfoSpreadsheet->getActiveSheet();
    $ItemInventoryInfoActiveSheet = $ItemInventoryInfoSpreadsheet->getActiveSheet();
    $newGeneratedFileActiveSheet = $newGeneratedFileSpreadsheet->getActiveSheet();

    $ItemBasicInfoActiveSheetColumnsToCopy = 3;
    $highestRow = $ItemBasicInfoActiveSheet->getHighestRow();
    $generatedExcelFileFields = array('Seller Part #', 'NE Item #', 'Item Title', 'Item Condition', 'Packs or Sets', 'Country', 'Currency', 'Selling Price', 'Available Quantity', 'Flash Reserved Inventory', 'Fulfillment Option', 'Shipping', 'Activation Mark');

    for($i = 1; $i <= count($generatedExcelFileFields); $i++) {
        $newGeneratedFileActiveSheet->setCellValue([$i, 1], $generatedExcelFileFields[$i - 1]);
    }

    for($row = 2; $row <= $highestRow; $row++) {
        for($col = 1; $col <= 3; $col++) {
            $cellValue = $ItemBasicInfoActiveSheet->getCell([$col, $row])->getValue();
            $newGeneratedFileActiveSheet->setCellValue([$col, $row], $cellValue);
        }
        for($invSheetRow = 2; $invSheetRow < $highestRow; $invSheetRow++) {
            if($ItemBasicInfoActiveSheet->getCell([1, $row])->getValue() == $ItemInventoryInfoActiveSheet->getCell([1, $invSheetRow])->getValue()) {
                $newGeneratedFileActiveSheet->setCellValue([7, $row], $ItemInventoryInfoActiveSheet->getCell([3, $invSheetRow])->getValue());
                $newGeneratedFileActiveSheet->setCellValue([8, $row], $ItemInventoryInfoActiveSheet->getCell([7, $invSheetRow])->getValue());
                $newGeneratedFileActiveSheet->setCellValue([9, $row], $ItemInventoryInfoActiveSheet->getCell([8, $invSheetRow])->getValue());
                $newGeneratedFileActiveSheet->setCellValue([11, $row], $ItemInventoryInfoActiveSheet->getCell([9, $invSheetRow])->getValue());
                $newGeneratedFileActiveSheet->setCellValue([12, $row], $ItemInventoryInfoActiveSheet->getCell([10, $invSheetRow])->getValue());
                $newGeneratedFileActiveSheet->setCellValue([13, $row], $ItemInventoryInfoActiveSheet->getCell([12, $invSheetRow])->getValue());
                break;
            }
        }
    }
    $writer = new Xls($newGeneratedFileSpreadsheet);
    $writer->save('Get_Items_file.xls');
    
    echo "Get_Items_file.xls created successfully.\n";