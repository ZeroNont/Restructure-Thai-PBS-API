<?php

namespace App\Libraries;

use Illuminate\Support\Facades\DB;

class Meeting
{

    public static function existMeeting(int $id): bool
    {
        $exist = DB::connection('main')->select('SELECT meeting_id FROM meetings WHERE meeting_id = :meeting_id LIMIT 1', [
            'meeting_id' => $id
        ]);
        return ($exist) ? true : false;
    }

    public static function existTopic(int $id): bool
    {
        $exist = DB::connection('main')->select('SELECT topic_id FROM topics WHERE topic_id = :topic_id LIMIT 1', [
            'topic_id' => $id
        ]);
        return ($exist) ? true : false;
    }
}
