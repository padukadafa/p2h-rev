<?php

namespace App\Http\Controllers;

use App\Models\BahanBakar;
use App\Models\HargaBahanBakar;
use App\Http\Resources\BahanBakarResource;
use App\Models\LokasiKerja;
use Illuminate\Http\Request;

class BahanBakarApiController extends Controller
{
    public function create(Request $request) {
        
        $forms = $request->validate([
            'driver_id' => 'required',
            'kendaraan_id' => 'required',
            'jarak' => 'required',
            'lokasi_kerja_id' => 'required',
            'image' => 'required|image|file',
        ]);
        if ($request->file('image')) {
            $forms['foto_url'] = "https://p2hdriver.satriabahana.co.id/storage/".$request->file('image')->store('BBMImages');
        }
        if (!isset(auth()->user()->lokasi_kerja_id)) {
            return response('lokasi kerja belum ditentukan. Harap lapor kepada admin',500);
        }
        $forms['liter'] = $forms['jarak'] /LokasiKerja::where('id',$forms['lokasi_kerja_id'])->get()->first()->bbm;
        BahanBakar::create($forms);
        return response([
            'liter' => $forms['liter'],
            'message' => 'berhasil membuat laporan',
        ]);

    }
    public function index() {
        $bbm = [];
        if (auth()->user()->role == 'driver') {
            $bbm = BahanBakar::where('driver_id',auth()->user()->driver_id)->get();
        } else {
            $bbm = BahanBakar::where('lokasi_kerja_id',auth()->user()->lokasi_kerja_id)->get();
        }
        
        return response(BahanBakarResource::collection($bbm));
    }
    public function driver_history() {
        $bbm = BahanBakar::where('driver_id',auth()->user()->driver_id)->get();
        return response(BahanBakarResource::collection($bbm));
    }
}
