<?php

class ilUFreibPsyNotiTableGUI extends ilTable2GUI
{
    private $plugin_object;

    function __construct($a_parent_obj, $a_parent_cmd, $plugin_object)
    {
        global $DIC;
        $this->plugin_object = $plugin_object;

        $this->setId("ilUFreibPsyNotiTableGUI");
        parent::__construct($a_parent_obj, $a_parent_cmd);

        $DIC->logger()->usr()->info("Tableclass is called");

        $this->initNotificationTable();
        $this->setTableData();
        $this->fillRow($this->getData());
    }

    public function initNotificationTable()
    {
        $this->setTitle($this->lng->txt("notifications"));

        $this->addColumn($this->plugin_object->txt("notification_id"), "notification_id");
        $this->addColumn($this->plugin_object->txt("event_type"), "event_type");
        $this->addColumn($this->plugin_object->txt("recipient_type"), "recipient_type");
        $this->addColumn($this->plugin_object->txt("scorm_object"), "scorm_ref_id");
        $this->addColumn($this->lng->txt("usrf"), "recipient_accounts");
        $this->addColumn($this->plugin_object->txt("days_to_reminder"), "reminder_after_x_days");
        $this->addColumn($this->lng->txt("udf_type_text"), "text");
        $this->setRowTemplate($this->plugin_object->getDirectory()."/templates/tpl.notification_row.html");
    }


    private function setTableData()
    {

        $notification_ids = ilUFreibPsyNotification::lookupNotificationIds();

        $n = 0;
        $tbl_data = array();
        foreach ($notification_ids as $notification_id) {
            $notification = new ilUFreibPsyNotification($notification_id);

            $tbl_data[$n]['notification_id'] = $notification_id;

            switch ($notification->getEventType()) {
                case ilUFreibPsyNotiPlugin::EVENT_TYPE_SCORM_ACCESS:
                    $tbl_data[$n]['event_type'] = $this->plugin_object->txt("scorm_access");
                    break;
                case ilUFreibPsyNotiPlugin::EVENT_TYPE_SCORM_COMPLETED:
                    $tbl_data[$n]['event_type'] = $this->plugin_object->txt("scorm_completed");
                    break;
                case ilUFreibPsyNotiPlugin::EVENT_TYPE_SCORM_NOT_FINISHED:
                    $tbl_data[$n]['event_type'] = $this->plugin_object->txt("scorm_unfinished");
                    break;
                default:
                    break;
            }

            switch ($notification->getRecipientType()) {
                case ilUFreibPsyNotiPlugin::RECIPIENT_TYPE_STUDENT:
                    $tbl_data[$n]['recipient_type'] = $this->plugin_object->txt("recipient_students");
                    break;
                case ilUFreibPsyNotiPlugin::RECIPIENT_TYPE_ECOACHES:
                    $tbl_data[$n]["recipient_type"] = $this->plugin_object->txt("recipient_ecoaches");
                    break;
                case ilUFreibPsyNotiPlugin::RECIPIENT_TYPE_ACCOUNTS:
                    $tbl_data[$n]["recipient_type"] = $this->plugin_object->txt("recipient_accounts");
                    break;
                default:
                    break;
            }

            $tbl_data[$n]["scorm_obj_title"] = ilObject::_lookupTitle(ilObject::_lookupObjectId($notification->getScormRefId()));
            $tbl_data[$n]["recipient_accs"]  = $notification->getRecipientAccounts();
            $tbl_data[$n]["reminder"]        = $notification->getReminderAfterXDays();
            $tbl_data[$n]["text"]            = $notification->getText();

            $n++;
        }

        $this->setDefaultOrderField("notification_id");
        $this->setDefaultOrderDirection("asc");
        $this->setData($tbl_data);
    }

    protected function fillRow($a_set)
    {
        $this->tpl->setVariable("NOTI_ID", $a_set["notification_id"]);
        $this->tpl->setVariable("EVENT_TYPE", $a_set["event_type"]);
        $this->tpl->setVariable("RECIPIENT_TYPE", $a_set["recipient_type"]);
        $this->tpl->setVariable("SCORM_OBJECT", $a_set["scorm_obj_title"]);
        $this->tpl->setVariable("RECIPIENT_ACCS", $a_set["recipient_accs"]);
        $this->tpl->setVariable("REMINDER", $a_set["reminder"]);
        $this->tpl->setVariable("TEXT", $a_set["text"]);

    }
}