<?php

namespace App\Modules\Meeting;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Libraries\Response;
use App\Libraries\Util;

class TemplateController extends Controller
{

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    // API

    public function list()
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            $data = [];

            foreach (DB::connection('main')->select('SELECT * FROM meeting_templates ORDER BY template_id ASC') as $template) {
                $list = [
                    'topic' => [],
                    'position' => []
                ];
                foreach (DB::connection('main')->select('SELECT * FROM template_fills WHERE template_id = :template_id ORDER BY no ASC', ['template_id' => $template->template_id]) as $row) {
                    $list[($row->type_code === 'T') ? 'topic' : 'position'][] = [
                        'fill_id' => $row->fill_id,
                        'name' => $row->name
                    ];
                }
                $data[] = [
                    'template_id' => $template->template_id,
                    'name' => $template->name,
                    'data' => $list
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

    public function create()
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            $this->clean(['name']);

            $validator = Validator::make($this->req->all(), [
                'name' => Util::rule('Meeting', true, 'text.title')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // Create
            $id = DB::connection('main')->table('meeting_templates')->insertGetId([
                'created_at' => Util::now(),
                'name' => $this->req->input('name')
            ], 'template_id');
            // @#
            $res->set('CREATED', [
                'template_id' => $id
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

            $validator = Validator::make(['template_id' => $id], [
                'template_id' => Util::rule('Meeting', true, 'primary')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }
            $exist = DB::connection('main')->select('SELECT * FROM meeting_templates WHERE template_id = :template_id', [
                'template_id' => $id
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }
            $exist = $exist[0];

            // List
            $list = [
                'topic' => [],
                'position' => []
            ];
            foreach (DB::connection('main')->select('SELECT * FROM template_fills WHERE template_id = :template_id ORDER BY no ASC', ['template_id' => $id]) as $row) {
                $list[($row->type_code === 'T') ? 'topic' : 'position'][] = [
                    'fill_id' => $row->fill_id,
                    'name' => $row->name
                ];
            }

            // @#
            $res->set('OK', [
                'template_id' => $exist->template_id,
                'name' => $exist->name,
                'data' => $list
            ]);
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

            $this->clean(['name']);

            $validator = Validator::make([...$this->req->all(), 'template_id' => $id], [
                'template_id' => Util::rule('Meeting', true, 'primary'),
                'name' => Util::rule('Meeting', true, 'text.title')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            $exist = DB::connection('main')->select('SELECT * FROM meeting_templates WHERE template_id = :template_id', [
                'template_id' => $id
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }

            DB::connection('main')->table('meeting_templates')->where('template_id', $id)->update([
                'updated_at' => Util::now(),
                'name' => $this->req->input('name')
            ]);

            // @#
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

            $validator = Validator::make(['template_id' => $id], [
                'template_id' => Util::rule('Meeting', true, 'primary')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }
            $exist = DB::connection('main')->select('SELECT * FROM meeting_templates WHERE template_id = :template_id', [
                'template_id' => $id
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }

            // Delete
            DB::connection('main')->table('template_fills')->where('template_id', $id)->delete();
            DB::connection('main')->table('meeting_templates')->where('template_id', $id)->delete();

            // @#
            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }
}
