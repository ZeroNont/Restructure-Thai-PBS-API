<?php

namespace App\Modules\Meeting;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Libraries\Response;
use App\Libraries\Util;

class TemplateFillController extends Controller
{

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    public function create()
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {

            $this->clean(['name']);

            $validator = Validator::make($this->req->all(), [
                'template_id' => Util::rule('Meeting', true, 'primary'),
                'type_code' => Util::rule('Meeting', true, 'template.type_code'),
                'name' => Util::rule('Meeting', true, 'text.title'),
                'no' => Util::rule('Meeting', true, 'number.no')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // Existing
            $template = DB::connection('main')->select('SELECT * FROM meeting_templates WHERE template_id = :template_id', [
                'template_id' => $this->req->input('template_id')
            ]);
            if (!$template) {
                $res->set('EXIST');
                throw new Exception();
            }

            // Create
            $id = DB::connection('main')->table('template_fills')->insertGetId([
                'template_id' => $this->req->input('template_id'),
                'type_code' => $this->req->input('type_code'),
                'name' => $this->req->input('name'),
                'no' => $this->req->input('no')
            ], 'fill_id');
            // @#
            $res->set('CREATED', [
                'fill_id' => $id
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

            $validator = Validator::make([...$this->req->all(), 'fill_id' => $id], [
                'fill_id' => Util::rule('Meeting', true, 'primary'),
                'name' => Util::rule('Meeting', true, 'text.title'),
                'no' => Util::rule('Meeting', true, 'number.no')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // Existing
            $exist = DB::connection('main')->select('SELECT * FROM template_fills WHERE fill_id = :fill_id', [
                'fill_id' => $id
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }

            // Update
            DB::connection('main')->table('template_fills')->where('fill_id', $id)->update([
                'name' => $this->req->input('name'),
                'no' => $this->req->input('no')
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

            $validator = Validator::make(['fill_id' => $id], [
                'fill_id' => Util::rule('Meeting', true, 'primary')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            // Existing
            $exist = DB::connection('main')->select('SELECT * FROM template_fills WHERE fill_id = :fill_id', [
                'fill_id' => $id
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }

            // Delete
            DB::connection('main')->table('template_fills')->where('fill_id', $id)->delete();
            // @#
            $res->set('OK');
        } catch (Exception $e) {

            $res->debug($e->getMessage());
        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }
}
