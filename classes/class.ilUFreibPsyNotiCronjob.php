<?php

class ilUFreibPsyNotiCronjob extends ilCronJob
{
    const DEFAULT_SCHEDULE_TIME = 1;

    /**
     * @var \ilUFreibPsyNotiPlugin
     */
    protected $plugin;

    /**
     * @return string
     */
    public function getId()
    {
        return ilUFreibPsyNotiPlugin::getInstance()->getId();
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return ilUFreibPsyNotiPlugin::PNAME;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return ilUFreibPsyNotiPlugin::getInstance()->txt('cron_job_info');
    }

    /**
     * @return int
     */
    public function getDefaultScheduleType()
    {
        return self::SCHEDULE_TYPE_IN_HOURS;
    }

    /**
     * @return array|int
     */
    public function getDefaultScheduleValue()
    {
        return self::DEFAULT_SCHEDULE_TIME;
    }

    /**
     * @return bool
     */
    public function hasAutoActivation()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function hasFlexibleSchedule()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function hasCustomSettings()
    {
        return true;
    }

    /**
     * @return \ilCronJobResult
     */
    public function run()
    {
        $log = ilLoggerFactory::getLogger("mail");

        $result = new ilCronJobResult();

        $plugin = $this->getPlugin();
        $plugin->includeClass("class.ilUFreibPsyNotiAccessRepository.php");
        $access_repo = new ilUFreibPsyNotiAccessRepository();
        $plugin->includeClass("class.ilUFreibPsyNotiAccessRepository.php");
        $handler = new ilUFreibEventHandler($plugin);

        $log->debug("--- ilUFreibPsyNoti start");

        foreach (ilUFreibPsyNotification::_query(ilUFreibPsyNotiPlugin::EVENT_TYPE_SCORM_NOT_FINISHED) as $noti) {
            $log->debug("--- noti: ".$noti->getId());
            foreach ($access_repo->getUserToNotify($noti->getReminderAfterXDays(), $noti->getScormRefId()) as $student_id) {
                $log->debug("--- student: ".$student_id);
                $handler->sendNotification($noti, $student_id);
            }
        }


        \ilLoggerFactory::getLogger('otxt')->info('Cron job result is: ' . $result->getCode());

        return $result;
    }

    /**
     * @return \ilUFreibPsyNotiPlugin
     */
    public function getPlugin()
    {
        return \ilUFreibPsyNotiPlugin::getInstance();
    }
}