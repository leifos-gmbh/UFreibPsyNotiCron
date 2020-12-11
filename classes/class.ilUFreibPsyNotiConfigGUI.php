<?php

/**
 *
 *
 * @author Marvin Barz <barz@leifos.com>
 * @ilCtrl_Calls ilUFreibPsyNotiConfigGUI: ilPropertyFormGUI
 */

class ilUFreibPsyNotiConfigGUI extends ilPluginConfigGUI
{
    /**
     * @var ilCtrl
     */
    protected $ctrl;

    /**
     * @var ilTemplate
     */
    protected $main_tpl;

    /**
     * @var ilLanguage
     */
    protected $lng;

    /**
     * @var ilSetting
     */
    protected $settings;

    /**
     * @var ilPlugin
     */
    protected $plugin;

    /**
     * @var ilToolbarGUI
     */
    protected $toolbar;

    /**
     * @var int
     */
    protected $notification_id;

    /**
     * @var array
     */
    protected $notification_ids;

    /**
     * Handles all commmands, default is "configure"
     */
    function performCommand($cmd)
    {
        global $DIC;

        $this->ctrl       = $DIC->ctrl();
        $this->main_tpl   = $DIC->ui()->mainTemplate();
        $this->lng        = $DIC->language();
        $this->plugin     = $this->getPluginObject();
        $this->toolbar    = $DIC->toolbar();

        $request = $DIC->http()->request();
        $this->notification_id = (int) $request->getQueryParams()["notification_id"];
        $this->notification_ids = $request->getParsedBody()["notification_ids"];

        $this->ctrl->saveParameter($this, "notification_id");

        $this->settings = new ilSetting("ufreibpsynoti");

        $next_class = $this->ctrl->getNextClass($this);

        switch ($next_class) {
            case 'ilpropertyformgui':
                $form = $this->initNotificationForm();
                $this->ctrl->forwardCommand($form);
                break;
            default:
                break;
        }

        switch ($cmd) {
            case 'notificationform':
                $this->showForm();
                break;
            default:
                $this->$cmd();
                break;

        }
    }

    /**
     * Configure
     *
     */
    protected function configure()
    {
        $this->listNotifications();
	}

	protected function createNotificationSetting(ilPropertyFormGUI $form = null)
    {
        if (is_null($form)) {
            $form = $this->initNotificationForm();
        }
        $this->main_tpl->setContent($form->getHTML());
    }

    protected function editNotificationSetting(ilPropertyFormGUI $form = null)
    {
        global $DIC;
        $request = $DIC->http()->request();

        if (is_null($form)) {
            $form = $this->initNotificationForm("updateNotificationSetting", $this->notification_id);
        }
        $this->main_tpl->setContent($form->getHTML());
    }

	private function initNotificationForm($save_cmd = "saveNotificationSetting", $id = null)
    {

        $form = new ilPropertyFormGUI();

        $form->setTitle($this->plugin->txt("notification_form"));
        $form->setFormAction($this->ctrl->getFormAction($this));

        $event_select = new ilRadioGroupInputGUI($this->plugin->txt("event_type"), "event_type");
        $event_select->setRequired(true);

        $op1 = new ilRadioOption($this->plugin->txt("scorm_access"), ilUFreibPsyNotiPlugin::EVENT_TYPE_SCORM_ACCESS);
        $event_select->addOption($op1);
        $op2 = new ilRadioOption($this->plugin->txt("scorm_completed"), ilUFreibPsyNotiPlugin::EVENT_TYPE_SCORM_COMPLETED);
        $event_select->addOption($op2);
        $op3 = new ilRadioOption($this->plugin->txt("scorm_unfinished"), ilUFreibPsyNotiPlugin::EVENT_TYPE_SCORM_NOT_FINISHED);
        $event_select->addOption($op3);
        $op4 = new ilRadioOption($this->plugin->txt("feedback_sent"), ilUFreibPsyNotiPlugin::EVENT_TYPE_FEEDBACK_SENT);
        $event_select->addOption($op4);
        $reminder_day = new ilNumberInputGUI($this->plugin_object->txt("days_to_reminder"), "reminder_after_x_days");
        $reminder_day->setRequired(true);
        $reminder_day->setMaxLength(3);
        $reminder_day->setSize(3);
        $op3->addSubItem($reminder_day);

        $form->addItem($event_select);

        $repos = new ilRepositorySelector2InputGUI($this->plugin->txt("scorm_object"), "scorm_ref_id");
        $repos->setRequired(true);
        $definition = $GLOBALS['DIC']['objDefinition'];
        $white_list = [];
        foreach ($definition->getAllRepositoryTypes() as $type) {
            if ($definition->isContainer($type)) {
                $white_list[] = $type;
            }
        }
        $white_list[] = "sahs";
        $repos->getExplorerGUI()->setTypeWhiteList($white_list);
        $repos->getExplorerGUI()->setSelectableTypes(["sahs"]);

        $form->addItem($repos);


        $recipient = new ilRadioGroupInputGUI($this->plugin->txt("recipient_type"), "recipient_type");
        $recipient->setRequired(true);

        $students = new ilRadioOption($this->plugin->txt("recipient_students"), ilUFreibPsyNotiPlugin::RECIPIENT_TYPE_STUDENT);
        $recipient->addOption($students);

        $ecoaches = new ilRadioOption($this->plugin->txt("recipient_ecoaches"), ilUFreibPsyNotiPlugin::RECIPIENT_TYPE_ECOACHES);
        $recipient->addOption($ecoaches);

        $accounts = new ilRadioOption($this->plugin->txt("recipient_accounts"), ilUFreibPsyNotiPlugin::RECIPIENT_TYPE_ACCOUNTS);

        $acc_list_input = new ilTextInputGUI($this->plugin->txt("recipient_accounts"), "recipient_accounts");
        $acc_list_input->setInfo($this->plugin->txt("recipient_accounts_info"));
        $acc_list_input->setRequired(true);
        $accounts->addSubItem($acc_list_input);
        $recipient->addOption($accounts);
        $form->addItem($recipient);

        // subject
        $ti = new ilTextInputGUI($this->plugin->txt("subject"), "subject");
        $ti->setRequired(true);
        $form->addItem($ti);

        // message
        $text = new ilTextAreaInputGUI($this->plugin->txt("message"), "text");
        $text->setRequired(true);
        $text->setRows(10);
        $form->addItem($text);

        if (!empty($id)) {
            $notification = new ilUFreibPsyNotification($id);

            $event_select->setValue($notification->getEventType());
            $repos->setValue($notification->getScormRefId());
            $recipient->setValue($notification->getRecipientType());
            $acc_list_input->setValue($notification->getRecipientAccounts());
            $reminder_day->setValue($notification->getReminderAfterXDays());
            $text->setValue($notification->getText());
            $ti->setValue($notification->getSubject());
        }

        $form->addCommandButton($save_cmd, $this->lng->txt("save"));
        $form->addCommandButton("listNotifications", $this->lng->txt("cancel"));

        return $form;
    }

    private function listNotifications()
    {
        $button = ilLinkButton::getInstance();
        $button->setCaption($this->plugin_object->txt("add_notification"), false);
        $button->setUrl($this->ctrl->getLinkTarget($this, 'createNotificationSetting'));
        $this->toolbar->addButtonInstance($button);

        $table = new ilUFreibPsyNotiTableGUI($this, "listNotifications", $this->plugin_object);

        $this->main_tpl->setContent($table->getHTML());
    }

    private function saveNotificationSetting()
    {

        $form = $this->initNotificationForm();
        if (!$form->checkInput()) {
            $form->setValuesByPost();
            ilUtil::sendFailure($this->lng->txt("err_check_input"));
            return $this->createNotificationSetting($form);
        }

        //initiate notification instance and set values
        $notification = new ilUFreibPsyNotification();
        $notification->setEventType($form->getInput("event_type"));
        $notification->setRecipientType($form->getInput("recipient_type"));
        $notification->setScormRefId($form->getInput("scorm_ref_id"));
        if(!empty($form->getInput("recipient_accounts"))) {
            $notification->setRecipientAccounts($form->getInput("recipient_accounts"));
        }
        $notification->setReminderAfterXDays((int) $form->getInput("reminder_after_x_days"));
        $notification->setSubject($form->getInput("subject"));
        $notification->setText($form->getInput("text"));

        //insert notification values into DB
        $notification->create();

        ilUtil::sendSuccess($this->lng->txt("notification_saved"));
        $this->ctrl->redirect($this, "listNotifications");
    }

    private function updateNotificationSetting()
    {
        $form = $this->initNotificationForm("updateNotificationSetting");
        if (!$form->checkInput()) {
            $form->setValuesByPost();
            ilUtil::sendFailure($this->lng->txt("err_check_input"));
            return $this->editNotificationSetting($form);
        }

        //initiate notification instance and set values
        $notification = new ilUFreibPsyNotification($this->notification_id);
        $notification->setEventType($form->getInput("event_type"));
        $notification->setRecipientType($form->getInput("recipient_type"));
        $notification->setScormRefId($form->getInput("scorm_ref_id"));
        if(!empty($form->getInput("recipient_accounts")) && $form->getInput("recipient_type") == ilUFreibPsyNotiPlugin::RECIPIENT_TYPE_ACCOUNTS) {
            $notification->setRecipientAccounts($form->getInput("recipient_accounts"));
        } else {
            $notification->setRecipientAccounts("");
        }
        $notification->setReminderAfterXDays((int) $form->getInput("reminder_after_x_days"));
        $notification->setSubject($form->getInput("subject"));
        $notification->setText($form->getInput("text"));

        //update notification values in DB
        $notification->update();

        ilUtil::sendSuccess($this->plugin_object->txt("notification_saved"));
        $this->ctrl->redirect($this, "listNotifications");
    }


    public function confirmDelete()
    {

        if ($this->notification_id > 0) {
            $notification_ids[] = $this->notification_id;
        } else {
            $notification_ids = $this->notification_ids;
        }

        $cgui = new ilConfirmationGUI();
        $cgui->setFormAction($this->ctrl->getFormAction($this));
        $cgui->setHeaderText($this->lng->txt("info_delete_sure"));
        foreach ($notification_ids as $notification_id) {
            $notification = new ilUFreibPsyNotification($notification_id);

            switch ($notification->getEventType()) {
                case ilUFreibPsyNotiPlugin::EVENT_TYPE_SCORM_ACCESS:
                    $event_type = $this->plugin_object->txt("scorm_access");
                    break;
                case ilUFreibPsyNotiPlugin::EVENT_TYPE_SCORM_COMPLETED:
                    $event_type = $this->plugin_object->txt("scorm_completed");
                    break;
                case ilUFreibPsyNotiPlugin::EVENT_TYPE_SCORM_NOT_FINISHED:
                    $event_type = $this->plugin_object->txt("scorm_unfinished");
                    break;
                default:
                    break;
            }

            switch ($notification->getRecipientType()) {
                case ilUFreibPsyNotiPlugin::RECIPIENT_TYPE_STUDENT:
                    $recipient_type = $this->plugin_object->txt("recipient_students");
                    break;
                case ilUFreibPsyNotiPlugin::RECIPIENT_TYPE_ECOACHES:
                    $recipient_type = $this->plugin_object->txt("recipient_ecoaches");
                    break;
                case ilUFreibPsyNotiPlugin::RECIPIENT_TYPE_ACCOUNTS:
                    $recipient_type = $this->plugin_object->txt("recipient_accounts");
                    break;
                default:
                    break;
            }

            $scorm_obj_title = ilObject::_lookupTitle(ilObject::_lookupObjectId($notification->getScormRefId()));

            $cgui->addItem("notification_ids[]", $notification_id, sprintf($this->plugin_object->txt("notification_conc_desc"), $event_type, $recipient_type, $scorm_obj_title, $notification_id));
        }
        $cgui->setCancel($this->lng->txt("cancel"), "listNotifications");
        $cgui->setConfirm($this->lng->txt("delete"), "deleteNotification");


        $this->main_tpl->setContent($cgui->getHTML());
    }

    private function deleteNotification()
    {
        $notification_ids = $this->notification_ids;

        foreach ($notification_ids as $notification_id) {
            $notification = new ilUFreibPsyNotification($notification_id);
            $notification->delete();
        }

        ilUtil::sendSuccess($this->plugin_object->txt("notification_deleted"));
        $this->ctrl->redirect($this, "listNotifications");
    }

}