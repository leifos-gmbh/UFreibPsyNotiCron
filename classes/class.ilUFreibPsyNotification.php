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
        $event_type     = $this->db->quote($this->event_type, "integer");
        $recipient_type = $this->db->quote($this->recipient_type, "integer");
        $scorm_ref_id   = $this->db->quote($this->scorm_ref_id,"integer");
        if (is_string($this->recipient_accounts)) {
            $recipient_account = $this->db->quote($this->recipient_accounts, "text");
        }
        $reminder_after = $this->db->quote($this->reminder_after_x_days, "integer");
        $text = $this->db->quote($this->text, "text");
        if(!empty($this->id)) {
            $this->db->manipulate("INSERT INTO ufreibpsy_notification (notification_id, event_type, recipient_type, scorm_ref_id, recipient_accounts, reminder_after_x_days, text) 
                                     VALUES ($id, $event_type, $recipient_type, $scorm_ref_id,$recipient_account, $reminder_after, $text)");
        }
    }

    public function update()
    {
        $event_type     = $this->db->quote($this->event_type, "integer");
        $recipient_type = $this->db->quote($this->recipient_type, "integer");
        $scorm_ref_id   = $this->db->quote($this->scorm_ref_id,"integer");
        if (is_string($this->recipient_accounts)) {
            $recipient_account = $this->db->quote($this->recipient_accounts, "text");
        }
        $reminder_after = $this->db->quote($this->reminder_after_x_days, "integer");
        $text = $this->db->quote($this->text, "text");
        if(!empty($this->id)) {
            $this->db->manipulate("UPDATE ufreibpsy_notification
                                     SET event_type = $event_type, recipient_type = $recipient_type, scorm_ref_id = $scorm_ref_id, recipient_accounts = $recipient_account, reminder_after_x_days = $reminder_after, text = $text 
                                     WHERE id = $this->id");
        }
    }

    public function delete()
    {
        if(!empty($this->id) && !empty($this->event_type)) {
            $this->db->manipulate("DELETE FROM ufreibpsy_notification WHERE id = $this->id;");
        }
    }

}