<?php

/**
 * Removes not started old sessions and deactivates finished sessions
 *
 * Command:
 * /path/to/public_html/protected/yiic sessionMonitor
 * start nightly
 * 
 * (Please do sure that file yiic is allowable to run (chmod +x file))
 * 
 */
class SessionMonitorCommand extends CConsoleCommand
{

    public function actionIndex()
    {
        $db = Yii::app()->db;
        // not started sessions
        $date = date('Y-m-d H:i:s', strtotime('-2 days'));
        //$sql = "UPDATE subscription_status SET active = 0 WHERE active=1 AND date_start='0000-00-00 00:00:00' AND date_created < '{$date}'";
        $sql = "DELETE from subscription_status WHERE active=1 AND date_start='0000-00-00 00:00:00' AND date_created < '{$date}'";
        $db->createCommand($sql)->execute();
        
        // finished sms sessions
        $date = date('Y-m-d H:i:s');
        $sql = "UPDATE subscription_status SET active = 0 WHERE active=1 AND payed=1 AND subscribe_type IN ('sms', 'pseudo') AND date_end < '{$date}' ";
        $db->createCommand($sql)->execute();

        // finished subscribe sessions will be deactivated by STOP
    }
}
