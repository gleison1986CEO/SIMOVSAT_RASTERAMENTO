<?php

namespace App\Http\Controllers;
use Redirect;
use App\Models\Estoque;
use Illuminate\Http\Request;



class EstoqueController extends Controller
{



    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $estoque = Estoque::query()
            ->where('iccid', 'LIKE', "%{$search}%")
            ->orWhere('imei', 'LIKE', "%{$search}%")
            ->orWhere('chip', 'LIKE', "%{$search}%")
            ->orWhere('modelo', 'LIKE', "%{$search}%")
            ->orWhere('hash', 'LIKE', "%{$search}%")
            ->orWhere('status', 'LIKE', "%{$search}%")
            ->get();
        return view('superadmin.estoque.estoque', compact('estoque'));
    }


    public function error()
    {
        return view('superadmin.error');
    }


    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('superadmin.estoque.form');
    }

    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        try {
            $estoque = Estoque::firstOrNew([
            
                'iccid' => $request->iccid,
                'chip' => $request->chip,
                'imei' => $request->imei,       
                'modelo' => $request->modelo,
                'hash' => $request->hash,
                'status' => $request->status,                 
            ]);
            
            $estoque->save();

        } catch ( \Illuminate\Database\QueryException $e) {
            return Redirect::to('admin/users/clients/error');

        }

        return Redirect::to('admin/users/clients/estoque');
    }

    /**
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $estoque = Estoque::findOrFail($id);
 
        if ($estoque) {
            return view('superadmin.estoque.form', compact('estoque'));
        } else {
            return Redirect::to('admin/users/clients/estoque');
        }
    }

    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $estoque = Estoque::where('id', $id)->update($request->except('_token', '_method'));
 
        if ($estoque) {
            return Redirect::to('admin/users/clients/estoque');
        }
    }

    /**
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $estoque = Estoque::where('id', $id)->delete();
       
        if ($estoque) {
            return Redirect::to('admin/users/clients/estoque');
    }
    }



    //////////UPLOAD ARQUIVOPS

    
}

