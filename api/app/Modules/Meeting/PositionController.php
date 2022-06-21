<?php

namespace App\Modules\Meeting;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Libraries\Response;
use App\Libraries\Util;

class PositionController extends Controller
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

            $this->clean(['name']);

            // @0 Validating
            $validator = Validator::make($this->req->all(), [
                'meeting_id' => Util::rule('Meeting', true, 'primary'),
                'name' => Util::rule('Meeting', true, 'text.title')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // @1 Existing Meeting
            $exist = DB::connection('main')->select('SELECT * FROM meetings WHERE meeting_id = :meeting_id', [
                'meeting_id' => $this->req->input('meeting_id')
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }

            // @2 Insert
            $no = DB::connection('main')->select('SELECT order_no FROM positions WHERE meeting_id = :meeting_id ORDER BY order_no DESC LIMIT 1', [
                'meeting_id' => $this->req->input('meeting_id')
            ]);
            $no = (!$no) ? 1 : ((int) $no[0]->order_no + 1);
            $id = DB::connection('main')->table('positions')->insertGetId([
                'created_at' => Util::now(),
                'meeting_id' => (int) $this->req->input('meeting_id'),
                'name' => $this->req->input('name'),
                'order_no' => $no,
                'allowance' => 0
            ], 'pos_id');

            $res->set('CREATED', [
                'pos_id' => $id
            ]);
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }
    public function list($id) // Meeting ID
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $validator = Validator::make(['meeting_id' => $id], [
                'meeting_id' => Util::rule('Meeting', true, 'primary')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            $data = [];
            $temp = DB::connection('main')->select('SELECT * FROM positions WHERE meeting_id = :meeting_id ORDER BY order_no ASC', [
                'meeting_id' => $id
            ]);
            foreach ($temp as $row) {
                $data[] = [
                    'pos_id' => $row->pos_id,
                    'name' => $row->name,
                    'order_no' => (int) $row->order_no,
                    'allowance' => (float) $row->allowance
                ];
            }

            // @#
            $res->set('OK', $data);
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function update($id) // Position ID
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            $this->clean(['name']);
            $validator = Validator::make([...$this->req->all(), 'pos_id' => $id], [
                'pos_id' => Util::rule('Meeting', true, 'primary'),
                'name' => Util::rule('Meeting', true, 'text.title'),
                'order_no' => Util::rule('Meeting', true, 'number.no')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            $exist = DB::connection('main')->select('SELECT * FROM positions WHERE pos_id = :pos_id', [
                'pos_id' => $id
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }

            DB::connection('main')->table('positions')->where('pos_id', $id)->update([
                'updated_at' => Util::now(),
                'name' => $this->req->input('name'),
                'order_no' => (int) $this->req->input('order_no')
            ]);
            // @#
            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function delete($id) // Position ID
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            $validator = Validator::make(['pos_id' => $id], [
                'pos_id' => Util::rule('Meeting', true, 'primary')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // Existing Position
            $position = DB::connection('main')->select('SELECT * FROM positions WHERE pos_id = :pos_id', [
                'pos_id' => $id
            ]);
            if (!$position) {
                $res->set('EXIST');
                throw new Exception();
            }

            // Existing Attendee
            $attendee = DB::connection('main')->select('SELECT * FROM attendees WHERE pos_id = :pos_id', [
                'pos_id' => $id
            ]);
            if ($attendee) {
                $res->set('PROGRESS');
                throw new Exception();
            }

            // Delete
            DB::connection('main')->table('positions')->where('pos_id', $id)->delete();

            // @#
            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }
}
