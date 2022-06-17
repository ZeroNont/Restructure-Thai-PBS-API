<?php

namespace App\Modules\Meeting;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Libraries\Response;
use App\Libraries\Util;

class AttendeeController extends Controller
{

    private const DEFAULT_STATUS = 'WAIT'; // Wait

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    // API

    public function create()
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            $this->clean(['out_full_name', 'out_rank', 'out_institution']);

            // @0 Validating
            $schema = [
                'pos_id' => Util::rule(true, 'primary'),
                'group_code' => 'required|in:INSIDER,OUTSIDER'
            ];
            if ($this->req->input('group_code') === 'INSIDER') {
                $schema = [
                    ...$schema,
                    'user_id' => Util::rule(true, 'primary'),
                    'is_access' => 'required|boolean'
                ];
            } else {
                $schema = [
                    ...$schema,
                    'out_email' => Util::rule(true, 'user.email'),
                    'out_full_name' => Util::rule(true, 'text.name'),
                    'out_rank' => Util::rule(false, 'text.title'),
                    'out_institution' => Util::rule(false, 'text.title')
                ];
            }
            $validator = Validator::make($this->req->all(), $schema);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // @1 Existing Position
            $position = DB::connection('main')->select('SELECT * FROM positions WHERE pos_id = :pos_id', [
                'pos_id' => $this->req->input('pos_id')
            ]);
            if (!$position) {
                $res->set('EXIST');
                throw new Exception();
            }

            // @2 Insider Case Existing User
            $reference = null;
            if ($this->req->input('group_code') === 'INSIDER') {

                $user = DB::connection('main')->select('SELECT * FROM users WHERE user_id = :user_id', [
                    'user_id' => $this->req->input('user_id')
                ]);
                if (!$user) {
                    $res->set('EXIST');
                    throw new Exception();
                }

                // Checking Duplicated
                $duplicate = DB::connection('main')->select('SELECT * FROM attendees WHERE pos_id = :pos_id AND user_id = :user_id LIMIT 1', [
                    'pos_id' => $this->req->input('pos_id'),
                    'user_id' => $this->req->input('user_id')
                ]);
            } else {

                // Checking Duplicated
                $duplicate = DB::connection('main')->select('SELECT * FROM attendees WHERE pos_id = :pos_id AND out_email = :out_email LIMIT 1', [
                    'pos_id' => $this->req->input('pos_id'),
                    'out_email' => $this->req->input('out_email')
                ]);

                $reference = Util::genStr('ATTENDEE');
            }
            // @3 Duplicated
            if ($duplicate) {
                $res->set('EXIST');
                throw new Exception();
            }

            // @4 Insert
            $id = DB::connection('main')->table('attendees')->insertGetId([
                'created_at' => Util::now(),
                'user_id' => ($this->req->input('group_code') === 'INSIDER') ? $this->req->input('user_id') : null,
                'pos_id' => $this->req->input('pos_id'),
                'status_code' => self::DEFAULT_STATUS,
                'is_access' => ($this->req->input('group_code') === 'OUTSIDER') ? false : (bool) $this->req->input('is_access'),
                // Outsider
                'out_email' => ($this->req->input('group_code') === 'OUTSIDER') ? Util::trim($this->req->input('out_email')) : null,
                'out_ref_code' => $reference,
                'out_full_name' => ($this->req->input('group_code') === 'OUTSIDER') ? Util::trim($this->req->input('out_full_name')) : null,
                'out_rank' => ($this->req->input('group_code') === 'OUTSIDER') ? Util::trim($this->req->input('out_rank')) : null,
                'out_institution' => ($this->req->input('group_code') === 'OUTSIDER') ? Util::trim($this->req->input('out_institution')) : null
            ], 'attendee_id');

            $res->set('CREATED', [
                'attendee_id' => $id
            ]);
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function delete($id) // Attendee ID
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            if (!is_numeric($id)) {
                $res->set('INPUT');
                throw new Exception();
            }

            $exist = DB::connection('main')->select('SELECT * FROM attendees WHERE attendee_id = :attendee_id', [
                'attendee_id' => $id
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }
            if ($exist[0]->status_code !== 'WAIT') {
                $res->set('PROGRESS');
                throw new Exception();
            }
            DB::connection('main')->table('attendees')->where('attendee_id', $id)->delete();

            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    // Access Status for Insider
    public function access($id) // Attendee ID
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            $validator = Validator::make([...$this->req->all(), 'attendee_id' => $id], [
                'attendee_id' => Util::rule(true, 'primary'),
                'is_access' => 'required|boolean'
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            $exist = DB::connection('main')->select('SELECT * FROM attendees WHERE attendee_id = :attendee_id AND user_id IS NOT NULL', [
                'attendee_id' => $id
            ]);
            if (!$exist) {
                $res->set('PROGRESS');
                throw new Exception();
            }

            DB::connection('main')->table('attendees')->where('attendee_id', $id)->update([
                'updated_at' => Util::now(),
                'is_access' => (bool) $this->req->input('is_access')
            ]);

            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    // Outsider Update
    public function outsider($id) // Attendee ID
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            $this->clean(['out_full_name', 'out_rank', 'out_institution']);
            $validator = Validator::make([...$this->req->all(), 'attendee_id' => $id], [
                'attendee_id' => Util::rule(true, 'primary'),
                'out_full_name' => Util::rule(true, 'text.name'),
                'out_rank' => Util::rule(false, 'text.title'),
                'out_institution' => Util::rule(false, 'text.title')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            $exist = DB::connection('main')->select('SELECT * FROM attendees WHERE attendee_id = :attendee_id AND out_full_name IS NOT NULL', [
                'attendee_id' => $id
            ]);
            if (!$exist) {
                $res->set('PROGRESS');
                throw new Exception();
            }

            DB::connection('main')->table('attendees')->where('attendee_id', $id)->update([
                'updated_at' => Util::now(),
                'out_full_name' => $this->req->input('out_full_name'),
                'out_rank' => $this->req->input('out_rank'),
                'out_institution' => $this->req->input('out_institution')
            ]);

            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }
}
