<?php
session_start();

// НАСТРОЙКИ MONADLEAD.COM
define( 'API_KEY', '4ac9ece63bb9acc4fa774d327384fb1c' ); // Значение api_key со страницы оффера - вкладка API
define( 'OFFER_ID', '899' ); // ID оффера
define( 'COUNTRY_CODE', 'RO' ); // Код страны - если не передан из формы
define( 'BASE_URL', 'https://m-trackpad.com/t/?h=MjAyNHx8NTY1fHw5NmJkY2M5NWU2YWQxMjNhODFiZWU1Mjc1YTU4NGI0NQ==&lp=1118&prelp=-1&sub_1=%s&sub_2=%s' ); // Ссылка на лендинг

define( 'LOG_FILE', md5(OFFER_ID) ); // Имя файла лога
define( 'SUCCESS_PAGE', 'ths.php' ); // Страница спасибо
define( 'ERROR_PAGE', 'ths2.php' ); // Страница ошибки

// НАСТРОЙКИ ПОЛЕЙ ФОРМЫ
define( 'NAME_FIELD', 'name' ); // Название поля формы содержащего Имя
define( 'PHONE_FIELD', 'phone' ); // Название поля формы содержащего Телефон
define( 'SUBID_FIELD', 'subid' ); // Название поля формы содержащего subid
define( 'PIXEL_FIELD', 'px' ); // Название поля формы содержащего пиксель
define( 'BAYER_FIELD', 'bayer' ); // Название поля формы содержащего байера
define( 'COUNTRY_FIELD', 'geo' ); // Название поля формы содержащего код гео

// НАСТРОЙКИ SALES RENDER
define( 'SALESRENDER_COPMPANY_ID', '551' ); // ID компании в Sales Render
define( 'SALESRENDER_API_KEY', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2RlLmJhY2tlbmQuc2FsZXNyZW5kZXIuY29tLyIsImF1ZCI6IkNQQSIsImp0aSI6ImQ2MDJlMzUyYjNlNDdlMDUwOWE5NGNkZjA0ZmEwZWY3IiwidHlwZSI6IndlYm1hc3Rlcl9hcGkiLCJjaWQiOiI1NTEiLCJyZWYiOnsiYWxpYXMiOiJ3ZWJtYXN0ZXIiLCJpZCI6IjIifX0.eRDdAPst0Y80_i-u5DW8Gjz92oA8bQfFzjrVlZsyWCw' ); // API ключ вебмастера в Sales Render
define( 'SALESRENDER_OFFER_ID', '21' ); // ID оффера в Sales Render

/**
 * Передача лида
 */

$leadData = [
  'country_code' => !empty($_POST[COUNTRY_FIELD]) ? check($_POST[COUNTRY_FIELD]) : COUNTRY_CODE,
  'name' => !empty($_POST[NAME_FIELD]) ? check($_POST[NAME_FIELD]) : '',
  'phone' => !empty($_POST[PHONE_FIELD]) ? check($_POST[PHONE_FIELD]) : '',
  'subid' => !empty($_POST[SUBID_FIELD]) ? check($_POST[SUBID_FIELD]) : '-',
  'bayer' => !empty($_POST[BAYER_FIELD]) ? check($_POST[BAYER_FIELD]) : '-',
  'pixel' => !empty($_POST[PIXEL_FIELD]) ? check($_POST[PIXEL_FIELD]) : '-',
  'ip'    => getUserIP(),
  'useragent' => !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
  'referrer'  => !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'https://google.com',
  'data1' => !empty($_POST['sub1']) ? check($_POST['sub1']) : '',
  'data2' => !empty($_POST['sub2']) ? check($_POST['sub2']) : '',
  'data3' => !empty($_POST['sub3']) ? check($_POST['sub3']) : '',
  'data4' => !empty($_POST['sub4']) ? check($_POST['sub4']) : '',
  'data5' => !empty($_POST['sub5']) ? check($_POST['sub5']) : '',
  'id'    => '',
];

/**
 * Отправка данных лида в лидрок
 */
try {
  $requestParams = [
    'api_key'   => API_KEY,
    'offer_id'  => OFFER_ID,
    'name'      => $leadData['name'],
    'phone'     => $leadData['phone'],
    'country_code'  => $leadData['country_code'],
    'base_url'  => sprintf(BASE_URL, urlencode($leadData['subid']), urlencode($leadData['bayer'])),
    'referrer'  => sprintf(BASE_URL, urlencode($leadData['subid']), urlencode($leadData['bayer'])),
    //'referrer'  => $leadData['referrer'],
    'user_ip'   => $leadData['ip'],
    'sub_1'     => $leadData['subid'],
    'sub_2'     => $leadData['bayer'],
    'px' => $leadData['pixel'],
    'pixel' => $leadData['pixel'],
  ];

  $curl = curl_init();

  curl_setopt_array($curl, [
    CURLOPT_URL => 'https://api.monadlead.com/api/v1/orders/create/',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($requestParams),
    CURLOPT_HTTPHEADER => [
      'Content-Type: application/json',
    ],
  ]);
  $response = curl_exec($curl);
  $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  curl_close($curl);
  $response = json_decode($response);

  logRequests($requestParams, ['code' => $httpCode, 'body' => $response]);



  if ($httpCode !== 200) {
    $errorMessage = 'Something wrong?!';
    if (is_object($response) && isset($response->reason)) {
      $errorMessage = $response->reason;
    } elseif (is_string($response)) {
      $errorMessage = $response;
    }
    throw new \Exception($errorMessage);
  }

  $leadData['id'] = $response->order_id;
} catch (\Throwable $e) {
  logRequests($leadData, ['error' => $e->getMessage(), 'line' => $e->getLine()]);
}

logLeadData($leadData);
if (!empty(SALESRENDER_API_KEY) && !empty(SALESRENDER_COPMPANY_ID) && !empty(SALESRENDER_OFFER_ID)) {
  sendLeadToSR(
    SALESRENDER_API_KEY, SALESRENDER_COPMPANY_ID, SALESRENDER_OFFER_ID,
    $leadData['name'], $leadData['phone'], $leadData['subid'], $leadData['id'],
    $leadData['referrer'], $leadData['ip']
  );
}


if (!empty($leadData['id'])) {
  if (!empty(SUCCESS_PAGE)) {
    include __DIR__ . DIRECTORY_SEPARATOR . SUCCESS_PAGE;
    exit(0);
  }
  echo sprintf(
    "<center><h2>You order placed!</h2></center>
<center><p>%s we contact with you by phone number %s</p></center>",
    $leadData['name'], $leadData['phone']
  );
  exit(0);
} else {
  if (!empty(ERROR_PAGE)) {
    include __DIR__ . DIRECTORY_SEPARATOR . ERROR_PAGE;
    exit(0);
  }
  echo sprintf(
    "<center><h2>Whooops!</h2></center>
<center><p>We cant place your order :(</p><p>Try <a href='%s'>again</a></p></center>",
    $leadData['referrer']
  );
  exit(0);
}

/**
 * Вспомогательные функции
 */

function check($str = '')
{
  if (is_int($str)) {
    $str = (int)$str;
  } else {
    $str = htmlspecialchars($str);
    $str = stripslashes(trim($str));
  }
  return $str;
}

function logRequests($request, $response)
{
  $fileName = __DIR__ . DIRECTORY_SEPARATOR . LOG_FILE . '.log.php';
  if (!file_exists($fileName)) {
    file_put_contents($fileName, '<?php die("404 - Not found"); ?>' . PHP_EOL);
  }
  file_put_contents($fileName, sprintf(
    "[%s]\n\tRequest: %s\n\tResponse: %s\n\n",
    date('Y-m-d H:i:s'),
    json_encode($request),
    json_encode($response)
  ), FILE_APPEND);
}

function logLeadData($leadData) {
  $logFile = __DIR__ . DIRECTORY_SEPARATOR . 'leads.csv.php';

  $fileHandler = null;

  if (file_exists($logFile) === false) {
    file_put_contents($logFile, '<?php die("404 - Not found"); ?>' . PHP_EOL);
    $fileHandler = fopen($logFile, 'a+');
    fputcsv($fileHandler, [
      'Date', 'LeadID', 'Name', 'Phone', 'IpAddress', 'UserAgent', 'SubID', 'Bayer', 'Pixel'
    ]);
  }
  if ($fileHandler === null) {
    $fileHandler = fopen($logFile, 'a+');
  }
  fputcsv($fileHandler, [
    date('Y-m-d H:i:s'),
    !empty($leadData['id']) ? $leadData['id'] : '-',
    $leadData['name'],
    $leadData['phone'],
    $leadData['ip'],
    $leadData['useragent'],
    $leadData['subid'],
    $leadData['bayer'],
    $leadData['pixel'],
  ]);
  fclose($fileHandler);
}

function getUserIP() {
  $ipaddress = '';
  // Check CloudFlare visitor IP
  if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
    $ipaddress = $_SERVER['HTTP_CF_CONNECTING_IP'];
  else if (isset($_SERVER['HTTP_CLIENT_IP']))
    $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
  else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
    $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
  else if(isset($_SERVER['HTTP_X_FORWARDED']))
    $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
  else if(isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
    $ipaddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
  else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
    $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
  else if(isset($_SERVER['HTTP_FORWARDED']))
    $ipaddress = $_SERVER['HTTP_FORWARDED'];
  else if(isset($_SERVER['REMOTE_ADDR']))
    $ipaddress = $_SERVER['REMOTE_ADDR'];
  else
    $ipaddress = '127.0.0.1';
  return $ipaddress;
}

function sendLeadToSR($apiKey, $companyId, $offerId, $name, $phone, $subId, $leadId, $site, $ip)
{
  $salesRenderRequestUri = sprintf('https://de.backend.salesrender.com/companies/%s/CPA', $companyId);

  $requestData = <<<GQL
mutation addLead(\$offerId: ID!, \$externalId: String!, \$subId: String!, \$phone: String!, \$name: String!) {
  leadMutation {
    addLead(
      input: {
        offerId: \$offerId,
        externalId: \$externalId,
        externalTag: \$subId,
        data: {
          phone_1: \$phone,
          humanName_1: {
            firstName: \$name
          }
        },
        source: {
          uri: "%s",
          ip: "%s"
          utm_source: "CPA",
          utm_medium: "Doubling",
          utm_campaign: "SaveLead"
        }
      }
    ) {
      id
    }
  }
}
GQL;

  $requestData = sprintf($requestData, $site, $ip);  // Assuming $site and $ip are defined earlier
  $phone = preg_replace('~[\D]+~', '', $phone);

  $variables = [
    'offerId' => $offerId,
    'externalId' => $leadId,
    'subId' => $subId,
    'phone' => $phone,
    'name' => $name,
  ];

  $requestData = json_encode([
    'operationName' => 'addLead', // Assuming the mutation name is 'addLead'
    'query' => $requestData,
    'variables' => $variables,
  ]);

  $ch = curl_init($salesRenderRequestUri);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $requestData,
    CURLOPT_HTTPHEADER => [
      'Authorization: ' . $apiKey,
      'Content-Type: application/json'
    ],
  ]);

  $result = curl_exec($ch);
  curl_close($ch);
}