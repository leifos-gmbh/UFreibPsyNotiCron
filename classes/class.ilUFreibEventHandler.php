<?php

/* Copyright (c) 1998-2020 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Event handler
 * @author Alexander Killing <killing@leifos.de>
 */
class ilUFreibEventHandler
{
    /**
     * @var ilPlugin
     */
    protected $plugin;

    /**
     * @var ilRbacReview
     */
    protected $rbacreview;

    /**
     * @var \ilAccessHandler
     */
    protected $access;

    /**
     * @var ilUFreibPsyNotiAccessRepository
     */
    protected $access_repo;

    protected $handled = [];

    /**
     * Constructor
     */
    public function __construct($plugin)
    {
        global $DIC;
        $this->plugin = $plugin;
        $this->rbacreview = $DIC->rbac()->review();
        $this->access = $DIC->access();
        $this->plugin->includeClass("class.ilUFreibPsyNotiAccessRepository.php");
        $this->access_repo = new ilUFreibPsyNotiAccessRepository();

        $this->log = ilLoggerFactory::getLogger("mail");
    }

    public function handleEvent($a_component, $a_event, $a_parameter)
    {
        if ($a_component == "Services/AccessControl" && $a_event == "assignUser") {
            $this->handleAssignUserEvent($a_parameter);
        }
        if ($a_component == "Services/Tracking" && $a_event == "updateStatus") {
            $this->handleUpdateStatusEvent($a_parameter);
        }
        if ($a_component == "Services/Mail" && $a_event == "freibFeedbackSent") {
            $this->log->debug("----*** handleFeedbackEvent (1) ");
            $this->handleFeedbackEvent($a_parameter);
        }
    }

    /**
     * handle role assignment
     * @param array $par
     */
    protected function handleFeedbackEvent($par)
    {
        $scorm_ref_id = $par["scorm_ref_id"];
        $usr_id = $par["student_id"];
        if (!ilObject::_isInTrash($scorm_ref_id)) {
            $this->log->debug("----*** handleFeedbackEvent (2) $usr_id, $scorm_ref_id");
            $this->sendFeedbackNotifications($usr_id, $scorm_ref_id);
        }
    }

    /**
     * Send feedback notifications
     * @param $usr_id
     * @param $ref_id
     */
    protected function sendFeedbackNotifications($usr_id, $ref_id)
    {
        $this->plugin->includeClass("class.ilUFreibPsyNotification.php");
        foreach (ilUFreibPsyNotification::_query(ilUFreibPsyNotiPlugin::EVENT_TYPE_FEEDBACK_SENT, $ref_id) as $noti) {
            $this->log->debug("----*** handleFeedbackEvent (3) ");
            $this->sendNotification($noti, $usr_id);
        }
    }


    /**
     * handle role assignment
     * @param array $par
     */
    protected function handleAssignUserEvent($par)
    {
        global $DIC;

        $rbacsystem = $DIC->rbac()->system();
        $rbacreview = $this->rbacreview;
        $access = $this->access;

        $this->log->debug("-handleAssignUser-1");
        $obj_id = $par['obj_id'];
        $usr_id = $par['usr_id'];
        $role_id = $par['role_id'];
        $type = $par['type'];
        // has user been assigned to a role of a scorm object?
        if ($type == "sahs") {
            $this->log->debug("-handleAssignUser-2");
            $ref_id = $rbacreview->getObjectReferenceOfRole($role_id);

            $this->log->debug("-checking-role_id-$role_id-ref_id-$ref_id-usr_id-$usr_id-");

            // we check, if the user has read access to the object now
            // important: clear all the caches, otherwise this may return the wrong result
            $access->clear();
            $rbacsystem->resetCaches();
            if ($access->checkAccessOfUser($usr_id, "read", "", $ref_id)) {
                $this->log->debug("-handleAssignUser-3");

                // we store the access granted timestamp and send notifications, if
                // we did not store and sent already
                if ($this->access_repo->getAccessGrantedTS($usr_id, $ref_id) == 0) {
                    $this->log->debug("-handleAssignUser-4");
                    $this->sendAccessNotifications($usr_id, $ref_id);
                    $this->access_repo->storeAccessGranted($usr_id, $ref_id);
                }
            }
        }
    }

    /**
     * Send access notifications
     * @param $usr_id
     * @param $ref_id
     */
    protected function sendAccessNotifications($usr_id, $ref_id)
    {
        $this->plugin->includeClass("class.ilUFreibPsyNotification.php");
        foreach (ilUFreibPsyNotification::_query(ilUFreibPsyNotiPlugin::EVENT_TYPE_SCORM_ACCESS, $ref_id) as $noti) {
            $this->sendNotification($noti, $usr_id);
        }
    }

    /**
     * handle role assignment
     * @param array $par
     */
    protected function handleUpdateStatusEvent($par)
    {
        $obj_id = $par["obj_id"];
        $usr_id = $par["usr_id"];
        $status = $par["status"];
        $old_status = $par["old_status"];
        $this->log->debug("---");
        $this->log->logStack(ilLogLevel::DEBUG);
        $this->log->debug($obj_id);
        $this->log->debug($usr_id);
        $this->log->debug($status);
        $this->log->debug($old_status);
        if ($status == ilLPStatus::LP_STATUS_COMPLETED_NUM && $old_status != ilLPStatus::LP_STATUS_COMPLETED_NUM) {
            if (ilObject::_lookupType($obj_id) == "sahs") {
                foreach (ilObject::_getAllReferences($obj_id) as $scorm_ref_id) {
                    if (!ilObject::_isInTrash($scorm_ref_id)) {
                        if ($this->access_repo->storeCompletedTS($usr_id, $scorm_ref_id)) {
                            $this->log->debug("***");
                            $this->sendCompletedNotifications($usr_id, $scorm_ref_id);
                        }
                    }
                }
            }
        }
    }

    /**
     * Send access notifications
     * @param $usr_id
     * @param $ref_id
     */
    protected function sendCompletedNotifications($usr_id, $ref_id)
    {
        $this->plugin->includeClass("class.ilUFreibPsyNotification.php");
        foreach (ilUFreibPsyNotification::_query(ilUFreibPsyNotiPlugin::EVENT_TYPE_SCORM_COMPLETED, $ref_id) as $noti) {
            $this->sendNotification($noti, $usr_id);
        }
    }

    /**
     * Send notification
     * @param ilUFreibPsyNotification $noti
     * @param int $usr_id use triggering the event (student), NOT the recipient
     */
    public function sendNotification($noti, $usr_id)
    {
        $this->plugin->includeClass("class.ilUFreibPsyNotiRecipientsManager.php");
        $recipients_manager = new ilUFreibPsyNotiRecipientsManager();
        $recipient_ids = $recipients_manager->getRecipientsForNotification($noti, $usr_id);
        foreach ($recipient_ids as $recipient_id) {
            $this->sendMail($recipient_id, $noti->getSubject(), $noti->getText());
        }
    }

    /**
     * @param int $recipient_id
     * @param string $subject
     * @param string $message
     */
    protected function sendMail($recipient_id, $subject, $message)
    {
        $umail = new ilFormatMail(ANONYMOUS_USER_ID);
        $purifier = new ilMailBodyPurifier();
        $mailBody = new ilMailBody($message, $purifier);
        $sanitizedMessage = $mailBody->getContent();

        $mailer = $umail
            ->withContextId('')
            ->withContextParameters([]);
        $mailer->setSaveInSentbox(false);
        $email = trim(ilObjUser::_lookupEmail((int) $recipient_id));
        //$email = trim(ilObjUser::_lookupLogin((int) $recipient_id));
        if ($email != "") {
            $this->log->debug("----*** sent to $email -".$subject);
            $mailer->enqueue(
                ilUtil::securePlainString($email),
                "",
                "",
                ilUtil::securePlainString($subject),
                $sanitizedMessage,
                [],
                null
            );
        }
    }
}