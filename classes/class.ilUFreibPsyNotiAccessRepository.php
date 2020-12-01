<?php

/* Copyright (c) 1998-2020 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Storing access information for notifications
 * @author Alexander Killing <killing@leifos.de>
 */
class ilUFreibPsyNotiAccessRepository
{
    /**
     * @var \ilDBInterface
     */
    protected $db;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $DIC;

        $this->db = $DIC->database();
    }

    /**
     * @param int $user_id
     * @param int $ref_id
     */
    public function storeAccessGranted(int $user_id, int $ref_id) {
        $db = $this->db;
        
        if (!$this->getAccessGrantedTS($user_id, $ref_id)) {
            $db->replace(
                "ufreibpsy_access_store",
                [        // pk
                         "user_id" => ["integer", $user_id],
                         "scorm_ref_id" => ["integer", $ref_id],
                ],
                [
                    "access_granted_ts" => ["integer", time()]
                ]
            );
        }
    }

    /**
     * @param int $user_id
     * @param int $ref_id
     */
    public function storeCompletedTS(int $user_id, int $ref_id) {
        $db = $this->db;

        $r = $db->update(
            "ufreibpsy_access_store",
            [
                "completed_ts" => ["integer", time()]
            ],
            [ // where
              "user_id" => ["integer", $user_id],
              "scorm_ref_id" => ["integer", $ref_id],
              "completed_ts" => ["integer", 0]
            ]
        );
        return ($r > 0);
    }

    /**
     * Get access granted timestamp
     * @param int $user_id
     * @param int $ref_id
     * @return bool
     */
    public function getAccessGrantedTS(int $user_id, int $ref_id)
    {
        $db = $this->db;

        $set = $db->queryF(
            "SELECT * FROM ufreibpsy_access_store " .
            " WHERE user_id = %s ".
            " AND scorm_ref_id = %s ",
            ["integer", "integer"],
            [$user_id, $ref_id]
        );
        $rec = $db->fetchAssoc($set);
        return $rec["access_granted_ts"];
    }

    /**
     * Get all granted but unnotified
     * @param
     * @return
     */
    public function getUserToNotify($days, $scorm_ref_id)
    {
        $db = $this->db;

        $set = $db->queryF(
            "SELECT * FROM ufreibpsy_access_store " .
            " WHERE access_granted_ts > 0 ".
            " AND reminder_sent_ts = 0 ".
            " AND scorm_ref_id = %s ".
            " AND completed = 0 ",
            ["integer"],
            [$scorm_ref_id]
        );
        $users_to_notify = [];
        $obj_id = ilObject::_lookupObjId($scorm_ref_id);
        while($rec = $db->fetchAssoc($set)) {
            // re-check lp status
            $status = ilLPStatus::_lookupStatus($obj_id, $rec["user_id"]);
            if ($status == ilLPStatus::LP_STATUS_COMPLETED_NUM) {
                $this->storeCompleted($rec["user_id"], $scorm_ref_id);
            } else {
                if ((time() - $rec["access_granted_ts"]) > ($days * 60 * 60 * 24)) {
                    $users_to_notify[] = $rec["user_id"];
                    $this->storeReminderSent($rec["user_id"], $scorm_ref_id);
                }
            }
        }
        return $users_to_notify;
    }

    protected function storeCompleted($user_id, $scorm_ref_id)
    {
        $db = $this->db;

        $db->update(
            "ufreibpsy_access_store",
            [
                "completed" => ["integer", 1]
            ],
            [    // where
                 "scorm_ref_id" => ["integer", $scorm_ref_id],
                 "user_id" => ["integer", $user_id]
            ]
        );
    }

    protected function storeReminderSent($user_id, $scorm_ref_id)
    {
        $db = $this->db;

        $db->update(
            "ufreibpsy_access_store",
            [
                "reminder_sent_ts" => ["integer", time()]
            ],
            [    // where
                 "scorm_ref_id" => ["integer", $scorm_ref_id],
                 "user_id" => ["integer", $user_id]
            ]
        );
    }
}