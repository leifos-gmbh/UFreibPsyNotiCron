<?php

/* Copyright (c) 1998-2020 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author Alexander Killing <killing@leifos.de>
 */
class ilUFreibPsyNotiRecipientsManager
{
    const COACH_FIELD_NAME = "E-Coaches";

    /**
     * Constructor
     */
    public function __construct()
    {

    }

    /**
     *
     * @param ilUFreibPsyNotification $noti
     * @param int $usr_id
     * @return int[]
     */
    public function getRecipientsForNotification($noti, $usr_id)
    {
        $recipient_ids = [];
        switch ($noti->getRecipientType())
        {
            case ilUFreibPsyNotiPlugin::RECIPIENT_TYPE_STUDENT:
                $recipient_ids = [$usr_id];
                break;

            case ilUFreibPsyNotiPlugin::RECIPIENT_TYPE_ECOACHES:
                $recipient_ids = $this->getCoachesOfStudent($usr_id);
                break;

            case ilUFreibPsyNotiPlugin::RECIPIENT_TYPE_ACCOUNTS:
                $recipient_ids = $this->getIdsForLogins($noti->getRecipientAccounts());
                break;
        }

        return $recipient_ids;
    }

    /**
     * Get ids for logins
     * @param
     * @return
     */
    protected function getIdsForLogins($logins)
    {
        $ids = [];
        $logins = explode(",", $logins);
        foreach ($logins as $login) {
            $id = ilObjUser::_lookupId(trim($login));
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    /**
     * @param
     * @return array
     */
    protected function getCoachesOfStudent($student_id)
    {
        $user = new ilObjUser($student_id);

        $udf_userdata = $user->getUserDefinedData();

        $userDefinedFields = ilUserDefinedFields::_getInstance();
        $udf_definitions = $userDefinedFields->getVisibleDefinitions();

        if(!empty($udf_definitions))
        {
            foreach ($udf_definitions as $udf_key => $udf_definition)
            {
                if($udf_definition["field_name"] === self::COACH_FIELD_NAME)
                {
                    $udf_userdata = $udf_userdata["f_".$udf_key];
                }
            }
        }

        $e_coaches = [];
        if ($udf_userdata) {
            $e_coaches = explode(",", $udf_userdata);
        }

        $coach_ids = array();
        foreach ($e_coaches as $coach_name)
        {
            $coach_id = ilObjUser::_lookupId(trim($coach_name));

            if(!empty($coach_id))
            {
                $coach_ids[] = $coach_id;
            }
        }

        return $coach_ids;
    }


}