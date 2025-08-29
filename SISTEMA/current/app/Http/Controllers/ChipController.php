<?php

namespace App\Http\Controllers;
use Redirect;
use App\Models\Chip;
use Illuminate\Http\Request;



class ChipController extends Controller
{


 

    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $chip = Chip::all();
        return view('superadmin.chips.chip', compact('chip'));
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
        return view('superadmin.chips.form');
    }

    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        

        try {
            $chip = Chip::firstOrNew([
            
                'fornecedor' => $request->fornecedor,
                'operadora' => $request->operadora,
                'numero' => $request->numero,
                'imei' => $request->imei,
                'modelo' => $request->modelo,
                'equipamento' => $request->equipamento                
            ]);
            $chip->save();

        } catch ( \Illuminate\Database\QueryException $e) {
            return Redirect::to('admin/users/clients/error');

        }

        return Redirect::to('admin/users/clients/chip');
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
        $chip = Chip::findOrFail($id);
 
        if ($chip) {
            return view('superadmin.chips.form', compact('chip'));
        } else {
            return Redirect::to('admin/users/clients/chip');
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
        $chip = Chip::where('id', $id)->update($request->except('_token', '_method'));
 
        if ($chip) {
            return Redirect::to('admin/users/clients/chip');
        }
    }

    /**
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $chip = Chip::where('id', $id)->delete();
       
        if ($chip) {
            return Redirect::to('admin/users/clients/chip');
    }
    }



    //////////UPLOAD ARQUIVOPS

    
}

