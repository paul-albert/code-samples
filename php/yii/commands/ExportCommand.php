<?php
/**
 * Export some data from one format to other
 *
 * $Id$
 * $HeadURL$
 * @author     pavel.albert
 * @since      2014-07-14
 */
class ExportCommand extends BConsoleCommand
{

    /**
   * Get news data (through bulk select).
   * (Here we use DAO instead of ActiveRecord because it retrieves less memory and is more faster.)
   * ("Bulk" means that we select news in range between minimal and maximal guids.)
   *
   * $Id$
   * $HeadURL$
   * @author     pavel.albert
   * @since      2014-06-24
   *
   * @param      integer        $minGuid        Minimal available guid value.
   * @param      integer        $maxGuid        Maximal available guid value.
   * @param      string         $languages      Comma delimited languges iso codes.
   * @param      string         $typeNews       Comma delimited news type's (Message::TYPE_*).
   * @param      integer        $limit          Limit for bulk SQL-selects.
   *
   * @return     array
   */
  private function getNewsDataForExport($minGuid, $maxGuid, $languages, $typeNews, $limit)
  {
    // prepare news type and languages filters
    $typeNews  = implode("','", Common::toArray(explode(',', $typeNews)));
    $languages = implode("','", Common::toArray(explode(',', $languages)));

    $news      = array();

    // create SQL query for bulk select
    $sqlQuery  = "
      SELECT `t`.`guid`, `t`.`title`, `t`.`text`, `t`.`short_text`, `t`.`created_at`, `feedCategory`.`name`, `feedCategory`.`id`, `article`.`message_id`, `article`.`attr`
      FROM `message` `t`
      LEFT OUTER JOIN `category` `feedCategory` ON (`t`.`feed_category_id`=`feedCategory`.`id`)
      LEFT OUTER JOIN `article` `article` ON (`article`.`message_id`=`t`.`guid`)
      WHERE ((((t.is_deleted = 0) AND (type IN ('".$typeNews."'))) AND (t.language IN ('".$languages."'))) AND (guid > ".$minGuid." and guid < ".$maxGuid."))
      ORDER BY t.guid ASC
      LIMIT ".$limit."
    ";
    // do bulk select query through DAO
    $dataReader = app()->db->createCommand($sqlQuery)->query();
    // read data into news array
    while (($row = $dataReader->read()) !== false) {
      $news[] = $row;
    }

    return $news;
  }

  /**
   * Export news into file (new version).
   *
   * $Id$
   * $HeadURL$
   * @author     pavel.albert
   * @since      2014-06-24
   *
   * @param      string         $startDate          Export start date (YYYY-MM-DD). Default: yesterday's date.
   * @param      string         $endDate            Export end date (YYYY-MM-DD). Default: yesterday's date.
   * @param      string         $languages          Comma delimited languges iso codes. Default: application language.
   * @param      string         $typeNews           Comma delimited news type's (Message::TYPE_*). Default: Message::TYPE_RSS_NEWS.
   * @param      integer        $limitPerIteration  Max limit for SQL-selects.
   * @param      string         $delimiter          Fields delimiter.
   *
   * @return     void
   */
  public function actionExportNews($startDate = null, $endDate = null, $languages = null, $typeNews = null, $limitPerIteration = 100, $delimiter = '|')
  {
    // set working parameters
    $limitPerIteration= empty($limitPerIteration)    ? 100                                                : (int)$limitPerIteration;  // 100 by default
    $minDateParsed    = date_parse(empty($startDate) ? date(Time::FORMAT_DEFAULT, strtotime('-1 days'))   : $startDate);              // yesterday by default
    $maxDateParsed    = date_parse(empty($endDate)   ? date(Time::FORMAT_DEFAULT, strtotime('-1 days'))   : $endDate);                // yesterday by default
    $languages        = empty($languages)            ? app()->getLocale()->getLanguageID(app()->language) : $languages;               // 'en' by default
    $typeNews         = empty($typeNews)             ? Message::TYPE_RSS_NEWS                             : $typeNews;                // 7 by default

    // calculate minimal and maximal timestamps/dates
    $minDateTimestamp = CTimestamp::getTimestamp(00, 00, 00, $minDateParsed['month'], $minDateParsed['day'], $minDateParsed['year'], true);
    $maxDateTimestamp = CTimestamp::getTimestamp(23, 59, 59, $maxDateParsed['month'], $maxDateParsed['day'], $maxDateParsed['year'], true);
    $minDate          = gmdate(Time::FORMAT_DEFAULT, $minDateTimestamp);
    $maxDate          = gmdate(Time::FORMAT_DEFAULT, $maxDateTimestamp);

    // check for min and max timestamps range
    if ($minDateTimestamp > $maxDateTimestamp) {
      $this->message('startDate ('.$minDate.') > endDate ('.$maxDate.')');
      return;
    }

    // set log's file name with full path (hardcoded)
    $fileName = app()->runtimePath.DS.'export'.DS.'newsExport_'.Time::format(Time::getUTC(), Time::FORMAT_FILENAME).'_'.Guid::get().'.txt';
    $dirName  = app()->runtimePath.DS.'export'.DS;
    if (!file_exists($dirName)) {
      mkdir($dirName, 0777); // for ensure that directory exists
    }

    // open file for write
    $fileHandle = fopen($fileName, 'a');

    // write headers into file
    $stringLine = implode($delimiter, array('Date/Time UTC', 'Feed name', 'Title', 'Text'))."\n";
    fwrite($fileHandle, $stringLine);

    // calculate minimal and maximal guids for export
    $minGuid = Guid::min($minDate);
    $maxGuid = Guid::max(gmdate(Time::FORMAT_DEFAULT, $maxDateTimestamp + 1)); // +1 for grab of all messages

    // initialization before cycle
    $tmpDateTimestamp = $minDateTimestamp;
    $tmpMinGuid       = $minGuid;
    $tmpMaxGuid       = Guid::max(gmdate(Time::FORMAT_DEFAULT, $tmpDateTimestamp + 86400));

    // summary count of exported messages
    $total = 0;

    do {

      if ($tmpDateTimestamp > $maxDateTimestamp) {
        break 1; // break current cycle if current date is more than maximal date
      }

      // set current minimal and maximal timestamps/dates
      $tmpMinDate = gmdate(Time::FORMAT_DEFAULT, $tmpDateTimestamp);
      $tmpMaxDate = gmdate(Time::FORMAT_DEFAULT, $tmpDateTimestamp + 86400);
      $tmpDateTimestamp += 86400;

      // count of exported messages for current date
      $count = 0;
      do {

        // set current minimal and maximal guids
        $tmpMinGuid = empty($maxReceivedGuid) ? Guid::min($tmpMinDate) : $maxReceivedGuid;
        $tmpMaxGuid = Guid::max($tmpMaxDate);

        // array for store of messages guids
        $guids    = array();

        // get messages from DB
        $messages = $this->getNewsDataForExport($tmpMinGuid, $tmpMaxGuid, $languages, $typeNews, $limitPerIteration);

        if (empty($messages)) {
          break 1; // break current cycle if there are not messages
        }
        else {
          // there are messages and we need to export them into file
          foreach ($messages as $message) {
            $guids[] = $message['guid'];
            $articleAttr = json_decode($message['attr']); // related article attribute is JSON-encoded
            $text = empty($articleAttr->data->text) ? '' : $articleAttr->data->text;
            if (empty($text)) {
              $text = empty($message['text']) ? $message['short_text'] : $message['text'];
            }
            $text = HString::getPlainText($text);
            // prepare line with message data for writing
            $stringLine = implode($delimiter, array($message['created_at'], $message['name'], $message['title'], $text))."\n";
            fwrite($fileHandle, $stringLine);
          }
          // calculate maximal received guid for current part of messages
          $maxReceivedGuid = max($guids);
          $count += count($messages);
        }

      } while (true);

      $total += $count;

      if ($this->isDebug) {
        $this->message('Date '.gmdate('Y-m-d', strtotime($tmpMinDate)).' => messages count: '.sprintf('%5d', $count));
      }

    } while (true);

    // close file
    fclose($fileHandle);

    // log about work
    if ($this->isDebug) {
      $this->message('');
      $this->message('Total exported:    '.sprintf('%d', $total).' message(s)');
      $this->message('');
      $this->message('Memory usage:      '.sprintf('%03.1f', memory_get_usage(true) / 1024 / 1024).' MB');
      $this->message('Memory peak usage: '.sprintf('%03.1f', memory_get_peak_usage(true) / 1024 / 1024).' MB');
      $this->message('');
      $this->message('Export complete into file "'.$fileName.'"');
    }
  }

  /**
   * Export information from i18n data to files of JSON-format.
   *
   * $Id$
   * $HeadURL$
   * @author     pavel.albert
   * @since      2014-07-14
   *
   * @return     void
   */
  public function actionI18NToJSON()
  {
    // set log's file name with full path (hardcoded)
    $logDirName  = app()->runtimePath.DS.'export'.DS;
    if (!file_exists($logDirName)) {
      mkdir($logDirName, 0777); // for ensure that directory exists
    }
    $logFileName = $logDirName.'i18nToJSONExport_'.Time::format(Time::getUTC(), Time::FORMAT_FILENAME).'_'.Guid::get().'.txt';
    // open file for write
    $logFileHandle = fopen($logFileName, 'a');

    // set directory for exported data files
    $jsonDirName = app()->runtimePath.DS.'i18nJSON'.DS;
    if (!file_exists($jsonDirName)) {
      mkdir($jsonDirName, 0777); // for ensure that directory exists
    }

    $this->message('JSON directory for i18n data: "'.$jsonDirName.'"');

    // set directory from where we need to read i18n data
    $i18nDataDirName = Yii::getFrameworkPath().DS.'i18n'.DS.'data'.DS;

    $this->message('Directory with i18n data: "'.$i18nDataDirName.'"');

    // set counter for total count of exported i18n files
    $cnt = 0;
    foreach (new DirectoryIterator($i18nDataDirName) as $file) {
      $fullFileName = $file->getPathname();
      $pathinfo = pathinfo($fullFileName);
      $languageName = $pathinfo['filename']; // language name as filename without extension
      // read only .php files and skip "."/".." files
      if (!$file->isDot() && $pathinfo['extension'] == 'php') {
        // attempt to read array with locale data
        $data = require_once $fullFileName;
        // we use this method instead of simple built-in function json_encode()
        $jsonData = HArray::jsonEncode($data);
        // write JSON-encoded data to file
        $fh = fopen($jsonDirName.DS.$languageName.'.json', 'w');
        fwrite($fh, $jsonData);
        fclose($fh);
        $stringLine = $file->getFilename()."\t".$languageName."\n";
        fwrite($logFileHandle, $stringLine);
        if ($this->isDebug) {
          $this->message(trim($stringLine));
        }
        $cnt++;
      }
    }

    $stringLine  = "\n";
    $stringLine .= 'Total exported:    '.sprintf('%d', $cnt).' file(s)'."\n";
    $stringLine .= "\n";
    $stringLine .= 'Memory usage:      '.sprintf('%03.1f', memory_get_usage(true) / 1024 / 1024).' MB'."\n";
    $stringLine .= 'Memory peak usage: '.sprintf('%03.1f', memory_get_peak_usage(true) / 1024 / 1024).' MB'."\n";
    $stringLine .= "\n";
    fwrite($logFileHandle, $stringLine);

    // debug into console about work results
    if ($this->isDebug) {
      foreach (explode("\n", $stringLine) as $strChunk) {
        $this->message(trim($strChunk));
      }
      $this->message('Export complete into file "'.$logFileName.'"');
    }

    // close log file
    fclose($logFileHandle);
  }
}
?>