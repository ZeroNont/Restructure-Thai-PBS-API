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
use App\Mail\ProposalMail;

class ProposalController extends Controller
{

    private const PAGE_LIMIT = 10;
    // PENDING, PASSED, REJECTED
    private const DEFAULT_STATUS = 'PENDING';
    private const PASSED_STATUS = 'PASSED';

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    // API

    public function create()
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            $this->clean(['subject', 'text_background', 'text_rule', 'text_issue']);

            // @0 Validating
            $schema = [
                'subject' => Util::rule(true, 'text.title'),
                'level_code' => Util::rule(true, 'proposal.level_code'),
                'type_code' => Util::rule(true, 'proposal.type_code'),
                'text_background' => Util::rule(false, 'text.paper'),
                'text_rule' => Util::rule(false, 'text.paper'),
                'text_issue' => Util::rule(false, 'text.paper'),
                'admin' => 'required|array',
                'approver' => 'required|array'
            ];
            if ($this->req->input('level_code') !== 'D') {
                $schema['prop_prefix_id'] = Util::rule(true, 'primary');
            }
            $validator = Validator::make($this->req->all(), $schema);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // Prefix
            if ($this->req->input('level_code') !== 'D') {
                if (!self::hasPrefix($this->req->input('level_code'), (int) $this->req->input('prop_prefix_id'))) {
                    $res->set('INPUT');
                    throw new Exception();
                }
            }

            // Existing User
            if (!User::hasUserAsInput((array) $this->req->input('admin')) || !User::hasUserAsInput((array) $this->req->input('approver'))) {
                $res->set('INPUT');
                throw new Exception();
            }

            // Proposal
            $id = DB::connection('main')->table('meeting_proposals')->insertGetId([
                'created_at' => Util::now(),
                'created_user_id' => $this->req->input('auth_user_id'),
                'subject' => $this->req->input('subject'),
                'status_code' => self::DEFAULT_STATUS,
                'level_code' => $this->req->input('level_code'),
                'prop_prefix_id' => ($this->req->input('level_code') !== 'D') ? $this->req->input('prop_prefix_id') : null,
                'type_code' => $this->req->input('type_code'),
                'text_background' => $this->req->input('text_background'),
                'text_rule' => $this->req->input('text_rule'),
                'text_issue' => $this->req->input('text_issue')
            ], 'prop_id');

            // Admin
            $admin = [];
            $no = 0;
            foreach ($this->req->input('admin') as $row) {
                $admin[] = [
                    'created_at' => Util::now(),
                    'prop_id' => $id,
                    'user_id' => $row,
                    'no' => $no++
                ];
            }
            DB::connection('main')->table('proposal_admins')->insert($admin);

            // Approver
            $approver = [];
            $no = 0;
            foreach ($this->req->input('approver') as $row) {
                $approver[] = [
                    'created_at' => Util::now(),
                    'prop_id' => $id,
                    'user_id' => $row,
                    'no' => $no++,
                    'status_code' => self::DEFAULT_STATUS,
                    'note' => null
                ];
            }
            DB::connection('main')->table('proposal_approvers')->insert($approver);

            // Send Email to Approver
            $email = DB::connection('main')->select('SELECT GROUP_CONCAT(u.email) email_list FROM proposal_approvers ap INNER JOIN users u ON ap.user_id = u.user_id WHERE ap.prop_id = :prop_id GROUP BY ap.prop_id', [
                'prop_id' => $id
            ]);
            self::toApproverEmail($id, explode(',', $email[0]->email_list));

            $res->set('CREATED', [
                'prop_id' => $id
            ]);
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

            // @1 Existing
            $exist = DB::connection('main')->select("SELECT * FROM meeting_proposals WHERE prop_id = :prop_id", [
                'prop_id' => $id
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }

            if ($exist[0]->status_code === self::PASSED_STATUS) {
                $res->set('ACCESS');
                throw new Exception();
            }

            // @2 Delete
            DB::connection('main')->table('meeting_proposals')->where('prop_id', $id)->delete();
            DB::connection('main')->table('proposal_admins')->where('prop_id', $id)->delete();
            DB::connection('main')->table('proposal_approvers')->where('prop_id', $id)->delete();
            FileAttached::deleteModule('PROP', (int) $id);

            // @#
            $res->set('OK');
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

            $this->clean(['subject', 'text_background', 'text_rule', 'text_issue']);

            // @0 Validating
            $schema = [
                'prop_id' => Util::rule(true, 'primary'),
                'subject' => Util::rule(true, 'text.title'),
                'level_code' => Util::rule(true, 'proposal.level_code'),
                'type_code' => Util::rule(true, 'proposal.type_code'),
                'text_background' => Util::rule(false, 'text.paper'),
                'text_rule' => Util::rule(false, 'text.paper'),
                'text_issue' => Util::rule(false, 'text.paper'),
                'admin' => 'required|array',
                'approver' => 'required|array'
            ];
            if ($this->req->input('level_code') !== 'D') {
                $schema['prop_prefix_id'] = Util::rule(true, 'primary');
            }
            $validator = Validator::make([...$this->req->all(), 'prop_id' => $id], $schema);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // Existing Core Data
            $exist = DB::connection('main')->select("SELECT * FROM meeting_proposals WHERE prop_id = :prop_id", [
                'prop_id' => $id
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }

            // Prefix
            if ($this->req->input('level_code') !== 'D') {
                if (!self::hasPrefix($this->req->input('level_code'), (int) $this->req->input('prop_prefix_id'))) {
                    $res->set('INPUT');
                    throw new Exception();
                }
            }

            // Existing User
            if (!User::hasUserAsInput((array) $this->req->input('admin')) || !User::hasUserAsInput((array) $this->req->input('approver'))) {
                $res->set('INPUT');
                throw new Exception();
            }

            // Proposal
            DB::connection('main')->table('meeting_proposals')->where('prop_id', $id)->update([
                'updated_at' => Util::now(),
                'updated_user_id' => $this->req->input('auth_user_id'),
                'subject' => $this->req->input('subject'),
                'level_code' => $this->req->input('level_code'),
                'prop_prefix_id' => ($this->req->input('level_code') !== 'D') ? $this->req->input('prop_prefix_id') : null,
                'type_code' => $this->req->input('type_code'),
                'text_background' => $this->req->input('text_background'),
                'text_rule' => $this->req->input('text_rule'),
                'text_issue' => $this->req->input('text_issue')
            ]);

            // Able to Edit Only in Pending Status
            $stack = [];
            if ($exist[0]->status_code === 'PENDING') {

                // Admin
                DB::connection('main')->table('proposal_admins')->where('prop_id', $id)->delete();
                $admin = [];
                $no = 0;
                foreach ($this->req->input('admin') as $row) {
                    $admin[] = [
                        'created_at' => Util::now(),
                        'prop_id' => $id,
                        'user_id' => $row,
                        'no' => $no++
                    ];
                }
                DB::connection('main')->table('proposal_admins')->insert($admin);

                // Approver
                DB::connection('main')->table('proposal_approvers')->where('prop_id', $id)->where('status_code', 'PENDING')->delete();
                $lastApproverNo = DB::connection('main')->select('SELECT no FROM proposal_approvers WHERE prop_id = :prop_id ORDER BY no DESC LIMIT 1', [
                    'prop_id' => $id
                ]);
                $no = (!$lastApproverNo) ? 0 : ((int) $lastApproverNo[0]->no + 1);
                foreach ($this->req->input('approver') as $row) {
                    $hasApprover = DB::connection('main')->select('SELECT * FROM proposal_approvers WHERE prop_id = :prop_id AND user_id = :user_id', [
                        'prop_id' => $id,
                        'user_id' => $row
                    ]);
                    if (!$hasApprover) {
                        DB::connection('main')->table('proposal_approvers')->insert([
                            'created_at' => Util::now(),
                            'prop_id' => $id,
                            'user_id' => $row,
                            'no' => $no++,
                            'status_code' => self::DEFAULT_STATUS,
                            'note' => null
                        ]);
                        // Stack Send Email
                        $stack[] = $row;
                    }
                }
                $lastApproverStatus = DB::connection('main')->select('SELECT status_code FROM proposal_approvers WHERE prop_id = :prop_id ORDER BY no DESC LIMIT 1', [
                    'prop_id' => $id
                ]);
                DB::connection('main')->table('meeting_proposals')->where('prop_id', $id)->update([
                    'updated_at' => Util::now(),
                    'updated_user_id' => $this->req->input('auth_user_id'),
                    'status_code' => $lastApproverStatus[0]->status_code
                ]);
                // Send Email to Admin
                if ($lastApproverStatus[0]->status_code !== 'PENDING') {
                    self::toAdminEmail((int) $id);
                }
            }

            // Send Email
            foreach ($stack as $row) {
                $email = DB::connection('main')->select('SELECT email FROM users WHERE user_id = :user_id LIMIT 1', ['user_id' => $row]);
                self::toApproverEmail((int) $id, [$email[0]->email]);
            }
            $res->set('OK');
        } catch (Exception $e) {
            $res->debug($e->getMessage());
        }
        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }
    public function list()
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $this->clean(['keyword']);
            $validator = Validator::make($this->req->all(), [
                'keyword' => Util::rule(false, 'keyword'),
                'page' => Util::rule(true, 'page'),
                'status_code' => 'required|array',
                'status_code.*' => 'required|in:PENDING,PASSED,REJECTED',
                'type_code' => 'nullable|array',
                'type_code.*' => Util::rule(true, 'proposal.type_code'),
                'level_code' => 'nullable|array',
                'level_code.*' => Util::rule(true, 'proposal.level_code'),
                'from_date' => 'required|date|date_format:Y-m-d',
                'to_date' => 'required|date|date_format:Y-m-d|after_or_equal:from_date',
                'limit' => Util::rule(false, 'page_limit')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }
            $pageLimit = (empty($this->req->input('limit'))) ? self::PAGE_LIMIT : $this->req->input('limit');
            $keyword = Util::trim($this->req->input('keyword'));
            $statusFilter = array_unique($this->req->input('status_code'));
            $typeFilter = (empty($this->req->input('type_code'))) ? [] : array_unique($this->req->input('type_code'));
            $levelFilter = (empty($this->req->input('level_code'))) ? [] : array_unique($this->req->input('level_code'));

            // @1 Config
            $page = (int) $this->req->input('page');
            $offset = ($page - 1) * $pageLimit;

            // @2 Build a Query
            $script = "SELECT a.status_code last_approver_status_code, a.last_updated last_approver_updated, lau.full_name last_approver_full_name, m.name prefix_name, p.*, u.full_name FROM meeting_proposals p LEFT OUTER JOIN proposal_prefixes m ON p.prop_prefix_id = m.prop_prefix_id INNER JOIN users u ON u.user_id = p.created_user_id INNER JOIN( SELECT x.prop_id, SUBSTRING_INDEX(GROUP_CONCAT(x.user_id ORDER BY x.last_updated DESC), ',', 1) user_id, SUBSTRING_INDEX(GROUP_CONCAT(x.status_code ORDER BY x.last_updated DESC), ',', 1) status_code, x.last_updated FROM(SELECT prop_id, user_id, status_code,(CASE WHEN updated_at IS NOT NULL THEN updated_at ELSE created_at END) last_updated FROM proposal_approvers) x GROUP BY x.prop_id, x.last_updated) a ON p.prop_id = a.prop_id INNER JOIN users lau ON a.user_id = lau.user_id WHERE p.status_code IN ('" . implode("','", $statusFilter) . "') ";
            $script .= "AND CAST(p.created_at AS DATE) BETWEEN :from_date AND :to_date ";
            $blinds = [
                'from_date' => $this->req->input('from_date'),
                'to_date' => $this->req->input('to_date')
            ];
            // @2-1 Add Filter
            if ($keyword) {
                $script .= 'AND p.subject LIKE :keyword ';
                $blinds = [...$blinds, 'keyword' => '%' . $keyword . '%'];
            }
            if (!empty($typeFilter)) {
                $script .= "AND p.type_code IN ('" . implode("','", $typeFilter) . "') ";
            }
            if (!empty($levelFilter)) {
                $script .= "AND p.level_code IN ('" . implode("','", $levelFilter) . "') ";
            }
            // @2-2 Count Whole
            $whole = DB::connection('main')->select('SELECT COUNT(a.prop_id) whole FROM (' . $script . ') a', $blinds)[0]->whole; // No Limit
            // @2-3 Limit
            $script .= "ORDER BY CASE WHEN p.status_code = 'PENDING' THEN 1 ELSE 2 END ASC, CASE WHEN p.updated_at IS NULL THEN p.created_at ELSE p.updated_at END DESC LIMIT :limit OFFSET :offset ";
            $blinds = [...$blinds, 'limit' => $pageLimit, 'offset' => $offset];

            // @3 Fetching
            $data = [];
            $temp = DB::connection('main')->select($script, $blinds);
            foreach ($temp as $row) {
                $data[] = [
                    'prop_id' => $row->prop_id,
                    'created_at' => $row->created_at,
                    'subject' => $row->subject,
                    'type_code' => $row->type_code,
                    'status_code' => $row->status_code,
                    'prefix_name' => $row->prefix_name,
                    'level_code' => $row->level_code,
                    'creator_full_name' => $row->full_name,
                    'last_approver_full_name' => $row->last_approver_full_name,
                    'last_approver_updated' => $row->last_approver_updated,
                    'last_approver_status_code' => $row->last_approver_status_code
                ];
            }

            // @#
            $res->set('OK', $data, [
                'whole' => $whole,
                'count' => sizeof($data),
                'page' => $page,
                'limit' => $pageLimit
            ]);
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

            // @1 Existing User
            $exist = DB::connection('main')->select("SELECT p.*, m.name prefix_name FROM meeting_proposals p LEFT OUTER JOIN proposal_prefixes m ON p.prop_prefix_id = m.prop_prefix_id WHERE p.prop_id = :prop_id", [
                'prop_id' => $id
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }
            $exist = $exist[0];

            $data = [
                'created_at' => $exist->created_at,
                'updated_at' => $exist->updated_at,
                'status_code' => $exist->status_code,
                'type_code' => $exist->type_code,
                'subject' => $exist->subject,
                'level_code' => $exist->level_code,
                'prop_prefix_id' => $exist->prop_prefix_id,
                'prefix_name' => $exist->prefix_name,
                'text_background' => $exist->text_background,
                'text_rule' => $exist->text_rule,
                'text_issue' => $exist->text_issue,
                'attached_file' => [],
                'admin' => [],
                'approver' => []
            ];

            // Attached File
            foreach (FileAttached::getModule('PROP', (int) $id) as $row) {
                $data['attached_file'][] = [
                    'upload_id' => $row['upload_id'],
                    'file_ext' => $row['file_ext'],
                    'title' => $row['title'],
                    'origin_name' => $row['origin_name'],
                    'url' => $row['url']
                ];
            }

            // Admin
            $admin = DB::connection('main')->select("SELECT a.*, u.email, u.full_name, u.employee_code, u.rank, u.institution, u.department, u.branch FROM proposal_admins a INNER JOIN users u ON u.user_id = a.user_id WHERE a.prop_id = :prop_id ORDER BY a.no ASC", [
                'prop_id' => $id
            ]);
            foreach ($admin as $row) {
                $data['admin'][] = [
                    'prop_admin_id' => $row->prop_admin_id,
                    'user_id' => $row->user_id,
                    'no' => (int) $row->no,
                    'email' => $row->email,
                    'full_name' => $row->full_name,
                    'employee_code' => $row->employee_code,
                    'rank' => $row->rank,
                    'institution' => $row->institution,
                    'department' => $row->department,
                    'branch' => $row->branch
                ];
            }

            // Approver
            $approver = DB::connection('main')->select("SELECT a.*, u.email, u.full_name, u.employee_code, u.rank, u.institution, u.department, u.branch, (CASE WHEN a.updated_at IS NOT NULL THEN a.updated_at ELSE a.created_at END) last_updated FROM proposal_approvers a INNER JOIN users u ON u.user_id = a.user_id WHERE a.prop_id = :prop_id ORDER BY a.no ASC", [
                'prop_id' => $id
            ]);
            foreach ($approver as $row) {
                $data['approver'][] = [
                    'prop_apv_id' => $row->prop_apv_id,
                    'user_id' => $row->user_id,
                    'no' => (int) $row->no,
                    'email' => $row->email,
                    'full_name' => $row->full_name,
                    'employee_code' => $row->employee_code,
                    'rank' => $row->rank,
                    'institution' => $row->institution,
                    'department' => $row->department,
                    'branch' => $row->branch,
                    'status_code' => $row->status_code,
                    'note' => $row->note,
                    'last_updated' => $row->last_updated
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

    public function approve($id)
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            $this->clean([
                'note'
            ]);

            // @0 Validating
            $validator = Validator::make([...$this->req->all(), 'prop_apv_id' => $id], [
                'prop_apv_id' => Util::rule(true, 'primary'),
                'status_code' => 'required|in:REJECTED,PASSED',
                'note' => Util::rule(false, 'text.note')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            $exist = DB::connection('main')->select("SELECT * FROM proposal_approvers WHERE prop_apv_id = :prop_apv_id AND user_id = :user_id AND status_code = 'PENDING'", [
                'prop_apv_id' => $id,
                'user_id' => $this->req->input('auth_user_id')
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }
            $exist = $exist[0];

            // Above Check
            if ((int) $exist->no > 0) {
                $above = DB::connection('main')->select("SELECT * FROM proposal_approvers WHERE prop_id = :prop_id AND no = :no", [
                    'prop_id' => $exist->prop_id,
                    'no' => ((int) $exist->no - 1)
                ]);
                if ($above[0]->status_code !== 'PASSED') {
                    $res->set('EXIST');
                    throw new Exception();
                }
            }
            DB::connection('main')->table('proposal_approvers')->where('prop_apv_id', $id)->update([
                'updated_at' => Util::now(),
                'status_code' => $this->req->input('status_code'),
                'note' => $this->req->input('note')
            ]);

            // Below Check
            $below = DB::connection('main')->select("SELECT * FROM proposal_approvers WHERE prop_id = :prop_id AND no = :no", [
                'prop_id' => $exist->prop_id,
                'no' => ((int) $exist->no + 1)
            ]);
            if (!$below || $this->req->input('status_code') === 'REJECTED') {
                DB::connection('main')->table('meeting_proposals')->where('prop_id', $exist->prop_id)->update([
                    'status_code' => $this->req->input('status_code')
                ]);
                // Send Email to Admin
                self::toAdminEmail((int) $exist->prop_id);
            }

            // @#
            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    // Function

    private static function hasPrefix(string $level, int $id): bool
    {
        $temp = DB::connection('main')->select('SELECT prop_prefix_id FROM proposal_prefixes WHERE level_code = :level_code AND prop_prefix_id = :prop_prefix_id LIMIT 1', [
            'level_code' => $level,
            'prop_prefix_id' => $id
        ]);
        return ($temp) ? true : false;
    }

    private static function toApproverEmail(int $id, array $email): bool
    {
        $status = true;
        try {

            $detail = DB::connection('main')->select('SELECT mp.subject, mp.type_code, mp.level_code, pf.name presenter_name, am.admin_list FROM meeting_proposals mp LEFT OUTER JOIN proposal_prefixes pf ON mp.prop_prefix_id = pf.prop_prefix_id INNER JOIN( SELECT a.prop_id, GROUP_CONCAT(u.full_name) admin_list FROM proposal_admins a INNER JOIN users u ON a.user_id = u.user_id GROUP BY a.prop_id) am ON mp.prop_id = am.prop_id WHERE mp.prop_id = :prop_id LIMIT 1', [
                'prop_id' => $id
            ]);
            if (!$detail) {
                throw new Exception();
            }
            $detail = $detail[0];
            $envelope = [
                'mode' => 'APPROVER',
                'subject' => $detail->subject,
                'type_code' => $detail->type_code,
                'level_code' => $detail->level_code,
                'presenter_name' => $detail->presenter_name,
                'admin_list' => explode(',', $detail->admin_list)
            ];
            foreach ($email as $to) {
                Mail::to($to)->send(new ProposalMail($envelope));
            }
        } catch (Exception $e) {
            $status = false;
        }
        return $status;
    }

    private static function toAdminEmail(int $id): bool
    {
        $status = true;
        try {

            $detail = DB::connection('main')->select('SELECT mp.subject, mp.type_code, mp.status_code, am.admin_full_name_list, am.admin_email_list, ap.approver_full_name_list FROM meeting_proposals mp LEFT OUTER JOIN( SELECT aa.prop_id, GROUP_CONCAT(uu.full_name) approver_full_name_list FROM proposal_approvers aa INNER JOIN users uu ON aa.user_id = uu.user_id GROUP BY aa.prop_id) ap ON mp.prop_id = ap.prop_id LEFT OUTER JOIN( SELECT bb.prop_id, GROUP_CONCAT(xx.full_name) admin_full_name_list, GROUP_CONCAT(xx.email) admin_email_list FROM proposal_admins bb INNER JOIN users xx ON bb.user_id = xx.user_id GROUP BY bb.prop_id) am ON mp.prop_id = am.prop_id WHERE mp.prop_id = :prop_id GROUP BY mp.prop_id', ['prop_id' => $id]);
            if (!$detail) {
                throw new Exception();
            }
            $detail = $detail[0];
            $envelope = [
                'mode' => 'RESULT',
                'status_code' => $detail->status_code,
                'subject' => $detail->subject,
                'type_code' => $detail->type_code,
                'admin_full_name_list' => explode(',', $detail->admin_full_name_list),
                'approver_full_name_list' => explode(',', $detail->approver_full_name_list)
            ];
            foreach (explode(',', $detail->admin_email_list) as $to) {
                Mail::to($to)->send(new ProposalMail($envelope));
            }
        } catch (Exception $e) {
            $status = false;
        }
        return $status;
    }
}