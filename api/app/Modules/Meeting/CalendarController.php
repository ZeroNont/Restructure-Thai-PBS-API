<?php

namespace App\Modules\Meeting;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Libraries\Response;
use App\Libraries\Util;
use App\Libraries\FileAttached;

class CalendarController extends Controller
{

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    // ADMIN CAN SEE ANY OF THE MEETINGS EXCEPT WAITING METHOD WILL DISPLAY MEETINGS THAT YOUR BELONG

    public function today()
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @1 Query
            $script = "SELECT COUNT(ct.meeting_id) total FROM (SELECT m.meeting_id FROM meetings m LEFT OUTER JOIN positions p ON p.meeting_id = m.meeting_id LEFT OUTER JOIN attendees a ON a.pos_id = p.pos_id WHERE m.status_code NOT IN('CANCEL', 'DONE') AND CAST(m.started_at AS DATE) = :today ";
            $blind = [
                'today' => date('Y-m-d')
            ];

            if (in_array($this->req->input('auth_actor_code'), ['SECRET', 'LEADER', 'MEMBER'])) {
                $script .= "AND (a.user_id = :user_id OR a.rep_user_id = :rep_user_id) ";
                $blind['user_id'] = $this->req->input('auth_user_id');
                $blind['rep_user_id'] = $this->req->input('auth_user_id');
            }
            $script .= "GROUP BY m.meeting_id) ct ";
            $temp = DB::connection('main')->select($script, $blind);
            // @#
            $res->set('OK', [
                'today' => $temp[0]->total
            ]);
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
            $validator = Validator::make($this->req->all(), [
                'month' => Util::rule(true, 'date.month'),
                'year' => Util::rule(true, 'date.year')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }
            $range = $this->req->input('year') . '-' . str_pad($this->req->input('month'), 2, '0', STR_PAD_LEFT);

            $script = "SELECT a.status_code, COUNT(a.status_code) total FROM( SELECT m.status_code FROM meetings m LEFT OUTER JOIN positions p ON p.meeting_id = m.meeting_id LEFT OUTER JOIN attendees a ON a.pos_id = p.pos_id WHERE( SUBSTRING(CAST(m.started_at AS CHAR), 1, 7) = :started_at OR SUBSTRING(CAST(m.ended_at AS CHAR), 1, 7) = :ended_at) AND m.status_code IN('CANCEL', 'DONE') ";
            $blind = [
                'started_at' => $range,
                'ended_at' => $range
            ];
            if (in_array($this->req->input('auth_actor_code'), ['SECRET', 'LEADER', 'MEMBER'])) {
                $script .= "AND (a.user_id = :user_id OR a.rep_user_id = :rep_user_id) ";
                $blind['user_id'] = $this->req->input('auth_user_id');
                $blind['rep_user_id'] = $this->req->input('auth_user_id');
            }
            $script .= "GROUP BY m.meeting_id) a GROUP BY a.status_code ";
            $temp = DB::connection('main')->select($script, $blind);

            // @2 Fetching
            $data = [
                'cancel' => 0,
                'done' => 0
            ];
            foreach ($temp as $row) {
                if ($row->status_code === 'CANCEL') {
                    $data['cancel'] = (int) $row->total;
                }
                if ($row->status_code === 'DONE') {
                    $data['done'] = (int) $row->total;
                }
            }

            // @#
            $res->set('OK', $data);
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }
    public function waiting()
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $validator = Validator::make($this->req->all(), [
                'month' => Util::rule(true, 'date.month'),
                'year' => Util::rule(true, 'date.year')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }
            $range = $this->req->input('year') . '-' . str_pad($this->req->input('month'), 2, '0', STR_PAD_LEFT);

            // @1 Query
            $script = "SELECT m.type_code, m.is_secreted, m.meeting_id, m.subject, SUM(CASE WHEN a.status_code = 'WAIT' THEN 1 ELSE 0 END) count_wait, SUM(CASE WHEN a.status_code = 'JOINED' THEN 1 ELSE 0 END) count_joined, SUM(CASE WHEN a.status_code = 'REJECTED' THEN 1 ELSE 0 END) count_rejected, m.started_at, m.ended_at FROM attendees a INNER JOIN positions p ON p.pos_id = a.pos_id INNER JOIN meetings m ON m.meeting_id = p.meeting_id WHERE( SUBSTRING(CAST(m.started_at AS CHAR), 1, 7) = :started_at OR SUBSTRING(CAST(m.ended_at AS CHAR), 1, 7) = :ended_at) AND a.status_code = 'WAIT' ";
            $blind = [
                'started_at' => $range,
                'ended_at' => $range
            ];
            // User Selected
            $script .= "AND (a.user_id = :user_id OR a.rep_user_id = :rep_user_id) ";
            $blind['user_id'] = $this->req->input('auth_user_id');
            $blind['rep_user_id'] = $this->req->input('auth_user_id');

            $script .= "GROUP BY m.meeting_id ORDER BY m.started_at ASC ";
            $temp = DB::connection('main')->select($script, $blind);
            $data = [];

            // @2 Fetching
            foreach ($temp as $row) {
                $data[] = [
                    'meeting_id' => $row->meeting_id,
                    'subject' => $row->subject,
                    'count_wait' => (int) $row->count_wait,
                    'count_joined' => (int) $row->count_joined,
                    'count_rejected' => (int) $row->count_rejected,
                    'count_total' => ((int) $row->count_wait + (int) $row->count_joined + (int) $row->count_rejected),
                    'started_at' => $row->started_at,
                    'ended_at' => $row->ended_at,
                    'tag_color' => Util::calendarColor($row->type_code, (bool) $row->is_secreted)['tag'],
                    'background_color' => Util::calendarColor($row->type_code, (bool) $row->is_secreted)['background']
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
    public function monthly()
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            $this->clean(['keyword']);

            // @0 Validating
            $validator = Validator::make($this->req->all(), [
                'day' => Util::rule(false, 'date.day'),
                'month' => Util::rule(true, 'date.month'),
                'year' => Util::rule(true, 'date.year'),
                'keyword' => Util::rule(false, 'keyword'),
                'type_code' => 'nullable|array',
                'type_code.*' => Util::rule(false, 'meeting.type_code'),
                'join_code' => 'nullable|array',
                'join_code.*' => Util::rule(false, 'filter.calendar.join_code'),
                'status_code' => 'nullable|array',
                'status_code.*' => Util::rule(false, 'filter.calendar.status_code'),
                'is_publish' => 'nullable|array',
                'is_publish.*' => Util::rule(false, 'filter.calendar.is_publish'),
                'is_secreted' => Util::rule(false, 'filter.calendar.is_secreted')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }
            $typeFilter = (empty($this->req->input('type_code'))) ? [] : array_unique($this->req->input('type_code'));
            $joinFilter = (empty($this->req->input('join_code'))) ? [] : array_unique($this->req->input('join_code'));
            $statusFilter = (empty($this->req->input('status_code'))) ? [] : array_unique($this->req->input('status_code'));
            $publishFilter = (empty($this->req->input('is_publish'))) ? [] : array_unique($this->req->input('is_publish'));

            // @1 Query
            $range = $this->req->input('year') . '-' . str_pad($this->req->input('month'), 2, '0', STR_PAD_LEFT);
            $blind = [
                'started_at' => $range,
                'ended_at' => $range
            ];
            $script = "SELECT cu.full_name creator_full_name, m.created_user_id creator_user_id, m.meeting_id, m.status_code, m.subject, m.type_code, m.started_at, m.ended_at, m.is_publish, m.is_secreted, CASE WHEN m.pin IS NULL THEN FALSE ELSE TRUE END has_pin, s.wait attendee_wait, s.joined attendee_joined, s.rejected attendee_rejected,(s.wait + s.joined + s.rejected) attendee_total FROM meetings m INNER JOIN users cu ON cu.user_id = m.created_user_id LEFT OUTER JOIN positions p ON m.meeting_id = p.meeting_id LEFT OUTER JOIN attendees a ON p.pos_id = a.pos_id LEFT OUTER JOIN( SELECT pp.meeting_id, SUM(CASE WHEN aa.status_code = 'WAIT' THEN 1 ELSE 0 END) wait, SUM(CASE WHEN aa.status_code = 'JOINED' THEN 1 ELSE 0 END) joined, SUM(CASE WHEN aa.status_code = 'REJECTED' THEN 1 ELSE 0 END) rejected FROM attendees aa INNER JOIN positions pp ON pp.pos_id = aa.pos_id GROUP BY pp.meeting_id) s ON m.meeting_id = s.meeting_id WHERE (SUBSTRING(CAST(m.started_at AS CHAR), 1, 7) = :started_at OR SUBSTRING(CAST(m.ended_at AS CHAR), 1, 7) = :ended_at) ";
            if (in_array($this->req->input('auth_actor_code'), ['SECRET', 'LEADER', 'MEMBER'])) {
                $script .= "AND (a.user_id = :user_id OR a.rep_user_id = :rep_user_id) ";
                $blind['user_id'] = $this->req->input('auth_user_id');
                $blind['rep_user_id'] = $this->req->input('auth_user_id');
            }
            if (!empty($this->req->input('keyword'))) {
                $script .= 'AND m.subject LIKE :subject ';
                $blind = [...$blind, 'subject' => '%' . $this->req->input('keyword') . '%'];
            }
            if (!empty($typeFilter)) {
                $script .= "AND m.type_code IN ('" . implode("','", $typeFilter) . "') ";
            }
            if (!empty($joinFilter)) {
                $script .= "AND a.status_code IN ('" . implode("','", $joinFilter) . "') ";
            }
            if (!empty($statusFilter)) {
                $convStatusFilter = [];
                foreach ($statusFilter as $value) {
                    if ($value === 'WAIT') {
                        $convStatusFilter[] = 'CREATED';
                        $convStatusFilter[] = 'PROGRESS';
                    } else {
                        $convStatusFilter[] = $value;
                    }
                }
                $script .= "AND m.status_code IN ('" . implode("','", $convStatusFilter) . "') ";
            }
            if (!empty($publishFilter)) {
                $publishFilter = array_map(function ($value): int {
                    return ($value === 'ON') ? 1 : 0;
                }, $publishFilter);
                $script .= "AND m.is_publish IN (" . implode(',', $publishFilter) . ") ";
            }
            if ($this->req->input('is_secreted') === 'ON') {
                $script .= "AND m.is_secreted = 1 ";
            }
            $script .= "GROUP BY m.meeting_id ORDER BY m.started_at ASC ";
            $temp = DB::connection('main')->select($script, $blind);

            // @2 Fetching
            $data = [];
            foreach ($temp as $row) {
                $member = [];
                foreach (DB::connection('main')->select("SELECT mbr.* FROM( SELECT u.full_name, f.upload_id, f.ref_id, f.file_ext, u.user_id FROM meetings m INNER JOIN positions p ON p.meeting_id = m.meeting_id INNER JOIN attendees a ON a.pos_id = p.pos_id INNER JOIN users u ON u.user_id = a.user_id LEFT OUTER JOIN( SELECT * FROM file_uploads WHERE module_code = 'PROFILE') f ON f.ref_id = u.user_id WHERE m.meeting_id = :meeting_id ORDER BY a.attendee_id ASC) mbr GROUP BY mbr.user_id, mbr.upload_id LIMIT 5", ['meeting_id' => $row->meeting_id]) as $img) {
                    $member[] = [
                        'alt' => $img->full_name,
                        'url' => (is_null($img->upload_id)) ? null : FileAttached::url('PROFILE', $img->upload_id, $img->ref_id, $img->file_ext)
                    ];
                }

                $data[substr($row->started_at, 0, 10)][] = [
                    'meeting_id' => $row->meeting_id,
                    'creator_full_name' => $row->creator_full_name,
                    'creator_user_id' => $row->creator_user_id,
                    'subject' => $row->subject,
                    'type_code' => $row->type_code,
                    'status_code' => $row->status_code,
                    'started_at' => $row->started_at,
                    'ended_at' => $row->ended_at,
                    'is_publish' => (bool) $row->is_publish,
                    'is_secreted' => (bool) $row->is_secreted,
                    'has_pin' => (bool) $row->has_pin,
                    'tag_color' => Util::calendarColor($row->type_code, (bool) $row->is_secreted)['tag'],
                    'background_color' => Util::calendarColor($row->type_code, (bool) $row->is_secreted)['background'],
                    'attendees' => [
                        'wait' => (empty($row->attendee_wait)) ? 0 : (int) $row->attendee_wait,
                        'joined' => (empty($row->attendee_joined)) ? 0 : (int) $row->attendee_joined,
                        'rejected' => (empty($row->attendee_rejected)) ? 0 : (int) $row->attendee_rejected,
                        'total' => (empty($row->attendee_total)) ? 0 : (int) $row->attendee_total
                    ],
                    'member' => $member
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
