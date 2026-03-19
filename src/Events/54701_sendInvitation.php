<?php

/**
 * Created by BORU
 * Team: IN
 * Date: 14/10/22 1:54 PM
 */
chdir(dirname(__FILE__));
require '../init.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, GET, POST');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

$result = ['success' => false];
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false]);
    exit;
}
if (!empty($_REQUEST['list'])) {
    $selectedContact         = $_REQUEST['list'];
    $selectedEmailTemplateId = $_REQUEST['selectedEmail'];
    $mailSubject = $_REQUEST['mailSubject'];
    $mailBody = $_REQUEST['mailBody'];
    $eventid = $_REQUEST['eventid'];
    $result = preg_match_all('/\$(?:[a-zA-Z0-9]+)-(?:[a-zA-Z0-9]+)(?:_[a-zA-Z0-9]+)?(?:_[a-zA-Z0-9]+)?(?::[a-zA-Z0-9]+)?(?:_[a-zA-Z0-9]+)*\$/', $mailBody, $matches);
    $mergeFieldsList = [];
    foreach ($matches[0] as $matche) {
        $matche = str_replace('$', '', $matche);
        $explodeMatch = explode('-', $matche);
        if (strpos($explodeMatch[1], ':') !== false) {
            $referenceField = explode(':', $explodeMatch[1]);
            $mergeFieldsList[$explodeMatch[0]][$referenceField[0]][] = $referenceField[1];
        }
    }

    if (!empty($selectedEmailTemplateId)) {
        global $mail_config;
        unset($selectedContact['selectedEmailTemplate']);
        $from     = $mail_config['mail_from'];
        $dataUser = $vtod->retrieve($vtod->userId);
        date_default_timezone_set($dataUser['time_zone']);

        $eventDataFields = $vtod->retrieve('18x'.$eventid);
        $assigned_user_id = $eventDataFields['assigned_user_id'];
        $userData = $vtod->retrieve($assigned_user_id);
        foreach ($selectedContact as $key => $detail) {
            $contactid     = '4x' . $detail['id'];
            $contactFields = $vtod->retrieve($contactid);
            $to_email      = $contactFields['email'];
            try {
                if (!empty($selectedEmailTemplateId)) {
                    if (empty($mailBody) || empty($mailSubject)) {
                        $query   = "SELECT * FROM EmailTemplates where templatename = '" . $selectedEmailTemplateId . "' LIMIT 1;";
                        $data    = $vtod->query($query);
                        $subject = $data[0]['subject'];
                        $body    = $data[0]['body'];
                    } else {
                        $subject = $mailSubject;
                        $body    = $mailBody;
                    }
                    if (strpos($body, '@@eventid@@') != -1) {
                        $body = str_replace('@@eventid@@', $eventid, $body);
                    }

                    foreach ($contactFields as $fieldName => $fieldVal) {
                        $needle = '$contacts-' . $fieldName . '$';
                        if ($fieldName == 'id') {
                            $fieldName = 'contactid';
                            $fieldVal = $contactFields['firstname'] .' '.$contactFields['lastname'];
                            $needle = '$events-' . $fieldName . '$';
                        }

                        if ($fieldName == 'id') {
                            $fieldVal = ltrim($fieldVal, '4x');
                        }
                        if ($fieldName == 'account_id') {
                            $fieldVal = ltrim($fieldVal, '3x');
                        }

                        $subject = str_replace($needle, $fieldVal, $subject);
                        $body    = str_replace($needle, $fieldVal, $body);

                    }
                    $body    = str_replace('$contacts-id$', ltrim($contactFields['id'], '4x'), $body);

                    foreach ($eventDataFields as $fieldName => $fieldVal) {
                        $needle = '$events-' . $fieldName . '$';

                        if ($fieldName == 'assigned_user_id') {
                            $fieldVal = ltrim($fieldVal, '4x');
                        }
                        if ($fieldName == 'account_id') {
                            $fieldVal = ltrim($fieldVal, '3x');
                        }

                        $subject = str_replace($needle, $fieldVal, $subject);
                        $body    = str_replace($needle, $fieldVal, $body);
                    }

                    if (strpos($body, "$custom-currentyear$") > 0) {
                        $year = date('Y');
                        $body    = str_replace('$custom-currentyear$', $year, $body);
                    }
                    if (strpos($body, "$custom-currentmonth$") > 0) {
                        $month = date('m');
                        $body    = str_replace('$custom-currentmonth$', $month, $body);
                    }
                    if (strpos($body, "$custom-currentdate$") > 0) {
                        $date = date('d');
                        $body    = str_replace('$custom-currentdate$', $date, $body);
                    }

                    foreach ($mergeFieldsList as $module => $mergeFields) {
                        foreach ($mergeFields as $reference => $mergeFieldss) {
                            foreach ($mergeFieldss as $mergeField) {
                                $refereceneedle = "$$module-$reference:$mergeField$";
                                if ($reference == 'contactid') {
                                    $value = $contactFields[$mergeField];
                                } elseif ($reference == 'smownerid') {
                                    $value = $userData[$mergeField];
                                } else {
                                    $value = '';
                                }
                                $body    = str_replace($refereceneedle, $value, $body);
                            }
                        }
                    }
                    $arrEmail['date_start']       = date('Y-m-d H:i:s');
                    $arrEmail['time_start']       = date('H:i:s');
                    $arrEmail['subject']          = urlencode(strip_tags_content($subject));
                    $arrEmail['description']      = urlencode(strip_tags_content($body));
                    $arrEmail['from_email']       = $from;
                    $arrEmail['saved_toid']       = $to_email;
                    $arrEmail['assigned_user_id'] = $vtod->userId;
                    $arrEmail['parent_id']        = $contactid;

                    try {
                        $data_prod = $vtod->create('Emails', $arrEmail);
                        if (!empty($data_prod['id'])) {
                            $data = sendMail($from, '', [$to_email], $subject, $body);
                            if ($data === true) {
                                $result = ['success' => true,'data_prod' => $data_prod];
                            } else {
                                $result = [
                                    'success'   => false,
                                    'error'     => 'Error while Sending Email.',
                                    'errorDesc' => $data,
                                ];
                            }
                        } else {
                            $result = [
                                'success'   => false,
                                'error'     => 'Error while saving record.',
                                'errorDesc' => '',
                            ];
                        }
                    } catch (Exception $e) {
                        $result = [
                            'success'   => false,
                            'error'     => 'Error while Send mail.',
                            'errorDesc' => $e->getMessage(),
                        ];
                    }
                }

            } catch (Exception $e) {
                $result = [
                    'success'   => false,
                    'error'     => 'Error while saving record.',
                    'errorDesc' => $e->getMessage(),
                ];
            }
        }
    }
}

function strip_tags_content($string)
{
    // ----- remove control characters -----
    $string = str_replace('&', 'and', $string);
    $string = str_replace(' ', '-', $string);
    $string = str_replace('-', ' ', $string);
    $string = str_replace("\r", '', $string);
    $string = str_replace("\n", ' ', $string);
    $string = str_replace("\t", ' ', $string);
    $string = htmlspecialchars($string);
    // ----- remove multiple spaces -----
    $string = trim(preg_replace('/ {2,}/', ' ', $string));
    return $string;

}


echo json_encode($result);
exit;
