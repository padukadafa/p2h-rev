<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\KerusakanKendaraan;
use App\Http\Resources\KerusakanKendaraanResource;
use Illuminate\Http\Request;

class KendaraanApiController extends Controller
{
    public function index() {
        $kendaraan = Kendaraan::where('lokasi_kerja_id',auth()->user()->lokasi_kerja_id)->get();
        return $kendaraan;
    }
    public function bermasalah() {
        $kendaraan = [];
        if (auth()->user()->role == 'driver') {
            $kendaraan = Kendaraan::where('kendaraan_id',auth()->user()->driver->kendaraan_id)->get();
        } else {
            $kendaraan = Kendaraan::where('lokasi_kerja_id',auth()->user()->lokasi_kerja_id)->where('status','bermasalah')->get();
        }
        
        return $kendaraan;
    }
    public function driver_bermasalah() {
        if (auth()->user()->role != "driver") {
            $kendaraan =KerusakanKendaraan::where('lokasi_kerja_id',auth()->user()->lokasi_kerja_id)->get();
            return response(KerusakanKendaraanResource::collection($kendaraan)); 
        }
        $kendaraan = KerusakanKendaraan::where('kendaraan_id',auth()->user()->driver->kendaraan_id)->get();
        return response(KerusakanKendaraanResource::collection($kendaraan));
    }
    public function ready() {
        $kendaraan = Kendaraan::where('lokasi_kerja_id',auth()->user()->lokasi_kerja_id)->doesntHave('driver')->get();
        
        return $kendaraan;
    }
    public function repaired(Request $request) {
        $kerusakan = KerusakanKendaraan::where('id', $request['id'])->first();
        KerusakanKendaraan::where('id', $request['id'])->update(['status'=>'ready']);
        Kendaraan::where('id', $kerusakan->kendaraan_id)->update([
            'status' => 'ready',
            'deskripsi_status' => "",
            'kerusakan_kendaraan_id' => null,
        ]);
        return response('berhasil');
    }
    public function lapor_bermasalah(Request $request) {
        $forms = $request->validate([
            'kendaraan_id' => 'required',
            'deskripsi' => 'required',
            'image' => 'required|image|file',
        ]);
        if ($request->file('image')) {
            $forms['foto_url'] = "https://p2hdriver.satriabahana.co.id/storage/".$request->file('image')->store('KerusakanImages');
        }
        $forms['status'] = 'bermasalah';
        if (!isset(auth()->user()->lokasi_kerja_id)) {
            return response('lokasi kerja belum ditentukan. Harap lapor kepada admin',500);
        }
        $forms['lokasi_kerja_id'] = auth()->user()->lokasi_kerja_id;
        
        $result = KerusakanKendaraan::create($forms);
        // return $result;
        Kendaraan::where('id',$forms['kendaraan_id'])->update(['status' => 'bermasalah','kerusakan_kendaraan_id' => $result->id]);
        return response('berhasil');
    }
    public function log_bermasalah() {
        if (auth()->user()->role != "driver") {
            return response(KerusakanKendaraan::where('lokasi_kerja_id',auth()->user()->lokasi_kerja_id)->get()); 
        }
        return response(KerusakanKendaraan::where('lokasi_kerja_id',auth()->user()->lokasi_kerja_id)::where('driver_id',auth()->user()->driver->kendaraan_id ?? "")->get());
    }
    public function get_kendaraan($id) {
        $kendaraan = Kendaraan::where('id',$id)->get()->first();
        return response($kendaraan);
    }
}
