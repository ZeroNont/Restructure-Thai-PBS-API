<?php

namespace App\Modules\User;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Libraries\Response;
use App\Libraries\Util;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\InviteMail;
use App\Mail\WelcomeMail;
use Carbon\Carbon;

class InviteController extends Controller
{

    private const EMAIL_EXP_TIME = 24; // Hours

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    // API

    public function create() // [POST]
    {

        $res = new Response(__METHOD__, $this->req->all());
        try {

            $this->clean([
                'full_name',
                'email',
                'rank',
                'institution',
                'department',
                'branch',
                'issue_date',
                'expiry_date',
                'mobile_phone'
            ]);
            // @0 Validating
            $schema = [
                'full_name' => Util::rule('User', true, 'text.name'),
                'email' => Util::rule('User', true, 'user.email'),
                'mobile_phone' => Util::rule('User', false, 'user.mobile_phone'),
                'actor_code' => 'required|in:ADMIN,SECRET,LEADER,MEMBER',
                'rank' => Util::rule('User', false, 'text.name'),
                'institution' => Util::rule('User', false, 'text.name'),
                'department' => Util::rule('User', false, 'text.name'),
                'branch' => Util::rule('User', false, 'text.name'),
                'is_permanent' => 'required|boolean'
            ];
            if ($this->req->input('is_permanent') === false) {
                $schema = [
                    ...$schema,
                    'issue_date' => 'nullable|date_format:Y-m-d|after_or_equal:' . date('Y-m-d'),
                    'expiry_date' => 'nullable|date_format:Y-m-d|after:issue_date'
                ];
            }
            $validator = Validator::make($this->req->all(), $schema);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // @1 Existing User
            $exist = DB::connection('main')->select('SELECT * FROM users WHERE email = :email', [
                'email' => $this->req->input('email')
            ]);
            if ($exist) {
                $res->set('EXIST');
                throw new Exception();
            }

            // @2 Create an Account
            $user = DB::connection('main')->table('users')->insertGetId([
                // Core
                'created_at' => Util::now(),
                // Auth
                'username' => $this->req->input('email'),
                'email' => $this->req->input('email'),
                // Status
                'issue_date' => ($this->req->input('is_permanent') === true) ? null : Util::trim($this->req->input('issue_date')),
                'expiry_date' => ($this->req->input('is_permanent') === true) ? null : Util::trim($this->req->input('expiry_date')),
                'is_enabled' => false,
                // Profile
                'actor_code' => $this->req->input('actor_code'),
                'full_name' => $this->req->input('full_name'),
                'mobile_phone' => Util::trim($this->req->input('mobile_phone')),
                'rank' => Util::trim($this->req->input('rank')),
                'institution' => Util::trim($this->req->input('institution')),
                'department' => Util::trim($this->req->input('department')),
                'branch' => Util::trim($this->req->input('branch')),
                'is_permanent' => $this->req->input('is_permanent')
            ], 'user_id');

            // @3 Sending an Email to Confirm an Invitation
            self::sending($user, $this->req->input('email'), $this->req->input('full_name'), (bool) $this->req->input('is_permanent'));

            // @#
            $res->set('CREATED');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function resend() // [POST]
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

            // @1 Existing and Not Confirmed User
            $userExist = DB::connection('main')->select('SELECT * FROM users WHERE user_id = :user_id AND confirmed_at IS NULL', [
                'user_id' => $this->req->input('user_id')
            ]);
            if (!$userExist) {
                $res->set('EXIST');
                throw new Exception();
            }

            // @2 Create a New Invitation and Sending
            self::sending($this->req->input('user_id'), $userExist[0]->email, $userExist[0]->full_name, (bool) $userExist[0]->is_permanent);

            // @#
            $res->set('CREATED');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function confirm(string $code) // [PUT]
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            if (!preg_match('/^[0-9a-zA-Z]{256}$/s', $code)) {
                $res->set('INPUT');
                throw new Exception();
            }
            $this->clean(['full_name', 'rank', 'institution', 'department', 'branch', 'mobile_phone']);
            $validator = Validator::make($this->req->all(), [
                'full_name' => Util::rule('User', true, 'text.name'),
                'password' => Util::rule('User', true, 'user.password'),
                'rank' => Util::rule('User', false, 'text.name'),
                'institution' => Util::rule('User', false, 'text.name'),
                'department' => Util::rule('User', false, 'text.name'),
                'branch' => Util::rule('User', false, 'text.name'),
                'mobile_phone' => Util::rule('User', false, 'user.mobile_phone'),
                'policy_version' => Util::rule('User', true, 'user.policy_version')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }
            $policy = DB::connection('main')->select('SELECT * FROM policies WHERE policy_version = :policy_version AND is_enabled = 1', [
                'policy_version' => $this->req->input('policy_version')
            ]);
            if (!$policy) {
                $res->set('INPUT');
                throw new Exception();
            }

            // @1 Find a Reference Code
            $exist = DB::connection('main')->select('SELECT * FROM invitations WHERE ref_code = :ref_code AND confirmed_at IS NULL LIMIT 1', [
                'ref_code' => $code
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }
            $exist = $exist[0];

            // @2 Check Expire
            if (strtotime($exist->expired_at) <= strtotime(Util::now())) {
                $res->set('EXPIRED');
                throw new Exception();
            }

            // @3 Update Confirm
            DB::connection('main')->table('invitations')->where('user_id', $exist->user_id)->update([
                'expired_at' => Util::now(),
                'confirmed_at' => Util::now()
            ]);

            // @4 Update Info and Enable User
            DB::connection('main')->table('users')->where('user_id', $exist->user_id)->update([
                'updated_at' => Util::now(),
                'confirmed_at' => Util::now(),
                'is_enabled' => true,
                'full_name' => $this->req->input('full_name'),
                'password' => Hash::make($this->req->input('password')),
                'rank' => Util::trim($this->req->input('rank')),
                'institution' => Util::trim($this->req->input('institution')),
                'department' => Util::trim($this->req->input('department')),
                'branch' => Util::trim($this->req->input('branch')),
                'mobile_phone' => Util::trim($this->req->input('mobile_phone')),
                'policy_version' => $this->req->input('policy_version')
            ]);

            // @#
            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function content(string $code) // [GET]
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            if (!preg_match('/^[0-9a-zA-Z]{256}$/s', $code)) {
                $res->set('INPUT');
                throw new Exception();
            }

            // @1 Find a Reference Code
            $exist = DB::connection('main')->select('SELECT u.rank, u.institution, u.department, u.branch, u.email, u.full_name, u.mobile_phone, i.expired_at FROM users u INNER JOIN invitations i ON u.user_id = i.user_id WHERE i.ref_code = :ref_code LIMIT 1', [
                'ref_code' => $code
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }
            $exist = $exist[0];

            // @2 Check Expire
            if (strtotime($exist->expired_at) <= strtotime(Util::now())) {
                $res->set('EXPIRED');
                throw new Exception();
            }

            // @#
            $res->set('OK', [
                'email' => $exist->email,
                'full_name' => $exist->full_name,
                'rank' => $exist->rank,
                'institution' => $exist->institution,
                'department' => $exist->department,
                'branch' => $exist->branch,
                'mobile_phone' => $exist->mobile_phone
            ]);
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function policy() // [GET]
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @1 Get Policy Data
            $exist = DB::connection('main')->select('SELECT * FROM policies WHERE is_enabled = 1 ORDER BY CASE WHEN updated_at IS NULL THEN created_at ELSE updated_at END DESC LIMIT 1');
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }
            $exist = $exist[0];

            // @#
            $res->set('OK', [
                'policy_version' => $exist->policy_version,
                'detail' => $exist->detail
            ]);
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function permanentCreate()
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

            // @1 Existing and Not Confirmed User
            $userExist = DB::connection('main')->select("SELECT * FROM users WHERE user_id = :user_id AND actor_code != :actor_code AND is_permanent = 1 AND email IS NOT NULL", [
                'user_id' => $this->req->input('user_id'),
                'actor_code' => 'THANOS'
            ]);
            if (!$userExist) {
                $res->set('EXIST');
                throw new Exception();
            }

            Mail::to($userExist[0]->email)->send(new WelcomeMail([
                'to' => $userExist[0]->full_name,
                'url' => env('WELCOME_EMAIL_URL')
            ]));

            // @#
            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    // Function

    private static function sending(int $user, string $email, string $to, bool $permanent): bool // Sending an Email
    {
        $reference = Util::genStr('INVITE');
        // Set Expired Time to Previous Invitations
        DB::connection('main')->table('invitations')->where('user_id', $user)->update([
            'expired_at' => Util::now()
        ]);
        // Create a New Reference Code
        $before = Carbon::now()->addHours(self::EMAIL_EXP_TIME);
        DB::connection('main')->table('invitations')->insert([
            'created_at' => Util::now(),
            'user_id' => $user,
            'ref_code' => $reference,
            'expired_at' => $before
        ]);
        $status = Mail::to($email)->send(new InviteMail([
            'permanent' => $permanent,
            'to' => $to,
            'before' => Util::convertDateFormatThai($before, 'INVITE'),
            'url' => env('INVITE_EMAIL_CONFIRM_URL') . '/' . $reference // MUST CHANGE
        ]));
        return ($status) ? true : false;
    }
}
