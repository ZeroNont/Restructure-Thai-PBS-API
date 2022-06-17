<?php

namespace App\Modules\Meeting;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Libraries\Response;
use App\Libraries\Util;
use App\Libraries\Meeting;
use App\Libraries\FileAttached;

class TopicController extends Controller
{

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    // API

    public function create()
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            $this->clean(['subject', 'detail', 'note']);
            $this->req->merge(['topic_no' => Util::rewriteNoVersion($this->req->input('topic_no'))]);

            // @0 Validating
            $validator = Validator::make($this->req->all(), [
                'meeting_id' => Util::rule(true, 'primary'),
                'topic_no' => Util::rule(true, 'meeting.topic_no'),
                'subject' => Util::rule(true, 'text.title'),
                'detail' => Util::rule(false, 'text.paper'),
                'note' => Util::rule(false, 'text.note'),
                'has_vote' => 'required|boolean'
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // @1 Existing Meeting
            if (!Meeting::existMeeting((int) $this->req->input('meeting_id'))) {
                $res->set('EXIST');
                throw new Exception();
            }

            // @2 Checking Duplicated No
            if (!self::createAllowanceNo($this->req->input('topic_no'), (int) $this->req->input('meeting_id'))) {
                $res->set('INPUT');
                throw new Exception();
            }

            // @3 Insert
            $id = DB::connection('main')->table('topics')->insertGetId([
                'created_at' => Util::now(),
                'meeting_id' => $this->req->input('meeting_id'),
                'topic_no' => $this->req->input('topic_no'),
                'subject' => $this->req->input('subject'),
                'detail' => $this->req->input('detail'),
                'note' => $this->req->input('note'),
                'has_vote' => (bool) $this->req->input('has_vote'),
                'is_passed' => false
            ], 'topic_id');

            $res->set('CREATED', [
                'topic_id' => $id
            ]);
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function delete($id) // Topic ID
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $validator = Validator::make(['topic_id' => $id], [
                'topic_id' => Util::rule(true, 'primary')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // @1 Existing Topic
            $exist = DB::connection('main')->select('SELECT * FROM topics WHERE topic_id = :topic_id', [
                'topic_id' => $id
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }

            // Existing Sub-Topic
            $sub = DB::connection('main')->select('SELECT * FROM topics WHERE meeting_id = :meeting_id AND topic_no LIKE :topic_no', [
                'meeting_id' => $exist[0]->meeting_id,
                'topic_no' => $exist[0]->topic_no . '.%'
            ]);
            if ($sub) {
                $res->set('PROGRESS');
                throw new Exception();
            }

            // Delete
            DB::connection('main')->table('topics')->where('topic_id', $id)->delete();
            FileAttached::deleteModule('TOPIC', (int) $id);

            // Sorting Topic No
            // ...

            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function update($id) // Topic ID
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $this->clean(['subject', 'detail', 'note']);
            $this->req->merge(['topic_no' => Util::rewriteNoVersion($this->req->input('topic_no'))]);
            $validator = Validator::make([...$this->req->all(), 'topic_id' => $id], [
                'topic_id' => Util::rule(true, 'primary'),
                'topic_no' => Util::rule(true, 'meeting.topic_no'),
                'subject' => Util::rule(true, 'text.title'),
                'detail' => Util::rule(false, 'text.paper'),
                'note' => Util::rule(false, 'text.note'),
                'has_vote' => 'required|boolean'
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // @1 Existing Topic
            $exist = DB::connection('main')->select('SELECT * FROM topics WHERE topic_id = :topic_id', [
                'topic_id' => $id
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }

            // Condition Checking
            // ...

            // Update
            DB::connection('main')->table('topics')->where('topic_id', $id)->update([
                'updated_at' => Util::now(),
                'topic_no' => $this->req->input('topic_no'),
                'subject' => $this->req->input('subject'),
                'detail' => $this->req->input('detail'),
                'note' => $this->req->input('note'),
                'has_vote' => (bool) $this->req->input('has_vote')
            ]);

            // Sorting Topic No
            // ...

            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function content($id) // Topic ID
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $validator = Validator::make(['topic_id' => $id], [
                'topic_id' => Util::rule(true, 'primary')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // @1 Existing Topic
            $exist = DB::connection('main')->select('SELECT * FROM topics WHERE topic_id = :topic_id', [
                'topic_id' => $id
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }

            $attached = [];
            foreach (FileAttached::getModule('TOPIC', (int) $id) as $file) {
                $attached[] = [
                    'upload_id' => $file['upload_id'],
                    'file_ext' => $file['file_ext'],
                    'title' => $file['title'],
                    'origin_name' => $file['origin_name'],
                    'url' => $file['url']
                ];
            }

            $res->set('OK', [
                'topic_id' => $exist[0]->topic_id,
                'topic_no' => $exist[0]->topic_no,
                'subject' => $exist[0]->subject,
                'detail' => $exist[0]->detail,
                'note' => $exist[0]->note,
                'has_vote' => (bool) $exist[0]->has_vote,
                'is_passed' => (bool) $exist[0]->is_passed,
                'attached_file' => $attached
            ]);
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    // Function

    private static function createAllowanceNo(string $no, int $meeting): bool
    {

        $currentArr = explode('.', $no);
        if (in_array('0', $currentArr)) {
            return false;
        }

        $beforeArr = $currentArr;
        if ((((int) $beforeArr[sizeof($beforeArr) - 1]) - 1) === 0) {
            unset($beforeArr[sizeof($beforeArr) - 1]);
        } else {
            $beforeArr[sizeof($beforeArr) - 1] = ((int) $beforeArr[sizeof($beforeArr) - 1]) - 1;
        }
        $beforeNo = (sizeof($beforeArr) === 0) ? null : implode('.', $beforeArr);
        unset($currentArr, $beforeArr);

        // Duplicated
        $existCurrentNo = DB::connection('main')->select('SELECT * FROM topics WHERE meeting_id = :meeting_id AND topic_no = :topic_no', [
            'meeting_id' => $meeting,
            'topic_no' => $no
        ]);
        if ($existCurrentNo) {
            return false;
        }

        // Parent
        if (!is_null($beforeNo)) {
            $existBeforeNo = DB::connection('main')->select('SELECT * FROM topics WHERE meeting_id = :meeting_id AND topic_no = :topic_no', [
                'meeting_id' => $meeting,
                'topic_no' => $beforeNo
            ]);
            if (!$existBeforeNo) {
                return false;
            }
        }

        return true;
    }
}
