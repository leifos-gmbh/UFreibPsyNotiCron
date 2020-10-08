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
        if (is_null($form)) {
            $form = $this->initNotificationForm();
        }
        $this->main_tpl->setContent($form->getHTML());
    }

	private function initNotificationForm($id = null)
    {
        $form = new ilPropertyFormGUI();

        $form->setTitle($this->plugin->txt("notification_form"));
        $form->setFormAction($this->ctrl->getFormAction($this));

        $event_select = new ilSelectInputGUI($this->plugin->txt("event_type"), "event_type");
        $event_select->setRequired(true);
        $event_select->setOptions([
           "" => $this->lng->txt("please_choose"),
           ilUFreibPsyNotiPlugin::EVENT_TYPE_SCORM_ACCESS => $this->plugin->txt("scorm_access"),
           ilUFreibPsyNotiPlugin::EVENT_TYPE_SCORM_COMPLETED => $this->plugin->txt("scorm_completed"),
           ilUFreibPsyNotiPlugin::EVENT_TYPE_SCORM_NOT_FINISHED => $this->plugin->txt("scorm_unfinished")
        ]);
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
        $accounts->addSubItem($acc_list_input);
        $recipient->addOption($accounts);
        $form->addItem($recipient);

        $reminder_day = new ilNumberInputGUI($this->plugin_object->txt("days_to_reminder"), "reminder_after_x_days");
        $reminder_day->setRequired(true);
        $form->addItem($reminder_day);

        $text = new ilTextAreaInputGUI($this->lng->txt("udf_type_text"), "text");
        $text->setRequired(true);
        $form->addItem($text);

        $form->addCommandButton("saveNotificationSetting", $this->lng->txt("save"));
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
        $notification->setReminderAfterXDays($form->getInput("reminder_after_x_days"));
        $notification->setText($form->getInput("text"));

        //insert notification values into DB
        $notification->create();

        ilUtil::sendSuccess($this->lng->txt("notification_saved"));
        $this->ctrl->redirect($this, "listNotifications");
    }

    private function updateNotificationSetting()
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
        $notification->setReminderAfterXDays($form->getInput("reminder_after_x_days"));
        $notification->setText($form->getInput("text"));

        //update notification values in DB
        $notification->update();

        ilUtil::sendSuccess($this->lng->txt("notification_saved"));
        $this->ctrl->redirect($this, "listNotifications");
    }

}