<?php

namespace App\Modules\User;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Validator;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Libraries\Response;
use App\Libraries\Util;
use App\Events\UserDeleteEvent;
use App\Libraries\FileAttached;

class AuthController extends Controller
{

    private const TOKEN_EXP_TIME = 6; // Hours

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    // API

    public function login() // [POST]
    {

        $res = new Response(__METHOD__, $this->req->all());
        try {

            $this->clean([
                'username'
            ]);

            // @0 Validating
            $validator = Validator::make($this->req->all(), [
                'username' => Util::rule(true, 'user.username'),
                'password' => Util::rule(true, 'user.password')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // @1 Existing User, It Could be AD or Contract
            $userFetch = DB::connection('main')->select('SELECT * FROM users WHERE (username = :username OR email = :email) AND deleted_at IS NULL LIMIT 1', [
                'username' => $this->req->input('username'),
                'email' => $this->req->input('username')
            ]);
            if (!$userFetch) {
                $res->set('AUTH');
                throw new Exception();
            }
            $userFetch = $userFetch[0];

            // @3 Check Password
            if ((bool) $userFetch->is_permanent === false) {
                // THANOS MUST BE NOT PERMANENT
                if (!Hash::check($this->req->input('password'), $userFetch->password)) {
                    $res->set('AUTH');
                    throw new Exception();
                }
            } else {
                // AD FOR PERMANENT, IN DEV AND UAT LDAP WILL BE PASSED WITHOUT THE CORRECTLY PASSWORD
                if (env('APP_ENV') === 'production') {
                    if (!self::ldap($this->req->input('username'), $this->req->input('password'))) {
                        $res->set('AUTH');
                        throw new Exception();
                    }
                }
            }

            // @4 Check Enable and Confirmed
            if (!$userFetch->is_enabled || empty($userFetch->confirmed_at)) {
                $res->set('AUTH');
                throw new Exception();
            }

            // @5 Check Expiry Date
            if (!empty($userFetch->expiry_date) && date('Y-m-d') >= $userFetch->expiry_date) {
                $res->set('EXPIRED');
                throw new Exception();
            }

            // @#
            $res->set('OK', [
                'token' => self::genToken($userFetch->actor_code, $userFetch->user_id),
                'is_reset' => (bool) $userFetch->is_reset
            ]);
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function logout() // [POST]
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @1 Delete a Token
            $status = DB::connection('main')->table('active_sessions')->where('user_id', $this->req->input('auth_user_id'))->where('token', $this->req->input('auth_token'))->delete();
            if (!$status) {
                $res->set('PROGRESS');
                throw new Exception();
            }

            // @#
            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function content() // [GET]
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @1 Get User Data
            $exist = DB::connection('main')->select('SELECT * FROM users WHERE user_id = :user_id', [
                'user_id' => $this->req->input('auth_user_id')
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }
            $exist = $exist[0];
            $image = null;
            foreach (FileAttached::getModule('PROFILE', $exist->user_id) as $file) {
                $image = $file['url'];
            }

            // @#
            $res->set('OK', [
                'user_id' => $exist->user_id,
                'created_at' => $exist->created_at,
                'updated_at' => $exist->updated_at,
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
                'image_url' => $image
            ]);
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function update() // [PUT]
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $this->clean(['full_name', 'rank', 'institution', 'department', 'branch']);
            $validator = Validator::make($this->req->all(), [
                'mobile_phone' => Util::rule(false, 'user.mobile_phone'),
                'full_name' => Util::rule(true, 'text.name'),
                'rank' => Util::rule(false, 'text.name'),
                'institution' => Util::rule(false, 'text.name'),
                'department' => Util::rule(false, 'text.name'),
                'branch' => Util::rule(false, 'text.name')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }
            // @1 Existing User
            $exist = DB::connection('main')->select("SELECT * FROM users WHERE user_id = :user_id", [
                'user_id' => $this->req->input('auth_user_id')
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }

            // @2 Update User
            DB::connection('main')->table('users')->where('user_id', $this->req->input('auth_user_id'))->update([
                'updated_at' => Util::now(),
                'mobile_phone' => Util::trim($this->req->input('mobile_phone')),
                'full_name' => $this->req->input('full_name'),
                'rank' => Util::trim($this->req->input('rank')),
                'institution' => Util::trim($this->req->input('institution')),
                'department' => Util::trim($this->req->input('department')),
                'branch' => Util::trim($this->req->input('branch'))
            ]);

            // @#
            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function delete() // [DELETE]
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @1 Delete User
            event(new UserDeleteEvent((int) $this->req->input('auth_user_id')));

            // @#
            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function updatePassword() // [PUT]
    {
        $res = new Response(__METHOD__, $this->req->all());

        try {

            // @0 Validating
            $validator = Validator::make($this->req->all(), [
                'password' => Util::rule(true, 'user.password')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // @1 Update Password
            DB::connection('main')->table('users')->where('user_id', $this->req->input('auth_user_id'))->update([
                'updated_at' => Util::now(),
                'is_reset' => false,
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

    private static function genToken(string $actor, string $id): string
    {
        // Create a Token
        $expire = time() + (3600 * self::TOKEN_EXP_TIME);
        $token = JWT::encode([
            'iss' => env('APP_NAME'),
            'sub' => 'AUTH',
            'exp' => $expire,
            'nbf' => time(),
            'actor_code' => $actor,
            'user_id' => $id
        ], env('JWT_KEY'));
        // Log
        DB::connection('main')->table('active_sessions')->insert([
            'created_at' => Util::now(),
            'user_id' => $id,
            'token' => $token,
            'expired_at' => date('Y-m-d H:i:s', $expire)
        ]);
        return $token;
    }

    private static function ldap(string $username, string $password): bool
    {

        try {

            $connect = ldap_connect(env('AD_HOST'));
            ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($connect, LDAP_OPT_REFERRALS, 0);
            $login = ldap_bind($connect, $username . '@thaipbs.or.th', $password);
            if ($login !== true) {
                throw new Exception();
            }
            $filter = '(&(objectClass=user)(samaccountname=' . $username . '))';
            $data = ldap_get_entries($connect, ldap_search($connect, 'OU=Thai PBS,DC=thaipbs,DC=or,DC=th', $filter));
            if (empty(Util::trim($data[0]['userprincipalname'][0]))) {
                throw new Exception();
            }

            $email = (string) Util::trim($data[0]['userprincipalname'][0]); // Not Used

        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}