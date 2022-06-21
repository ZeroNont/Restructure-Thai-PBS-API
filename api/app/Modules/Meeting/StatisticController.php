<?php

namespace App\Modules\Meeting;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Libraries\Response;
use App\Libraries\Util;
use App\Libraries\User;
use App\Libraries\FileAttached;
use Illuminate\Support\Facades\Mail;

class StatisticController extends Controller
{

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    //API

    public function type()
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $this->clean(['keyword']);
            $validator = Validator::make($this->req->all(), [
                'years' => Util::rule('Meeting', true, 'date.year'),
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }
            // @3 Fetching
            $data = [];
            $temp = DB::connection('main')->select("SELECT
                        MONTHNAME(started_at) AS month,
                        SUM(CASE WHEN type_code = 'CONSIDER' THEN 1 ELSE 0 END) total_CONSIDER,
                        SUM(CASE WHEN type_code = 'NOTICE' THEN 1 ELSE 0 END) total_NOTICE,
                        SUM(CASE WHEN type_code = 'CONT' THEN 1 ELSE 0 END) total_CONT,
                        COUNT(*) total
                    FROM
                        meetings
                    WHERE
                        YEAR(started_at) = :years
                    GROUP BY
                        MONTHNAME(started_at)", ['years' => $this->req->input('year')]);
            foreach ($temp as $row) {
                $data['meeting'][] = [
                    'name' => $row->month,
                    'CONSIDER' => (int) $row->total_CONSIDER,
                    'NOTICE' => (int) $row->total_NOTICE,
                    'CONT' => (int) $row->total_CONT,
                    'total' => (int) $row->total,
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
    public function status()
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $this->clean(['keyword']);
            $validator = Validator::make($this->req->all(), [
                'years' => Util::rule('Meeting', true, 'date.year'),
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }
            // @3 Fetching
            $data = [];
            $temp = DB::connection('main')->select("SELECT
                        MONTHNAME(started_at) AS month,
                        SUM(CASE WHEN status_code  = 'PROGRESS' THEN 1 ELSE 0 END) total_PROGRESS,
                        SUM(CASE WHEN status_code = 'DONE' THEN 1 ELSE 0 END) total_DONE,
                        SUM(CASE WHEN status_code = 'CREATED' THEN 1 ELSE 0 END) total_CREATED,
                        SUM(CASE WHEN status_code = 'CANCELED' THEN 1 ELSE 0 END) total_CANCELED,
                        COUNT(*) total
                    FROM
                        meetings
                    WHERE
                        YEAR(started_at) = :years
                    GROUP BY
                        MONTHNAME(started_at)", ['years' => $this->req->input('year')]);
            foreach ($temp as $row) {
                $data['meeting'][] = [
                    'name' => $row->month,
                    'PROGRESS' => (int) $row->total_PROGRESS,
                    'DONE' => (int) $row->total_DONE,
                    'CREATED' => (int) $row->total_CREATED,
                    'CANCELED,' => (int) $row->total_CANCELED,
                    'total' => (int) $row->total,
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
    public function resolution()
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $this->clean(['keyword']);
            $validator = Validator::make($this->req->all(), [
                'years' => Util::rule('Meeting', true, 'date.year'),
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }
            // @3 Fetching
            $data = [];
            $temp = DB::connection('main')->select("SELECT
						MONTHNAME(started_at) AS month,
                        SUM(CASE WHEN resolution_code  = 'BOM' THEN 1 ELSE 0 END) total_BOM,
                        SUM(CASE WHEN resolution_code = 'BOC' THEN 1 ELSE 0 END) total_BOC,
                        COUNT(*) total
                    FROM
                    	meetings
                    WHERE
                        YEAR(started_at) = :years
                    GROUP BY
                        month", ['years' => $this->req->input('year')]);
            foreach ($temp as $row) {
                $data['meeting'][] = [
                    'name' => $row->month,
                    'BOM' => (int) $row->total_BOM,
                    'BOC' => (int) $row->total_BOC,
                    'total' => (int) $row->total,
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
    public function department()
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $this->clean(['keyword']);
            $validator = Validator::make($this->req->all(), [
                'years' => Util::rule('Meeting', true, 'date.year'),
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }
            // @3 Fetching
            $data = [];
            $temp = DB::connection('main')->select("SELECT
						u.department ,
                        SUM(CASE WHEN status_code  = 'REJECTED' THEN 1 ELSE 0 END) total_REJECTED,
                        SUM(CASE WHEN status_code = 'JOINED' THEN 1 ELSE 0 END) total_JOINED,
                        SUM(CASE WHEN status_code = 'WAIT' THEN 1 ELSE 0 END) total_WAIT,
                        COUNT(*) total
                    FROM
                        attendees a
                    INNER JOIN
                    	users u
                    ON a.user_id = u.user_id
                    WHERE
                        YEAR(a.created_at) = :years
                    GROUP BY
                        u.department ", ['years' => $this->req->input('year')]);
            foreach ($temp as $row) {
                $data['meeting'][] = [
                    'name' => $row->department,
                    'REJECTED' => (int) $row->total_REJECTED,
                    'JOINED' => (int) $row->total_JOINED,
                    'WAIT' => (int) $row->total_WAIT,
                    'total' => (int) $row->total,
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
}
