<?php

/**
 * Imports horoscopes
 *
 * Command to make imports:
 * 
 * 1. /path/to/public_html/protected/yiic import daily (once a day!)
 * 2. /path/to/public_html/protected/yiic import weekly (once a week!)
 * 
 * (Please do sure that file yiic is allowable to run (chmod +x file))
 * (for first command may be passed additional parameter for horoscope type,
 * for example:
 *     /path/to/public_html/protected/yiic import daily --horo=common
 * )
 * 
 */
class ImportCommand extends CConsoleCommand
{

    public function actionDaily($horo = '')
    {
        if ($horo !== '') {
            $url = Yii::app()->params['horoUrl']['daily'][$horo];
            echo 'Daily horoscope:' . "\n";
            $content = Curl::request($url);
            $bulkReplaceValues = $this->parseForDaily($content, $horo);
            $count = $this->replaceToDbForDaily($bulkReplaceValues);
            echo $horo . "\n";
            echo "\t" . 'Parsed and replaced ' . $count . ' records' . "\n";
        } else {
            $urls = Yii::app()->params['horoUrl']['daily'];
            if (is_array($urls)) {
                echo 'Daily horoscopes:' . "\n";
                foreach ($urls as $type => $url) {
                    $content = Curl::request($url);
                    $bulkReplaceValues = $this->parseForDaily($content, $type);
                    $count = $this->replaceToDbForDaily($bulkReplaceValues);
                    echo $type . "\n";
                    echo "\t" . 'Parsed and replaced ' . $count . ' records' . "\n";
                }
            }
        }
    }

    public function actionWeekly()
    {
        echo 'Weekly horoscope:' . "\n";
        $url = Yii::app()->params['horoUrl']['weekly'];
        $content = Curl::request($url);
        $bulkReplaceValues = $this->parseForWeekly($content);
        $count = $this->replaceToDbForWeekly($bulkReplaceValues);
        echo "\t" . 'Parsed and replaced ' . $count . ' records' . "\n";
    }

    private function parseForDaily($content, $type)
    {
        $result = array();
        $xmlArray = XmlParser::parseXmlAsArray($content, 1);
        if (isset($xmlArray) && is_array($xmlArray) && !empty($xmlArray['horo'])) {
            
            $DateUtil = new DateUtil();
            
            $dateToday = $DateUtil->parseAsYmd($xmlArray['horo']['date_attr']['today']);
            $dateTomorrow = $DateUtil->parseAsYmd($xmlArray['horo']['date_attr']['tomorrow']);
            $dateAfterTomorrow = $DateUtil->parseAsYmd($xmlArray['horo']['date_attr']['tomorrow02']);
            
            foreach ($xmlArray['horo'] as $xmlArrayKey => $xmlArrayItem) {
                // skip date and date_attr items of xml array
                if ($xmlArrayKey !== 'date' && $xmlArrayKey !== 'date_attr') {
                    if (!empty($xmlArrayItem) && is_array($xmlArrayItem)) {
                        // horoscope for today
                        if (isset($xmlArrayItem['today'])) {
                            $result[] = array(
                                'date' => $dateToday,
                                'type' => $type,
                                'sign' => $xmlArrayKey,
                                'text' => $xmlArrayItem['today'],
                            );
                        }
                        // horoscope for tomorrow
                        if (isset($xmlArrayItem['tomorrow'])) {
                            $result[] = array(
                                'date' => $dateTomorrow,
                                'type' => $type,
                                'sign' => $xmlArrayKey,
                                'text' => $xmlArrayItem['tomorrow'],
                            );
                        }
                        // horoscope for after tomorrow
                        if (isset($xmlArrayItem['tomorrow02'])) {
                            $result[] = array(
                                'date' => $dateAfterTomorrow,
                                'type' => $type,
                                'sign' => $xmlArrayKey,
                                'text' => $xmlArrayItem['tomorrow02'],
                            );
                        }
                    }
                }
            }

        }
        return $result;
    }

    private function parseForWeekly($content)
    {
        $result = array();
        $xmlArray = XmlParser::parseXmlAsArray($content, 1);
        if (isset($xmlArray) && is_array($xmlArray) && !empty($xmlArray['horo'])) {

            $dateFromOriginal = trim(mb_substr($xmlArray['horo']['date_attr']['weekly'], 0, mb_strpos($xmlArray['horo']['date_attr']['weekly'], '-')));
            $dateToOriginal = trim(mb_substr($xmlArray['horo']['date_attr']['weekly'], mb_strpos($xmlArray['horo']['date_attr']['weekly'], '-') + 1));

            $DateUtil = new DateUtil();
            $dateFrom = $DateUtil->parseAsYmd($dateFromOriginal);
            $dateTo = $DateUtil->parseAsYmd($dateToOriginal);
            if (date('n', strtotime($dateTo)) == 1 && date('j', strtotime($dateTo)) < 7) {
                $dateTo = date('Y-m-d', strtotime($dateTo . ' +1 year'));
            }

            foreach ($xmlArray['horo'] as $xmlArrayKey => $xmlArrayItem) {

                // skip date and date_attr items of xml array
                if ($xmlArrayKey !== 'date' && $xmlArrayKey !== 'date_attr') {

                    if (!empty($xmlArrayItem) && is_array($xmlArrayItem)) {
                        foreach ($xmlArrayItem as $horoType => $horoValue) {
                            $result[] = array(
                                'date_from' => $dateFrom,
                                'date_to' => $dateTo,
                                'type' => $horoType,
                                'sign' => $xmlArrayKey,
                                'text' => $horoValue,
                            );
                        }
                    }
                }
            }
        }
        return $result;
    }

    private function replaceToDbForDaily($data)
    {
        $result = 0;
        $dataCount = count($data);
        $dataChunkSize = Yii::app()->params['bulkReplaceChunkSize'];
        $connection = Yii::app()->db;
        for ($i = 0; $i < $dataCount; $i += $dataChunkSize) {
            $sqlQueryChunks = array();
            $startIndex = $i;
            $endIndex = (($i + $dataChunkSize) > $dataCount) ? $dataCount : ($i + $dataChunkSize);
            for ($j = $startIndex; $j < $endIndex; $j++) {
                $sqlQueryChunks[] = '(:date_' . $j . ', :type_' . $j . ', :sign_' . $j . ', :text_' . $j . ')';
                $result++;
            }
            $sqlQuery = 'REPLACE INTO `horo_daily`(`date`, `type`, `sign`, `text`) VALUES ' . implode(', ', $sqlQueryChunks);
            $transaction = $connection->beginTransaction();
            try {
                $command = $connection->createCommand($sqlQuery);
                for ($j = $startIndex; $j < $endIndex; $j++) {
                    $command->bindParam(':date_' . $j, $data[$j]['date'], PDO::PARAM_STR);
                    $command->bindParam(':type_' . $j, $data[$j]['type'], PDO::PARAM_STR);
                    $command->bindParam(':sign_' . $j, $data[$j]['sign'], PDO::PARAM_STR);
                    $command->bindParam(':text_' . $j, $data[$j]['text'], PDO::PARAM_STR);
                }
                $command->execute();
                $transaction->commit();
            } catch (Exception $e) {
                $transaction->rollback();
                $result = $result - count($sqlQueryChunks);
            }
        }
        return $result;
    }

    private function replaceToDbForWeekly($data)
    {
        $result = 0;
        $dataCount = count($data);
        $dataChunkSize = Yii::app()->params['bulkReplaceChunkSize'];
        $connection = Yii::app()->db;
        for ($i = 0; $i < $dataCount; $i += $dataChunkSize) {
            $sqlQueryChunks = array();
            $startIndex = $i;
            $endIndex = (($i + $dataChunkSize) > $dataCount) ? $dataCount : ($i + $dataChunkSize);
            for ($j = $startIndex; $j < $endIndex; $j++) {
                $sqlQueryChunks[] = '(:date_from_' . $j . ', :date_to_' . $j . ', :type_' . $j . ', :sign_' . $j . ', :text_' . $j . ')';
                $result++;
            }
            $sqlQuery = 'REPLACE INTO `horo_weekly`(`date_from`, `date_to`, `type`, `sign`, `text`) VALUES ' . implode(', ', $sqlQueryChunks);
            $transaction = $connection->beginTransaction();
            try {
                $command = $connection->createCommand($sqlQuery);
                for ($j = $startIndex; $j < $endIndex; $j++) {
                    $command->bindParam(':date_from_' . $j, $data[$j]['date_from'], PDO::PARAM_STR);
                    $command->bindParam(':date_to_' . $j, $data[$j]['date_to'], PDO::PARAM_STR);
                    $command->bindParam(':type_' . $j, $data[$j]['type'], PDO::PARAM_STR);
                    $command->bindParam(':sign_' . $j, $data[$j]['sign'], PDO::PARAM_STR);
                    $command->bindParam(':text_' . $j, $data[$j]['text'], PDO::PARAM_STR);
                }
                $command->execute();
                $transaction->commit();
            } catch (Exception $e) {
                $transaction->rollback();
                $result = $result - count($sqlQueryChunks);
            }
        }
        return $result;
    }

}
