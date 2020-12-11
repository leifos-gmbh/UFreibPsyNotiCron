<?php
include_once("./Services/Cron/classes/class.ilCronHookPlugin.php");

class ilUFreibPsyNotiPlugin extends ilCronHookPlugin
{
    /**
     * @var \ilUFreibPsyNotiPlugin|null
     */
    private static $instance = null;

    const EVENT_TYPE_SCORM_ACCESS = 1;
    const EVENT_TYPE_SCORM_COMPLETED = 2;
    const EVENT_TYPE_SCORM_NOT_FINISHED = 3;
    const EVENT_TYPE_FEEDBACK_SENT = 4;
    const RECIPIENT_TYPE_STUDENT = 1;
    const RECIPIENT_TYPE_ECOACHES = 2;
    const RECIPIENT_TYPE_ACCOUNTS = 3;

    const CTYPE = 'Services';
    const CNAME = 'Cron';
    const SLOT_ID = 'crnhk';
    const PNAME = 'UFreibPsyNoti';

    /**
     * @return \ilUFreibPsyNotiPlugin|\ilPlugin|null
     */
    public static function getInstance()
    {
        global $ilPluginAdmin;

        if(self::$instance)
        {
            return self::$instance;
        }
        return self::$instance = ilPluginAdmin::getPluginObject(
            self::CTYPE,
            self::CNAME,
            self::SLOT_ID,
            self::PNAME
        );
    }

    /**
     * Init auto load
     */
    protected function init()
    {
        $this->initAutoLoad();
    }

    /**
     * Init auto loader
     * @return void
     */
    protected function initAutoLoad()
    {
        spl_autoload_register(
            array($this,'autoLoad')
        );
    }

    /**
     * Auto load implementation
     *
     * @param string class name
     */
    private final function autoLoad($a_classname)
    {
        $class_file = $this->getClassesDirectory().'/class.'.$a_classname.'.php';
        if(file_exists($class_file) && include_once($class_file))
        {
            return;
        }
    }

    function getPluginName()
    {
        return self::PNAME;
    }

    function getCronJobInstances()
    {
        $job = new ilUFreibPsyNotiCronjob();

        return array($job);
    }

    function getCronJobInstance($a_job_id)
    {
        return new ilUFreibPsyNotiCronjob();
    }

    public function handleEvent($a_component, $a_event, $a_parameter)
    {
        $this->includeClass("class.ilUFreibEventHandler.php");
        $event_handler = new ilUFreibEventHandler($this);
        $event_handler->handleEvent($a_component, $a_event, $a_parameter);
    }

}