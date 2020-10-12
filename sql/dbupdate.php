<#1>
<?php
$notification_fields = array(
	'notification_id' => array (
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'event_type' => array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => true
	),
	'recipient_type' => array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => true
	),
	'scorm_ref_id' => array(
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
	),
	'recipient_accounts' => array(
        'type' => 'text',
        'length' => 1000,
        'notnull' => false
	),
	'reminder_after_x_days' => array(
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
	),
	'text' => array(
        'type' => 'clob',
        'notnull' => true
	)
);
$ilDB->createTable("ufreibpsy_notification", $notification_fields);
$ilDB->addPrimaryKey("ufreibpsy_notification", array("notification_id"));
$ilDB->createSequence("ufreibpsy_notification");
?>
<#2>
<?php
$event_fields = array(
    'event_id' => array (
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ),
    'user_id' => array (
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ),
    'scorm_ref_id' => array (
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ),
    'access_since' => array (
        'type' => 'timestamp',
        'notnull' => true
    ),
    'reminder_sent' => array (
        'type' => 'timestamp',
        'notnull' => true
    ),
);

$ilDB->createTable("ufreibpsy_events", $event_fields);
$ilDB->addPrimaryKey("ufreibpsy_events", array("event_id"));
$ilDB->createSequence("ufreibpsy_events");
?>
<#3>
<?php
if (!$ilDB->tableColumnExists('ufreibpsy_notification', 'subject')) {
    $ilDB->addTableColumn('ufreibpsy_notification', 'subject', array(
        'type' => 'text',
        'notnull' => true,
        'length' => 255
    ));
}
?>
