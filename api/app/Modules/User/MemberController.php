<?php

namespace App\Modules\User;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Libraries\Response;
use App\Libraries\Util;
use App\Events\UserDeleteEvent;
use Illuminate\Support\Facades\Hash;
use App\Libraries\FileAttached;

class MemberController extends Controller
{

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    private const PAGE_LIMIT = 10;

    // API

    public function search() // [GET]
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $this->clean(['keyword']);
            $validator = Validator::make($this->req->all(), [
                'keyword' => Util::rule('User', false, 'keyword'),
                'actor_code' => 'nullable|array',
                'actor_code.*' => Util::rule('User', true, 'user.actor_code')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            $blind = [];
            $command = "SELECT u.*, f.upload_id, f.ref_id, f.file_ext FROM users u LEFT OUTER JOIN (SELECT * FROM file_uploads WHERE module_code = 'PROFILE') f ON u.user_id = f.ref_id WHERE u.actor_code != 'THANOS' AND u.is_enabled = 1 ";
            if (!is_null($this->req->input('keyword'))) {
                $command .= "AND (u.full_name LIKE :full_name OR u.employee_code LIKE :employee_code OR u.email LIKE :email OR u.rank LIKE :rank OR u.institution LIKE :institution OR u.department LIKE :department OR u.branch LIKE :branch) ";
                $keyword = '%' . $this->req->input('keyword') . '%';
                $blind = [
                    ...$blind,
                    'full_name' => $keyword,
                    'employee_code' => $keyword,
                    'email' => $keyword,
                    'rank' => $keyword,
                    'institution' => $keyword,
                    'department' => $keyword,
                    'branch' => $keyword
                ];
            }
            $actorFilter = (empty($this->req->input('actor_code'))) ? [] : array_unique($this->req->input('actor_code'));
            if (!empty($actorFilter)) {
                $command .= "AND u.actor_code IN ('" . implode("','", $actorFilter) . "') ";
            }
            $command .= "ORDER BY u.user_id ASC LIMIT 5";

            $temp = DB::connection('main')->select($command, $blind);
            $data = [];
            foreach ($temp as $row) {
                $data[] = [
                    'user_id' => $row->user_id,
                    'employee_code' => $row->employee_code,
                    'full_name' => $row->full_name,
                    'email' => $row->email,
                    'rank' => $row->rank,
                    'institution' => $row->institution,
                    'department' => $row->department,
                    'branch' => $row->branch,
                    'actor_code' => $row->actor_code,
                    'url' => (is_null($row->upload_id)) ? null : FileAttached::url('PROFILE', $row->upload_id, $row->ref_id, $row->file_ext)
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

    public function leader() // [GET]
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {
            // @0 Validating
            $validator = Validator::make($this->req->all(), [
                'user_id' => Util::rule('User', true, 'primary')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }
            $command = "SELECT b.*, f.upload_id, f.ref_id, f.file_ext FROM users u INNER JOIN ranks r ON u.rank_code = r.rank_code INNER JOIN users b ON r.parent_code = b.rank_code LEFT OUTER JOIN( SELECT * FROM file_uploads WHERE module_code = 'PROFILE') f ON u.user_id = f.ref_id WHERE u.actor_code != 'THANOS' AND u.user_id = :user_id";
            $temp = DB::connection('main')->select($command, ['user_id' => $this->req->input('user_id')]);
            $data = [];
            foreach ($temp as $row) {
                $data[] = [
                    'user_id' => $row->user_id,
                    'employee_code' => $row->employee_code,
                    'full_name' => $row->full_name,
                    'email' => $row->email,
                    'rank' => $row->rank,
                    'institution' => $row->institution,
                    'department' => $row->department,
                    'branch' => $row->branch,
                    'actor_code' => $row->actor_code,
                    'url' => (is_null($row->upload_id)) ? null : FileAttached::url('PROFILE', $row->upload_id, $row->ref_id, $row->file_ext)
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

    public function list() // [GET]
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $this->clean(['keyword']);
            $this->cast('bool', ['is_enabled']);
            $validator = Validator::make($this->req->all(), [
                'keyword' => Util::rule('User', false, 'keyword'),
                'group_code' => 'required|in:PERMANENT,CONTRACT',
                'page' => Util::rule('User', true, 'page'),
                'is_enabled' => 'nullable|boolean',
                'actor_code' => 'nullable|array',
                'actor_code.*' => Util::rule('User', false, 'user.actor_code'),
                'limit' => Util::rule('User', false, 'page_limit')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }
            $pageLimit = (empty($this->req->input('limit'))) ? self::PAGE_LIMIT : $this->req->input('limit');
            $keyword = Util::trim($this->req->input('keyword'));
            $actorFilter = (empty($this->req->input('actor_code'))) ? [] : array_unique($this->req->input('actor_code'));

            // @1 Config
            $page = (int) $this->req->input('page');
            $offset = ($page - 1) * $pageLimit;

            // @2 Build a Query
            $blinds = [];
            $script = "SELECT * FROM users WHERE deleted_at IS NULL AND actor_code NOT IN ('THANOS') ";
            $script .= ($this->req->input('group_code') === 'PERMANENT') ? " AND is_permanent = 1 " : " AND is_permanent = 0 ";
            // @2-1 Add Filter
            if ($keyword) {
                $script .= 'AND (full_name LIKE :full_name OR email LIKE :email OR employee_code LIKE :employee_code OR users.rank LIKE :rank OR institution LIKE :institution OR department LIKE :department OR branch LIKE :branch) ';
                $blinds = [
                    ...$blinds,
                    'full_name' => '%' . $keyword . '%',
                    'email' => '%' . $keyword . '%',
                    'employee_code' => '%' . $keyword . '%',
                    'rank' => '%' . $keyword . '%',
                    'institution' => '%' . $keyword . '%',
                    'department' => '%' . $keyword . '%',
                    'branch' => '%' . $keyword . '%'
                ];
            }
            if (!is_null($this->req->input('is_enabled'))) {
                if ($this->req->input('is_enabled')) {
                    $script .= 'AND is_enabled = 1 AND confirmed_at IS NOT NULL ';
                } else {
                    $script .= 'AND is_enabled = 0 OR expiry_date <= ' . date('Y-m-d') . ' ';
                }
            }
            if (!empty($actorFilter)) {
                $script .= "AND actor_code IN ('" . implode("','", $actorFilter) . "') ";
            }
            // @2-2 Count Whole
            $whole = DB::connection('main')->select('SELECT COUNT(a.user_id) whole FROM (' . $script . ') a', $blinds)[0]->whole; // No Limit
            // @2-3 Limit
            $script .= 'ORDER BY user_id DESC LIMIT :limit OFFSET :offset ';
            $blinds = [...$blinds, 'limit' => $pageLimit, 'offset' => $offset];

            // @3 Fetching
            $data = [];
            $temp = DB::connection('main')->select($script, $blinds);
            foreach ($temp as $row) {
                $data[] = [
                    'user_id' => $row->user_id,
                    'full_name' => $row->full_name,
                    'employee_code' => $row->employee_code,
                    'rank' => $row->rank,
                    'institution' => $row->institution,
                    'department' => $row->department,
                    'branch' => $row->branch,
                    'actor_code' => $row->actor_code,
                    'is_enabled' => (bool) $row->is_enabled,
                    'issue_date' => $row->issue_date,
                    'expiry_date' => $row->expiry_date,
                    'email' => $row->email,
                    'status_code' => self::status($row->confirmed_at, (bool) $row->is_enabled, $row->expiry_date)
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

    public function content($id) // [GET]
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            if (!is_numeric($id)) {
                $res->set('INPUT');
                throw new Exception();
            }

            // @1 Existing User
            $exist = DB::connection('main')->select("SELECT u.*, t.full_name created_full_name FROM users u LEFT OUTER JOIN users t ON u.created_user_id = t.user_id WHERE u.user_id = :user_id AND u.actor_code NOT IN ('THANOS')", [
                'user_id' => $id
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }
            $exist = $exist[0];

            // @#
            $res->set('OK', [
                'user_id' => $exist->user_id,
                'created_at' => $exist->created_at,
                'updated_at' => $exist->updated_at,
                'deleted_at' => $exist->deleted_at,
                'username' => $exist->username,
                'email' => $exist->email,
                'mobile_phone' => $exist->mobile_phone,
                'issue_date' => $exist->issue_date,
                'expiry_date' => $exist->expiry_date,
                'confirmed_at' => $exist->confirmed_at,
                'actor_code' => $exist->actor_code,
                'full_name' => $exist->full_name,
                'employee_code' => $exist->employee_code,
                'rank' => $exist->rank,
                'institution' => $exist->institution,
                'department' => $exist->department,
                'branch' => $exist->branch,
                'is_enabled' => (bool) $exist->is_enabled,
                'is_permanent' => (bool) $exist->is_permanent,
                'created_full_name' => $exist->created_full_name
            ]);
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function update($id) // [PUT]
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            if (!is_numeric($id)) {
                $res->set('INPUT');
                throw new Exception();
            }
            // @1 Existing User
            $exist = DB::connection('main')->select("SELECT * FROM users WHERE user_id = :user_id AND actor_code NOT IN ('THANOS', 'ADMIN') AND deleted_at IS NULL LIMIT 1", [
                'user_id' => $id
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }

            $this->clean(['full_name', 'rank', 'institution', 'department', 'branch']);
            $schema = [
                'is_enabled' => 'required|boolean'
            ];
            if ((bool) $exist[0]->is_permanent === false) {
                $schema = [
                    ...$schema,
                    'mobile_phone' => Util::rule('User', false, 'user.mobile_phone'),
                    'actor_code' => 'required|in:ADMIN,SECRET,LEADER,MEMBER',
                    'full_name' => Util::rule('User', true, 'text.name'),
                    'rank' => Util::rule('User', false, 'text.name'),
                    'institution' => Util::rule('User', false, 'text.name'),
                    'department' => Util::rule('User', false, 'text.name'),
                    'branch' => Util::rule('User', false, 'text.name')
                ];
            } else {
                // LDAP
                $schema = [
                    ...$schema,
                    'actor_code' => 'required|in:ADMIN,SECRET,LEADER,MEMBER'
                ];
            }
            $validator = Validator::make($this->req->all(), $schema);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // @2 Update User
            $data = [
                'updated_at' => Util::now(),
                'actor_code' => $this->req->input('actor_code'),
                'is_enabled' => (bool) $this->req->input('is_enabled'),
                'confirmed_at' => ((bool) $this->req->input('is_enabled') === true) ? Util::now() : null
            ];
            if ((bool) $exist[0]->is_permanent === false) {
                $data = [
                    ...$data,
                    'mobile_phone' => Util::trim($this->req->input('mobile_phone')),
                    'full_name' => $this->req->input('full_name'),
                    'rank' => Util::trim($this->req->input('rank')),
                    'institution' => Util::trim($this->req->input('institution')),
                    'department' => Util::trim($this->req->input('department')),
                    'branch' => Util::trim($this->req->input('branch'))
                ];
            }
            DB::connection('main')->table('users')->where('user_id', $id)->update($data);

            // @#
            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function delete($id) // [DELETE]
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            if (!is_numeric($id)) {
                $res->set('INPUT');
                throw new Exception();
            }
            // @1 Existing User
            $exist = DB::connection('main')->select("SELECT * FROM users WHERE user_id = :user_id AND actor_code NOT IN ('THANOS', 'ADMIN') AND deleted_at IS NULL LIMIT 1", [
                'user_id' => $id
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }

            // @2 Delete User
            event(new UserDeleteEvent((int) $id));

            // @#
            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function resetPassword($id) // [PUT]
    {
        $res = new Response(__METHOD__, $this->req->all());

        try {

            // @0 Validating
            if (!is_numeric($id)) {
                $res->set('INPUT');
                throw new Exception();
            }
            $validator = Validator::make($this->req->all(), [
                'password' => Util::rule('User', true, 'user.password')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // @1 Existing User
            $exist = DB::connection('main')->select("SELECT * FROM users WHERE user_id = :user_id AND actor_code NOT IN ('THANOS') AND deleted_at IS NULL LIMIT 1", [
                'user_id' => $id
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }

            // @2 Update Password
            DB::connection('main')->table('users')->where('user_id', $id)->update([
                'updated_at' => Util::now(),
                'is_reset' => true,
                'password' => Hash::make($this->req->input('password'))
            ]);

            // @#
            $res->set('OK');
        } catch (Exception $e) {
            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    // Function

    private static function status($confirm, bool $enable, $expiry): string
    {
        $status = ($enable) ? 'ACTIVE' : 'INACTIVE';
        if (empty($confirm)) {
            $status = 'PENDING';
        }
        if (!empty($expiry)) {
            if ($expiry <= date('Y-m-d')) {
                $status = 'INACTIVE';
            }
        }
        return $status;
    }
}
