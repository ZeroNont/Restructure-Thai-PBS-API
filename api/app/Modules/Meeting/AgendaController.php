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
use Illuminate\Support\Facades\Mail;
use App\Mail\MeetingMail;


class AgendaController extends Controller
{

    private const FIRST_STATUS = 'CREATED';

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    // API

    public function create()
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            $this->clean(['subject', 'address', 'url', 'pin', 'meeting_code']);

            // @0 Validating
            $validator = Validator::make($this->req->all(), [
                'subject' => Util::rule('Meeting', true, 'text.title'),
                'resolution_code' => Util::rule('Meeting', false, 'meeting.resolution_code'),
                'type_code' => Util::rule('Meeting', true, 'meeting.type_code'),
                'is_publish' => 'required|boolean',
                'is_secreted' => 'required|boolean',
                'started_at' => Util::rule('Meeting', true, 'date.started_at'),
                'ended_at' => Util::rule('Meeting', true, 'date.ended_at'),
                'address' => Util::rule('Meeting', false, 'text.note'),
                'url' => 'nullable|url',
                'period' => Util::rule('Meeting', false, 'number.no'),
                'annual' => Util::rule('Meeting', false, 'date.year'),
                'pin' => Util::rule('Meeting', false, 'number.pin'),
                'tag_id' => Util::rule('Meeting', false, 'primary'),
                'meeting_code' => Util::rule('Meeting', false, 'meeting.meeting_code')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // Existing Tag ID
            if (!empty($this->req->input('tag_id'))) {
                if (!DB::connection('main')->select("SELECT meeting_id FROM meetings WHERE meeting_id = :tag_id AND status_code != 'CANCELED' LIMIT 1", ['tag_id' => $this->req->input('tag_id')])) {
                    $res->set('EXIST');
                    throw new Exception();
                }
            }

            $id = DB::connection('main')->table('meetings')->insertGetId([
                'created_at' => Util::now(),
                'created_user_id' => $this->req->input('auth_user_id'),
                'tag_id' => (empty($this->req->input('tag_id'))) ? null : (int) $this->req->input('tag_id'),
                'meeting_code' => (empty($this->req->input('meeting_code'))) ? null : $this->req->input('meeting_code'),
                'status_code' => self::FIRST_STATUS,
                'type_code' => $this->req->input('type_code'),
                'resolution_code' => $this->req->input('resolution_code'),
                'is_publish' => $this->req->input('is_publish'),
                'is_secreted' => $this->req->input('is_secreted'),
                'subject' => $this->req->input('subject'),
                'started_at' => $this->req->input('started_at'),
                'ended_at' => $this->req->input('ended_at'),
                'address' => Util::trim($this->req->input('address')),
                'url' => Util::trim($this->req->input('url')),
                'period' => (empty($this->req->input('period'))) ? null : (int) $this->req->input('period'),
                'annual' => (empty($this->req->input('annual'))) ? null : (int) $this->req->input('annual'),
                'pin' => Util::trim($this->req->input('pin'))
            ], 'meeting_id');

            $res->set('CREATED', [
                'meeting_id' => $id
            ]);
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function clone()
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $validator = Validator::make($this->req->all(), [
                'meeting_id' => Util::rule('Meeting', true, 'primary')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // Existing Meeting
            $meeting = DB::connection('main')->select('SELECT * FROM meetings WHERE meeting_id = :meeting_id LIMIT 1', ['meeting_id' => $this->req->input('meeting_id')]);
            if (!$meeting) {
                $res->set('EXIST');
                throw new Exception();
            }
            $topic = DB::connection('main')->select('SELECT * FROM topics WHERE meeting_id = :meeting_id', ['meeting_id' => $this->req->input('meeting_id')]);
            $position = DB::connection('main')->select('SELECT * FROM positions WHERE meeting_id = :meeting_id', ['meeting_id' => $this->req->input('meeting_id')]);

            // Inserting
            $id = DB::connection('main')->table('meetings')->insertGetId([
                'created_at' => Util::now(),
                'created_user_id' => $this->req->input('auth_user_id'),
                'tag_id' => $meeting[0]->tag_id,
                'meeting_code' => Util::genStr('MEETING'),
                'status_code' => self::FIRST_STATUS,
                'type_code' => $meeting[0]->type_code,
                'resolution_code' => $meeting[0]->resolution_code,
                'priority_level' => $meeting[0]->priority_level,
                'is_publish' => false,
                'is_secreted' => false,
                'subject' => $meeting[0]->subject,
                'detail' => $meeting[0]->detail,
                'note' => $meeting[0]->note,
                'started_at' => $meeting[0]->started_at,
                'ended_at' => $meeting[0]->ended_at,
                'address' => $meeting[0]->address,
                'url' => $meeting[0]->url,
                'period' => $meeting[0]->period,
                'annual' => $meeting[0]->annual,
                'pin' => null
            ], 'meeting_id');

            // Topic
            $data = [];
            foreach ($topic as $row) {
                $data[] = [
                    'created_at' => Util::now(),
                    'meeting_id' => $id,
                    'topic_no' => $row->topic_no,
                    'subject' => $row->subject,
                    'detail' => $row->detail,
                    'note' => $row->note,
                    'has_vote' => (bool) $row->has_vote,
                    'is_passed' => false
                ];
            }
            DB::connection('main')->table('topics')->insert($data);

            // Position
            $data = [];
            foreach ($position as $row) {
                $data[] = [
                    'created_at' => Util::now(),
                    'meeting_id' => $id,
                    'name' => $row->name,
                    'order_no' => $row->order_no,
                    'allowance' => $row->allowance
                ];
            }
            DB::connection('main')->table('positions')->insert($data);
            unset($data);

            $res->set('CREATED', [
                'meeting_id' => $id
            ]);
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function contentOutsider($id, $reference)
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $validator = Validator::make(['meeting_id' => $id, 'out_ref_code' => $reference], [
                'meeting_id' => Util::rule('Meeting', true, 'primary'),
                'out_ref_code' => Util::rule('Meeting', true, 'meeting.reference')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // Checking Permission Reference
            $hasAccess = DB::connection('main')->select('SELECT a.attendee_id, a.status_code FROM attendees a INNER JOIN positions p ON a.pos_id = p.pos_id INNER JOIN meetings m ON m.meeting_id = p.meeting_id WHERE m.meeting_id = :meeting_id AND a.out_email IS NOT NULL AND a.out_ref_code = :out_ref_code LIMIT 1', [
                'meeting_id' => $id,
                'out_ref_code' => $reference,
            ]);
            if (!$hasAccess) {
                $res->set('ACCESS');
                throw new Exception();
            }

            // @1 Meeting
            $meeting = DB::connection('main')->select('SELECT m.*, t.subject tag_subject FROM meetings m LEFT OUTER JOIN meetings t ON m.tag_id = t.meeting_id WHERE m.meeting_id = :meeting_id', [
                'meeting_id' => $id
            ]);
            if (!$meeting) {
                $res->set('EXIST');
                throw new Exception();
            }
            $meeting = $meeting[0];

            $data = [
                'tag_subject' => $meeting->tag_subject,
                'created_at' => $meeting->created_at,
                'updated_at' => $meeting->updated_at,
                'meeting_code' => $meeting->meeting_code,
                'status_code' => $meeting->status_code,
                'type_code' => $meeting->type_code,
                'resolution_code' => $meeting->resolution_code,
                'priority_level' => $meeting->priority_level,
                'is_publish' => (bool) $meeting->is_publish,
                'is_secreted' => (bool) $meeting->is_secreted,
                'subject' => $meeting->subject,
                'detail' => $meeting->detail,
                'note' => $meeting->note,
                'started_at' => $meeting->started_at,
                'ended_at' => $meeting->ended_at,
                'address' => $meeting->address,
                'url' => $meeting->url,
                'period' => $meeting->period,
                'annual' => $meeting->annual,
                'tag_color' => Util::calendarColor($meeting->type_code, (bool) $meeting->is_secreted)['tag'],
                'background_color' => Util::calendarColor($meeting->type_code, (bool) $meeting->is_secreted)['background'],
                'topic' => [],
                'position' => [],
                'join_code' => $hasAccess[0]->status_code
            ];

            // @2 Topic
            $temp = [];
            $topic = DB::connection('main')->select('SELECT detail, topic_id, topic_no, subject, has_vote, is_passed FROM topics WHERE meeting_id = :meeting_id ORDER BY topic_no ASC', [
                'meeting_id' => $id
            ]);
            $index = 0;
            foreach ($topic as $row) {
                $temp[$index] = [
                    'topic_no' => $row->topic_no,
                    'subject' => $row->subject,
                    'detail' => strip_tags($row->detail),
                    'has_vote' => (bool) $row->has_vote,
                    'is_passed' => (bool) $row->is_passed,
                    'attached_file' => []
                ];
                foreach (FileAttached::getModule('TOPIC', $row->topic_id) as $file) {
                    $temp[$index]['attached_file'][] = [
                        'upload_id' => $file['upload_id'],
                        'file_ext' => $file['file_ext'],
                        'title' => $file['title'],
                        'origin_name' => $file['origin_name'],
                        'url' => $file['url']
                    ];
                }
                $index++;
            }
            $data['topic'] = $temp;
            unset($topic, $temp, $index);

            // @3 Position
            $temp = [];
            $position = DB::connection('main')->select('SELECT * FROM positions WHERE meeting_id = :meeting_id ORDER BY order_no ASC', [
                'meeting_id' => $id
            ]);
            foreach ($position as $pos) {
                $att = [];
                $attendee = DB::connection('main')->select("SELECT r.department rep_department, u.department att_department, u.institution att_institution, r.institution rep_institution, u.rank att_rank, r.rank rep_rank, u.email att_email, r.email rep_email, u.full_name att_full_name, r.full_name rep_full_name, a.*, fu.upload_id att_upload_id, fu.ref_id att_ref_id, fu.file_ext att_file_ext, fr.upload_id rep_upload_id, fr.ref_id rep_ref_id, fr.file_ext rep_file_ext FROM attendees a LEFT OUTER JOIN users u ON a.user_id = u.user_id LEFT OUTER JOIN users r ON a.rep_user_id = r.user_id LEFT OUTER JOIN(SELECT * FROM file_uploads WHERE module_code = 'PROFILE') fu ON u.user_id = fu.ref_id LEFT OUTER JOIN(SELECT * FROM file_uploads WHERE module_code = 'PROFILE') fr ON r.user_id = fr.ref_id WHERE a.pos_id = :pos_id ORDER BY a.user_id DESC, a.rep_user_id DESC, a.attendee_id ASC", [
                    'pos_id' => $pos->pos_id
                ]);
                // Attendee
                foreach ($attendee as $atr) {
                    $att[] = [
                        'status_code' => $atr->status_code,
                        'is_access' => (bool) $atr->is_access,
                        'is_insider' => (is_null($atr->user_id)) ? false : true,
                        'has_represent' => (is_null($atr->rep_user_id)) ? false : true,
                        // Attendee
                        'user_id' => $atr->user_id,
                        'att_full_name' => $atr->att_full_name,
                        'att_email' => $atr->att_email,
                        'att_rank' => $atr->att_rank,
                        'att_institution' => $atr->att_institution,
                        'att_department' => $atr->att_department,
                        'att_url' => (is_null($atr->att_upload_id)) ? null : FileAttached::url('PROFILE', $atr->att_upload_id, $atr->att_ref_id, $atr->att_file_ext),
                        // Represent
                        'rep_user_id' => $atr->rep_user_id,
                        'rep_full_name' => $atr->rep_full_name,
                        'rep_email' => $atr->rep_email,
                        'rep_rank' => $atr->rep_rank,
                        'rep_institution' => $atr->rep_institution,
                        'rep_department' => $atr->rep_department,
                        'rep_url' => (is_null($atr->rep_upload_id)) ? null : FileAttached::url('PROFILE', $atr->rep_upload_id, $rep->att_ref_id, $rep->att_file_ext),
                        // Outsider
                        'out_full_name' => $atr->out_full_name,
                        'out_email' => $atr->out_email,
                        'out_rank' => $atr->out_rank,
                        'out_institution' => $atr->out_institution
                    ];
                }
                $temp[] = [
                    'name' => $pos->name,
                    'order_no' => $pos->order_no,
                    'allowance' => $pos->allowance,
                    'attendee' => $att
                ];
            }
            $data['position'] = $temp;
            unset($position, $attendee, $temp);

            // @#
            $res->set('OK', $data);
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function content($id)
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            if (!is_numeric($id)) {
                $res->set('INPUT');
                throw new Exception();
            }

            // @1 Meeting
            $meeting = DB::connection('main')->select('SELECT m.*, t.subject tag_subject FROM meetings m LEFT OUTER JOIN meetings t ON m.tag_id = t.meeting_id WHERE m.meeting_id = :meeting_id', [
                'meeting_id' => $id
            ]);
            if (!$meeting) {
                $res->set('EXIST');
                throw new Exception();
            }
            $meeting = $meeting[0];

            $data = [
                'meeting_id' => $meeting->meeting_id,
                'tag_id' => $meeting->tag_id,
                'tag_subject' => $meeting->tag_subject,
                'created_at' => $meeting->created_at,
                'updated_at' => $meeting->updated_at,
                'meeting_code' => $meeting->meeting_code,
                'status_code' => $meeting->status_code,
                'type_code' => $meeting->type_code,
                'resolution_code' => $meeting->resolution_code,
                'priority_level' => $meeting->priority_level,
                'is_publish' => (bool) $meeting->is_publish,
                'is_secreted' => (bool) $meeting->is_secreted,
                'subject' => $meeting->subject,
                'detail' => $meeting->detail,
                'note' => $meeting->note,
                'started_at' => $meeting->started_at,
                'ended_at' => $meeting->ended_at,
                'address' => $meeting->address,
                'url' => $meeting->url,
                'period' => $meeting->period,
                'annual' => $meeting->annual,
                'pin' => $meeting->pin,
                'tag_color' => Util::calendarColor($meeting->type_code, (bool) $meeting->is_secreted)['tag'],
                'background_color' => Util::calendarColor($meeting->type_code, (bool) $meeting->is_secreted)['background'],
                'topic' => [],
                'position' => []
            ];

            // @2 Topic
            $temp = [];
            $topic = DB::connection('main')->select('SELECT detail, topic_id, topic_no, subject, has_vote, is_passed FROM topics WHERE meeting_id = :meeting_id ORDER BY topic_no ASC', [
                'meeting_id' => $id
            ]);
            $index = 0;
            foreach ($topic as $row) {
                $temp[$index] = [
                    'topic_id' => $row->topic_id,
                    'topic_no' => $row->topic_no,
                    'subject' => $row->subject,
                    'detail' => strip_tags($row->detail),
                    'has_vote' => (bool) $row->has_vote,
                    'is_passed' => (bool) $row->is_passed,
                    'attached_file' => []
                ];
                foreach (FileAttached::getModule('TOPIC', $row->topic_id) as $file) {
                    $temp[$index]['attached_file'][] = [
                        'upload_id' => $file['upload_id'],
                        'file_ext' => $file['file_ext'],
                        'title' => $file['title'],
                        'origin_name' => $file['origin_name'],
                        'url' => $file['url']
                    ];
                }
                $index++;
            }
            $data['topic'] = $temp;
            unset($topic, $temp, $index);

            // @3 Position
            $temp = [];
            $position = DB::connection('main')->select('SELECT * FROM positions WHERE meeting_id = :meeting_id ORDER BY order_no ASC', [
                'meeting_id' => $id
            ]);
            foreach ($position as $pos) {
                $att = [];
                $attendee = DB::connection('main')->select("SELECT r.department rep_department, u.department att_department, u.institution att_institution, r.institution rep_institution, u.rank att_rank, r.rank rep_rank, u.email att_email, r.email rep_email, u.full_name att_full_name, r.full_name rep_full_name, a.*, fu.upload_id att_upload_id, fu.ref_id att_ref_id, fu.file_ext att_file_ext, fr.upload_id rep_upload_id, fr.ref_id rep_ref_id, fr.file_ext rep_file_ext FROM attendees a LEFT OUTER JOIN users u ON a.user_id = u.user_id LEFT OUTER JOIN users r ON a.rep_user_id = r.user_id LEFT OUTER JOIN(SELECT * FROM file_uploads WHERE module_code = 'PROFILE') fu ON u.user_id = fu.ref_id LEFT OUTER JOIN(SELECT * FROM file_uploads WHERE module_code = 'PROFILE') fr ON r.user_id = fr.ref_id WHERE a.pos_id = :pos_id ORDER BY a.user_id DESC, a.rep_user_id DESC, a.attendee_id ASC", [
                    'pos_id' => $pos->pos_id
                ]);
                // Attendee
                foreach ($attendee as $atr) {
                    $att[] = [
                        'attendee_id' => $atr->attendee_id,
                        'status_code' => $atr->status_code,
                        'is_access' => (bool) $atr->is_access,
                        'is_insider' => (is_null($atr->user_id)) ? false : true,
                        'has_represent' => (is_null($atr->rep_user_id)) ? false : true,
                        // Attendee
                        'user_id' => $atr->user_id,
                        'att_full_name' => $atr->att_full_name,
                        'att_email' => $atr->att_email,
                        'att_rank' => $atr->att_rank,
                        'att_institution' => $atr->att_institution,
                        'att_department' => $atr->att_department,
                        'att_url' => (is_null($atr->att_upload_id)) ? null : FileAttached::url('PROFILE', $atr->att_upload_id, $atr->att_ref_id, $atr->att_file_ext),
                        // Represent
                        'rep_user_id' => $atr->rep_user_id,
                        'rep_full_name' => $atr->rep_full_name,
                        'rep_email' => $atr->rep_email,
                        'rep_rank' => $atr->rep_rank,
                        'rep_institution' => $atr->rep_institution,
                        'rep_department' => $atr->rep_department,
                        'rep_url' => (is_null($atr->rep_upload_id)) ? null : FileAttached::url('PROFILE', $atr->rep_upload_id, $rep->att_ref_id, $rep->att_file_ext),
                        // Outsider
                        'out_full_name' => $atr->out_full_name,
                        'out_email' => $atr->out_email,
                        'out_rank' => $atr->out_rank,
                        'out_institution' => $atr->out_institution
                    ];
                }
                $temp[] = [
                    'pos_id' => $pos->pos_id,
                    'name' => $pos->name,
                    'order_no' => $pos->order_no,
                    'allowance' => $pos->allowance,
                    'attendee' => $att
                ];
            }
            $data['position'] = $temp;
            unset($position, $attendee, $temp);

            // @#
            $res->set('OK', $data);
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function short($id)
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            if (!is_numeric($id)) {
                $res->set('INPUT');
                throw new Exception();
            }

            // @1 Meeting
            $meeting = DB::connection('main')->select('SELECT m.*, cu.full_name creator_full_name FROM meetings m INNER JOIN users cu ON m.created_user_id = cu.user_id WHERE m.meeting_id = :meeting_id', [
                'meeting_id' => $id
            ]);
            if (!$meeting) {
                $res->set('EXIST');
                throw new Exception();
            }
            $meeting = $meeting[0];
            if (!is_null($meeting->pin)) {
                if ($this->req->input('pin') !== $meeting->pin) {
                    $res->set('ACCESS');
                    throw new Exception();
                }
            }

            $data = [
                'meeting_id' => $meeting->meeting_id,
                'creator_full_name' => $meeting->creator_full_name,
                'creator_user_id' => $meeting->created_user_id,
                'status_code' => $meeting->status_code,
                'type_code' => $meeting->type_code,
                'resolution_code' => $meeting->resolution_code,
                'priority_level' => $meeting->priority_level,
                'is_publish' => (bool) $meeting->is_publish,
                'is_secreted' => (bool) $meeting->is_secreted,
                'subject' => $meeting->subject,
                'started_at' => $meeting->started_at,
                'ended_at' => $meeting->ended_at,
                'address' => $meeting->address,
                'url' => $meeting->url,
                'period' => $meeting->period,
                'annual' => $meeting->annual,
                'tag_color' => Util::calendarColor($meeting->type_code, (bool) $meeting->is_secreted)['tag'],
                'background_color' => Util::calendarColor($meeting->type_code, (bool) $meeting->is_secreted)['background'],
                'topic' => [],
                'attendee_stats' => [
                    'wait' => 0,
                    'joined' => 0,
                    'rejected' => 0,
                    'total' => 0
                ],
                'attendee_list' => [],
                'attached_file' => [],
                'join' => [
                    'is_owned' => false,
                    'status_code' => null,
                    'rep_user_id' => null
                ]
            ];

            // Topic
            $topic = [];
            $index = 0;
            foreach (DB::connection('main')->select('SELECT * FROM topics WHERE meeting_id = :meeting_id ORDER BY topic_no ASC', ['meeting_id' => $meeting->meeting_id]) as $row) {
                $topic[$index] = [
                    'topic_no' => $row->topic_no,
                    'subject' => $row->subject,
                    'detail' => $row->detail,
                    'has_vote' => (bool) $row->has_vote,
                    'is_passed' => (bool) $row->is_passed,
                    'attached_file' => []
                ];
                foreach (FileAttached::getModule('TOPIC', $row->topic_id) as $file) {
                    $topic[$index]['attached_file'][] = [
                        'upload_id' => $file['upload_id'],
                        'file_ext' => $file['file_ext'],
                        'title' => $file['title'],
                        'origin_name' => $file['origin_name'],
                        'url' => $file['url']
                    ];
                    $data['attached_file'][] = [
                        'upload_id' => $file['upload_id'],
                        'file_ext' => $file['file_ext'],
                        'title' => $file['title'],
                        'origin_name' => $file['origin_name'],
                        'url' => $file['url']
                    ];
                }
                $index++;
            }
            $data['topic'] = $topic;
            unset($topic);

            // Attendee List
            $temp = null;
            foreach (DB::connection('main')->select("SELECT a.attendee_id, a.user_id, a.rep_user_id, p.name position_name, a.status_code, ua.full_name att_full_name, ua.rank att_rank, ua.department att_department, ua.branch att_branch, ua.institution att_institution, ur.full_name rep_full_name, ur.rank rep_rank, ur.department rep_department, ur.branch rep_branch, ur.institution rep_institution, a.out_full_name, a.out_rank, a.out_institution, fu.upload_id att_upload_id, fu.ref_id att_ref_id, fu.file_ext att_file_ext, fr.upload_id rep_upload_id, fr.ref_id rep_ref_id, fr.file_ext rep_file_ext FROM attendees a INNER JOIN positions p ON p.pos_id = a.pos_id INNER JOIN meetings m ON m.meeting_id = p.meeting_id LEFT OUTER JOIN users ua ON a.user_id = ua.user_id LEFT OUTER JOIN users ur ON a.rep_user_id = ur.user_id LEFT OUTER JOIN(SELECT * FROM file_uploads WHERE module_code = 'PROFILE') fu ON ua.user_id = fu.ref_id LEFT OUTER JOIN(SELECT * FROM file_uploads WHERE module_code = 'PROFILE') fr ON ur.user_id = fr.ref_id WHERE m.meeting_id = :meeting_id", ['meeting_id' => $meeting->meeting_id]) as $row) {
                $data['attendee_list'][] = [
                    'position_name' => $row->position_name,
                    'status_code' => $row->status_code,
                    'is_insider' => (is_null($row->user_id)) ? false : true,
                    'has_represent' => (is_null($row->rep_user_id)) ? false : true,
                    // Attendee
                    'att_full_name' => $row->att_full_name,
                    'att_rank' => $row->att_rank,
                    'att_institution' => $row->att_institution,
                    'att_url' => (is_null($row->att_upload_id)) ? null : FileAttached::url('PROFILE', $row->att_upload_id, $row->att_ref_id, $row->att_file_ext),
                    // Represent
                    'rep_full_name' => $row->rep_full_name,
                    'rep_rank' => $row->rep_rank,
                    'rep_institution' => $row->rep_institution,
                    'rep_url' => (is_null($row->rep_upload_id)) ? null : FileAttached::url('PROFILE', $row->rep_upload_id, $row->rep_ref_id, $row->rep_file_ext),
                    // Outsider
                    'out_full_name' => $row->out_full_name,
                    'out_rank' => $row->out_rank,
                    'out_institution' => $row->out_institution
                ];
                $data['attendee_stats']['total'] += 1;
                if ($row->status_code === 'WAIT') {
                    $data['attendee_stats']['wait'] += 1;
                }
                if ($row->status_code === 'JOINED') {
                    $data['attendee_stats']['joined'] += 1;
                }
                if ($row->status_code === 'REJECTED') {
                    $data['attendee_stats']['rejected'] += 1;
                }

                // Join Permission Checking
                if ((int) $this->req->input('auth_user_id') === (int) $row->user_id) {
                    $data['join']['is_owned'] = true;
                    $temp = $row->attendee_id;
                }
            }

            // Get Join Status
            if ($data['join']['is_owned']) {
                $attendee = DB::connection('main')->select('SELECT rep_user_id, status_code FROM attendees WHERE attendee_id = :attendee_id', ['attendee_id' => $temp]);
                $data['join']['status_code'] = $attendee[0]->status_code;
                $data['join']['rep_user_id'] = $attendee[0]->rep_user_id;
            }

            // @#
            $res->set('OK', $data);
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function update($id)
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            $this->clean(['subject', 'address', 'url', 'pin', 'meeting_code']);

            // @0 Validating
            $validator = Validator::make([...$this->req->all(), 'meeting_id' => $id], [
                'meeting_id' => Util::rule('Meeting', true, 'primary'),
                'subject' => Util::rule('Meeting', true, 'text.title'),
                'resolution_code' => Util::rule('Meeting', false, 'meeting.resolution_code'),
                'type_code' => Util::rule('Meeting', true, 'meeting.type_code'),
                'is_secreted' => 'required|boolean',
                'started_at' => Util::rule('Meeting', true, 'date.started_at'),
                'ended_at' => Util::rule('Meeting', true, 'date.ended_at'),
                'address' => Util::rule('Meeting', false, 'text.note'),
                'url' => 'nullable|url',
                'period' => Util::rule('Meeting', false, 'number.no'),
                'annual' => Util::rule('Meeting', false, 'date.year'),
                'pin' => Util::rule('Meeting', false, 'number.pin'),
                'tag_id' => Util::rule('Meeting', false, 'primary'),
                'meeting_code' => Util::rule('Meeting', false, 'meeting.meeting_code')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // Existing
            $meeting = DB::connection('main')->select('SELECT * FROM meetings WHERE meeting_id = :meeting_id', [
                'meeting_id' => $id
            ]);
            if (!$meeting) {
                $res->set('EXIST');
                throw new Exception();
            }

            // Existing Tag ID
            if (!empty($this->req->input('tag_id'))) {
                if (!DB::connection('main')->select("SELECT meeting_id FROM meetings WHERE meeting_id = :tag_id AND status_code != 'CANCELED' LIMIT 1", ['tag_id' => $this->req->input('tag_id')])) {
                    $res->set('EXIST');
                    throw new Exception();
                }
            }

            // Update
            DB::connection('main')->table('meetings')->where('meeting_id', $id)->update([
                'updated_at' => Util::now(),
                'updated_user_id' => $this->req->input('auth_user_id'),
                'type_code' => $this->req->input('type_code'),
                'resolution_code' => $this->req->input('resolution_code'),
                'is_secreted' => (bool) $this->req->input('is_secreted'),
                'subject' => $this->req->input('subject'),
                'started_at' => $this->req->input('started_at'),
                'ended_at' => $this->req->input('ended_at'),
                'address' => Util::trim($this->req->input('address')),
                'url' => Util::trim($this->req->input('url')),
                'period' => (empty($this->req->input('period'))) ? null : (int) $this->req->input('period'),
                'annual' => (empty($this->req->input('annual'))) ? null : (int) $this->req->input('annual'),
                'pin' => ((bool) $this->req->input('is_secreted') === false) ? null : Util::trim($this->req->input('pin')),
                'tag_id' => (empty($this->req->input('tag_id'))) ? null : (int) $this->req->input('tag_id'),
                'meeting_code' => (empty($this->req->input('meeting_code'))) ? null : $this->req->input('meeting_code')
            ]);

            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function delete($id)
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            if (!is_numeric($id)) {
                $res->set('INPUT');
                throw new Exception();
            }

            // @1 Meeting
            $meeting = DB::connection('main')->select('SELECT * FROM meetings WHERE meeting_id = :meeting_id', [
                'meeting_id' => $id
            ]);
            if (!$meeting) {
                $res->set('EXIST');
                throw new Exception();
            }

            // Condition Checking
            // ...

            // Delete Position and Attendee
            $position = DB::connection('main')->select('SELECT * FROM positions WHERE meeting_id = :meeting_id', [
                'meeting_id' => $id
            ]);
            foreach ($position as $row) {
                DB::connection('main')->table('attendees')->where('pos_id', $row->pos_id)->delete();
            }
            DB::connection('main')->table('positions')->where('meeting_id', $id)->delete();

            // Delete Topic and File
            $topic = DB::connection('main')->select('SELECT * FROM topics WHERE meeting_id = :meeting_id', [
                'meeting_id' => $id
            ]);
            foreach ($topic as $row) {
                FileAttached::deleteModule('TOPIC', $row->topic_id);
            }
            DB::connection('main')->table('topics')->where('meeting_id', $id)->delete();

            // Delete Agenda and File
            FileAttached::deleteModule('AGENDA', (int) $id);
            DB::connection('main')->table('meetings')->where('meeting_id', $id)->delete();

            // @#
            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function status($id)
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $validator = Validator::make([...$this->req->all(), 'meeting_id' => $id], [
                'meeting_id' => Util::rule('Meeting', true, 'primary'),
                'status_code' => 'required|in:CANCELED,PROGRESS,DONE'
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // Existing
            $meeting = DB::connection('main')->select("SELECT * FROM meetings WHERE meeting_id = :meeting_id AND status_code NOT IN ('CANCELED', 'DONE') LIMIT 1", [
                'meeting_id' => $id
            ]);
            if (!$meeting) {
                $res->set('EXIST');
                throw new Exception();
            }

            // Condition
            switch ($meeting[0]->status_code) {
                case 'CREATED': {
                        if (!in_array($this->req->input('status_code'), ['CANCELED', 'PROGRESS'])) {
                            $res->set('PROGRESS');
                            throw new Exception();
                        }
                    }
                    break;
                case 'PROGRESS': {
                        if (!in_array($this->req->input('status_code'), ['DONE'])) {
                            $res->set('PROGRESS');
                            throw new Exception();
                        }
                    }
                    break;
            }

            // Update
            DB::connection('main')->table('meetings')->where('meeting_id', $id)->update([
                'updated_at' => Util::now(),
                'updated_user_id' => $this->req->input('auth_user_id'),
                'status_code' => $this->req->input('status_code')
            ]);

            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function publish($id)
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $validator = Validator::make([...$this->req->all(), 'meeting_id' => $id], [
                'meeting_id' => Util::rule('Meeting', true, 'primary'),
                'is_publish' => 'required|boolean'
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // Existing
            $meeting = DB::connection('main')->select("SELECT * FROM meetings WHERE meeting_id = :meeting_id", [
                'meeting_id' => $id
            ]);
            if (!$meeting) {
                $res->set('EXIST');
                throw new Exception();
            }

            // Update
            DB::connection('main')->table('meetings')->where('meeting_id', $id)->update([
                'updated_at' => Util::now(),
                'updated_user_id' => $this->req->input('auth_user_id'),
                'is_publish' => (bool) $this->req->input('is_publish')
            ]);

            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function email()
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $schema = [
                'meeting_id' => Util::rule('Meeting', true, 'primary'),
                'mode_code' => Util::rule('Meeting', true, 'meeting.email.mode_code')
            ];
            switch ($this->req->input('mode_code')) {
                case 'NEW': {
                        $schema = [
                            ...$schema,
                            'attendee_id' => 'required|array',
                            'attendee_id.*' => Util::rule('Meeting', true, 'primary')
                        ];
                    }
                    break;
                case 'EDIT': {
                        $schema = [
                            ...$schema,
                            'attendee_id' => 'required|array',
                            'attendee_id.*' => Util::rule('Meeting', true, 'primary'),
                            'edit_code' => 'required|array',
                            'edit_code.*' => Util::rule('Meeting', false, 'meeting.email.edit_code')
                        ];
                    }
                    break;
            }

            $validator = Validator::make($this->req->all(), $schema);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // Getting User Email
            if (in_array($this->req->input('mode_code'), ['NEW', 'EDIT'])) {

                // Sanitizing
                $receiver = array_map(function ($item): int {
                    return (int) $item;
                }, array_unique($this->req->input('attendee_id')));
                if (empty($receiver)) {
                    $res->set('INPUT');
                    throw new Exception();
                }
            }

            // Get Detail
            $detail = self::built((int) $this->req->input('meeting_id'));
            if (empty($detail)) {
                $res->set('PROGRESS');
                throw new Exception();
            }
            $detail = [
                ...$detail,
                'mode_code' => $this->req->input('mode_code'),
                'edit_code' => (!empty($this->req->input('edit_code')) && $this->req->input('mode_code') === 'EDIT') ? array_unique($this->req->input('edit_code')) : []
            ];

            // Build Envelope
            $envelope = [];
            $script = "SELECT a.out_ref_code, CASE WHEN a.user_id IS NULL THEN 'OUTSIDER' ELSE 'INSIDER' END type_code, CASE WHEN a.user_id IS NOT NULL THEN ua.full_name ELSE a.out_full_name END att_full_name, CASE WHEN a.user_id IS NOT NULL THEN ua.email ELSE a.out_email END att_email, ur.full_name req_full_name, ur.email rep_email FROM attendees a INNER JOIN positions p ON p.pos_id = a.pos_id INNER JOIN meetings m ON m.meeting_id = p.meeting_id LEFT OUTER JOIN users ua ON a.user_id = ua.user_id LEFT OUTER JOIN users ur ON a.rep_user_id = ur.user_id WHERE m.meeting_id = :meeting_id ";
            if (in_array($this->req->input('mode_code'), ['NEW', 'EDIT'])) {
                $script .= 'AND a.attendee_id IN (' . implode(',', $receiver) . ') ';
            }
            $script .= 'ORDER BY p.order_no ASC, a.attendee_id ASC ';
            foreach (DB::connection('main')->select($script, ['meeting_id' => $this->req->input('meeting_id')]) as $row) {

                // Main
                $envelope[] = [
                    'to' => $row->att_email,
                    'data' => [
                        ...$detail,
                        'link' => self::url($row->type_code, (int) $this->req->input('meeting_id'), $detail['pin'], $row->out_ref_code)
                    ]
                ];
                if (!empty($row->rep_email)) {
                    // Proxy
                    $envelope[] = [
                        'to' => $row->rep_email,
                        'data' => [
                            ...$detail,
                            'link' => self::url($row->type_code, (int) $this->req->input('meeting_id'), $detail['pin'], $row->out_ref_code)
                        ]
                    ];
                }
            }

            // Send
            foreach ($envelope as $row) {
                Mail::to($row['to'])->send(new MeetingMail($row['data']));
            }

            // @#
            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    // Joining Decision for Insider
    public function insider($id)
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $validator = Validator::make([...$this->req->all(), 'meeting_id' => $id], [
                'meeting_id' => Util::rule('Meeting', true, 'primary'),
                'status_code' => Util::rule('Meeting', true, 'meeting.join_code'),
                'rep_user_id' => Util::rule('Meeting', false, 'primary')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            $access = DB::connection('main')->select("SELECT a.attendee_id FROM attendees a INNER JOIN positions p ON a.pos_id = p.pos_id INNER JOIN meetings m ON m.meeting_id = p.meeting_id WHERE m.meeting_id = :meeting_id AND a.user_id = :user_id AND a.status_code = 'WAIT' LIMIT 1", [
                'meeting_id' => $id,
                'user_id' => $this->req->input('auth_user_id')
            ]);
            if (!$access) {
                $res->set('EXIST');
                throw new Exception();
            }
            if (!empty($this->req->input('rep_user_id'))) {
                $user = DB::connection('main')->select('SELECT * FROM users WHERE deleted_at IS NULL AND user_id = :rep_user_id AND user_id != :auth_user_id LIMIT 1', [
                    'rep_user_id' => $this->req->input('rep_user_id'),
                    'auth_user_id' => $this->req->input('auth_user_id') // Not The Same Person
                ]);
                if (!$user) {
                    $res->set('EXIST');
                    throw new Exception();
                }
            }

            $data = [
                'updated_at' => Util::now(),
                'status_code' => (!empty($this->req->input('rep_user_id'))) ? 'JOINED' : $this->req->input('status_code')
            ];
            if (!empty($this->req->input('rep_user_id'))) {
                $data['rep_user_id'] = $this->req->input('rep_user_id');
            }
            DB::connection('main')->table('attendees')->where('attendee_id', $access[0]->attendee_id)->update($data);

            // Send an Email to Proxy
            if (!empty($this->req->input('rep_user_id'))) {
                $detail = self::built($id);
                Mail::to($user[0]->email)->send(new MeetingMail([
                    ...$detail,
                    'mode_code' => 'NEW',
                    'edit_code' => [],
                    'link' => self::url('INSIDER', (int) $id, $detail['pin'], null)
                ]));
            }

            // @#
            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    // Joining Decision for Outsider
    public function outsider(string $reference)
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $validator = Validator::make([...$this->req->all(), 'out_ref_code' => $reference], [
                'out_ref_code' => Util::rule('Meeting', true, 'meeting.reference'),
                'status_code' => Util::rule('Meeting', true, 'meeting.join_code')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            $attendee = DB::connection('main')->select("SELECT * FROM attendees WHERE status_code = 'WAIT' AND out_ref_code = :out_ref_code", ['out_ref_code' => $reference]);
            if (!$attendee) {
                $res->set('EXIST');
                throw new Exception();
            }
            DB::connection('main')->table('attendees')->where('attendee_id', $attendee[0]->attendee_id)->update([
                'updated_at' => Util::now(),
                'status_code' => $this->req->input('status_code')
            ]);

            // @#
            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    // Content Build in Email
    private static function built(int $id): array
    {

        $data = [];

        try {

            $meeting = DB::connection('main')->select('SELECT * FROM meetings WHERE meeting_id = :meeting_id', ['meeting_id' => $id]);
            $attendee = DB::connection('main')->select('SELECT CASE WHEN a.user_id IS NOT NULL THEN ua.full_name ELSE a.out_full_name END att_full_name, ur.full_name rep_full_name, p.name position_name FROM attendees a INNER JOIN positions p ON p.pos_id = a.pos_id INNER JOIN meetings m ON m.meeting_id = p.meeting_id LEFT OUTER JOIN users ua ON a.user_id = ua.user_id LEFT OUTER JOIN users ur ON a.rep_user_id = ur.user_id WHERE m.meeting_id = :meeting_id ORDER BY p.order_no ASC, a.attendee_id ASC', ['meeting_id' => $id]);
            if (!$meeting || !$attendee) {
                throw new Exception();
            }
            $list = [];
            foreach ($attendee as $row) {
                $list[] = [
                    'att_full_name' => $row->att_full_name,
                    'rep_full_name' => $row->rep_full_name,
                    'position_name' => $row->position_name
                ];
            }
            $data = [
                'meeting_id' => $meeting[0]->meeting_id,
                'subject' => $meeting[0]->subject,
                'date_from' => Util::convertDateFormatThai($meeting[0]->started_at, 'DATE'),
                'time_from' => Util::convertDateFormatThai($meeting[0]->ended_at, 'TIME'),
                'address' => $meeting[0]->address,
                'online_url' => $meeting[0]->url,
                'pin' => $meeting[0]->pin,
                'attendee' => $list
            ];
        } catch (Exception $e) {
            $data = [];
        }

        return $data;
    }

    // URL Detail in Email
    public static function url(string $actor, int $meeting, $pin, $reference): string
    {
        $text = ($actor === 'INSIDER') ? env('INVITE_EMAIL_MEETING_INSIDER_URL') : env('INVITE_EMAIL_MEETING_OUTSIDER_URL');
        $text .= '?id=' . $meeting;
        if (!is_null($pin)) {
            $text .= '&pin=' . $pin;
        }
        if ($actor === 'OUTSIDER') {
            $text .= '&key=' . $reference;
        }
        return $text;
    }
}
