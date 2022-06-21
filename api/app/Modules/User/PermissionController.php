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
use Carbon\Carbon;

class PermissionController extends Controller
{

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    // API

    public function content($actor) // [GET]
    {

        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $validator = Validator::make(['actor_code' => $actor], [
                'actor_code' => Util::rule('User', true, 'user.actor_code')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            $temp = DB::connection('main')->select('SELECT * FROM permission_menus WHERE actor_code = :actor_code', [
                'actor_code' => $actor
            ]);
            $mock = [];
            foreach ($temp as $row) {
                $mock[$row->menu_code][$row->func_code] = [
                    'is_locked' => false,
                    'is_enabled' => (bool) $row->is_enabled
                ];
            }

            $data = [];
            $index = 0;
            foreach (['meeting', 'proposal', 'dashboard', 'member', 'permission'] as $menu) {
                $data[$index]['menu'] = $menu;
                foreach (['access', 'create', 'update', 'delete', 'search', 'approve', 'view'] as $func) {
                    $data[$index]['function'][] = [
                        'name' => $func,
                        'is_locked' => (isset($mock[$menu][$func])) ? $mock[$menu][$func]['is_locked'] : true,
                        'is_enabled' => (isset($mock[$menu][$func])) ? $mock[$menu][$func]['is_enabled'] : false
                    ];
                }
                $index++;
            }

            // @#
            $res->set('OK', $data);
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function update($actor) // [PUT]
    {

        $res = new Response(__METHOD__, $this->req->all());
        try {

            // @0 Validating
            $validator = Validator::make([...$this->req->all(), 'actor_code' => $actor], [
                'actor_code' => Util::rule('User', true, 'user.actor_code'),
                'list.*.menu_code' => Util::rule('User', true, 'permission.menu_code'),
                'list.*.func_code' => Util::rule('User', true, 'permission.func_code'),
                'list.*.is_enabled' => 'required|boolean',
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            foreach ($this->req->input('list') as $row) {
                DB::connection('main')->table('permission_menus')->where('actor_code', $actor)->where('menu_code', $row['menu_code'])->where('func_code', $row['func_code'])->update([
                    'is_enabled' => (bool) $row['is_enabled']
                ]);
            }

            // @#
            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }
}