<?php

class ilUFreibPsyNotification
{
    /**
     * @var ilDBInterface
     */
    protected $db;

    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $event_type;

    /**
     * @var int
     */
    private $recipient_type;

    /**
     * @var int
     */
    private $scorm_ref_id;

    /**
     * @var string
     */
    private $recipient_accounts = null;

    /**
     * @var int
     */
    private $reminder_after_x_days;

    /**
     * @var string
     */
    private $text;

    /**
     * @var string
     */
    private $subject;

    /**
     * ilUFreibPsyNotification constructor.
     * @param int|null $id
     */
    public function __construct(?int $id = null)
    {
        global $DIC;

        $this->db = $DIC->database();
        $this->id = $id;


        if (!empty($this->id)) {
            $this->read();
        }
    }

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id) : void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getEventType() : int
    {
        return $this->event_type;
    }

    /**
     * @param int $event_type
     */
    public function setEventType(int $event_type) : void
    {
        $this->event_type = $event_type;
    }

    /**
     * @return int
     */
    public function getRecipientType() : int
    {
        return $this->recipient_type;
    }

    /**
     * @param int $recipient_type
     */
    public function setRecipientType(int $recipient_type) : void
    {
        $this->recipient_type = $recipient_type;
    }

    /**
     * @return int
     */
    public function getScormRefId() : int
    {
        return $this->scorm_ref_id;
    }

    /**
     * @param int $scorm_ref_id
     */
    public function setScormRefId(int $scorm_ref_id) : void
    {
        $this->scorm_ref_id = $scorm_ref_id;
    }

    /**
     * @return string
     */
    public function getRecipientAccounts() : ?string
    {
        return $this->recipient_accounts;
    }

    /**
     * @param string $recipient_accounts
     */
    public function setRecipientAccounts(string $recipient_accounts) : void
    {
        $this->recipient_accounts = $recipient_accounts;
    }

    /**
     * @return int
     */
    public function getReminderAfterXDays() : int
    {
        return $this->reminder_after_x_days;
    }

    /**
     * @param int $reminder_after_x_days
     */
    public function setReminderAfterXDays(int $reminder_after_x_days) : void
    {
        $this->reminder_after_x_days = $reminder_after_x_days;
    }

    /**
     * @return string
     */
    public function getText() : string
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText(string $text) : void
    {
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function getSubject() : string
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject(string $subject) : void
    {
        $this->subject = $subject;
    }

    protected function read()
    {
        $res = $this->db->query("SELECT * FROM ufreibpsy_notification WHERE notification_id = $this->id");
        while ($row = $this->db->fetchAssoc($res)) {
            $this->event_type               = $row['event_type'];
            $this->recipient_type           = $row['recipient_type'];
            $this->scorm_ref_id             = $row['scorm_ref_id'];
            $this->recipient_accounts       = $row['recipient_accounts'];
            $this->reminder_after_x_days    = $row['reminder_after_x_days'];
            $this->text                     = $row['text'];
            $this->subject                  = $row['subject'];
        }

    }

    public static function lookupNotificationIds()
    {
        global $DIC;

        $db = $DIC->database();

        $notification_ids = array();
        $res = $db->query("SELECT notification_id FROM ufreibpsy_notification");
        while ($row = $db->fetchAssoc($res)) {
            $notification_ids[] = $row['notification_id'];
        }

        return $notification_ids;
    }

    public function create()
    {
        $id = $this->db->nextId("ufreibpsy_notification");

        $quotes = $this->getQuotedValues();
        $event_type         = $quotes["event_type"];
        $recipient_type     = $quotes["recipient_type"];
        $scorm_ref_id       = $quotes["scorm_ref_id"];
        $recipient_accounts = $quotes["recipient_accounts"];
        $reminder_after     = $quotes["reminder_after"];
        $text               = $quotes["text"];
        $subject            = $quotes["subject"];

        $query = "INSERT INTO ufreibpsy_notification (notification_id, event_type, recipient_type, scorm_ref_id,";

        if(!empty($recipient_accounts)) {
            $query .= "recipient_accounts,";
        }

        $query .= "reminder_after_x_days, text, subject) VALUES ($id, $event_type, $recipient_type, $scorm_ref_id,";

        if(!empty($recipient_accounts)) {
            $query .= "$recipient_accounts,";
        }

        $query .= "$reminder_after, $text, $subject)";


        $this->db->manipulate($query);

    }

    public function update()
    {
        $quotes = $this->getQuotedValues();
        $event_type         = $quotes["event_type"];
        $recipient_type     = $quotes["recipient_type"];
        $scorm_ref_id       = $quotes["scorm_ref_id"];
        $recipient_accounts = $quotes["recipient_accounts"];
        $reminder_after     = $quotes["reminder_after"];
        $text               = $quotes["text"];
        $subject            = $quotes["subject"];

        $query = "UPDATE ufreibpsy_notification SET event_type = $event_type, recipient_type = $recipient_type, scorm_ref_id = $scorm_ref_id,";

        if(!empty($recipient_accounts)) {
            $query .= "recipient_accounts = $recipient_accounts,";
        }

        $query .= "reminder_after_x_days = $reminder_after, text = $text, subject = $subject WHERE notification_id = $this->id";

        if(!empty($this->id)) {
            $this->db->manipulate($query);

            // note: better use db->update
            /*
            $db->update(
                "ufreibpsy_notification",
                [
                    "event_type" => ["integer", $this->event_type],
                    "recipient_type" => ["integer", $this->recipient_type],
                    ...
                ],
                [    // where
                     "notification_id" => ["integer", $this->id]
                ]
            );
            */
        }
    }

    public function delete()
    {
        if(!empty($this->id)) {
            $this->db->manipulate("DELETE FROM ufreibpsy_notification WHERE notification_id = $this->id;");
        }
    }

    private function getQuotedValues()
    {
        $quoted_values = [
            "event_type"     => $this->db->quote($this->event_type, "integer"),
            "recipient_type" => $this->db->quote($this->recipient_type, "integer"),
            "scorm_ref_id"   => $this->db->quote($this->scorm_ref_id,"integer"),
            "reminder_after" => $this->db->quote($this->reminder_after_x_days, "integer"),
            "text"           => $this->db->quote($this->text, "text"),
            "subject"        => $this->db->quote($this->subject, "text")
        ];

        if (is_string($this->recipient_accounts)) {
            $quoted_values['recipient_accounts'] = $this->db->quote($this->recipient_accounts, "text");
        } else {
            $quoted_values['recipient_accounts'] = null;
        }

        return $quoted_values;

    }

    /**
     * Note: This is not good practice, better would be a repository class for all db access to
     * ufreibpsy_notification table
     * @param int $event_type
     * @param int $scorm_ref_id
     * @return ilUFreibPsyNotification[]
     */
    static public function _query(int $event_type, int $scorm_ref_id = 0)
    {
        global $DIC;

        $db = $DIC->database();


        $query = "SELECT notification_id FROM ufreibpsy_notification " .
            " WHERE event_type = ".$db->quote($event_type, "integer");

        if ($scorm_ref_id > 0) {
            $query.= " AND scorm_ref_id = ".$db->quote($scorm_ref_id, "integer");
        }

        $set = $db->query($query);
        $notifications = [];
        while ($rec = $db->fetchAssoc($set)) {
            $notifications[] = new self($rec["notification_id"]);
        }

        return $notifications;
    }

}
