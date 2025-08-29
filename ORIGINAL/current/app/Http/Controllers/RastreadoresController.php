<?php

namespace App\Http\Controllers;
use Redirect;
use App\Models\Rastreadores;
use Illuminate\Http\Request;


class RastreadoresController extends Controller
{
    
    public function error()
    {
        return view('superadmin.error');
    }
    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $rastreador = Rastreadores::all();
        return view('superadmin.rastreadores.rastreador', compact('rastreador'));
    }

    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('superadmin.rastreadores.form');
    }

    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        try {
        $rastreador = Rastreadores::firstOrNew([
            
            'imei' => $request->imei,
            'modelo' => $request->modelo,
            'equipamento' => $request->equipamento
        ]);


        $rastreador->save();
     
    } catch ( \Illuminate\Database\QueryException $e) {
        return Redirect::to('admin/users/clients/error');

    }

    return Redirect::to('admin/users/clients/rastreadores');
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
        $rastreador = Rastreadores::findOrFail($id);
 
        if ($rastreador) {
            return view('superadmin.rastreadores.form', compact('rastreador'));
        } else {
            return Redirect::to('admin/users/clients/rastreadores');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $rastreador = Rastreadores::where('id', $id)->update($request->except('_token', '_method'));
 
        if ($rastreador) {
            return Redirect::to('admin/users/clients/rastreadores');
        }
    }

    /**
     
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $rastreador = Rastreadores::where('id', $id)->delete();
       
        if ($rastreador) {
            return Redirect::to('admin/users/clients/rastreadores');
    }
    }
}
