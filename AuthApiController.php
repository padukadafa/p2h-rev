<?php

namespace App\Http\Controllers;

use App\Http\Resources\DriverResource;
use App\Http\Resources\UserResource;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthApiController extends Controller
{

    public function pegawai_login(Request $request) {
        $fields = $request->validate([
            'nik' => 'required|string',
            'password' => 'required|string',

        ]);
        $pegawai = User::where('nik',$fields['nik'])->first();
        
        if (!$pegawai || !Hash::check($fields['password'],$pegawai->password)) {
            return response([
                'message' => 'Nik atau password tidak sesuai',401
            ]);
        }
        if ($pegawai->role == 'superadmin') {
            return response([
                'message' => 'Nik atau password tidak sesuai',401
            ]); 
        }
        $token = $pegawai->createToken('pegawaiToken')->plainTextToken;
        $response = [
            'user' => UserResource::make($pegawai),
            'token' => $token,
        ];
        return response($response,201);
    }
    public function pegawai_logout() {
        auth()->user()->tokens()->delete();

        return [
            'message' => 'Logged out',
        ];
    }
    public function get_driver($id) {
        $driver = Driver::where('id',$id)->get()->first();

        return response($driver);

    }
    public function get_pegawai() {
        return response(UserResource::make(auth()->user()));
    }
    public function forgot_password(Request $request) {
        $status = Password::sendResetLink(
            $request->only('email')
        );
     
        return $status === Password::RESET_LINK_SENT
                    ? response('link reset password telah dikirim ke email pengguna')
                    : response($status,500);
    }
    public function driver_login(Request $request) {
        $fields = $request->validate([
            'nik' => 'required|string',
            'password' => 'required|string',

        ]);
        $driver = Driver::where('nik',$fields['nik'])->first();

        if (!$driver || !Hash::check($fields['password'],$driver->password)) {
            return response([
                'message' => 'Nik atau password tidak sesuai',401
            ]);
        }
        $token = $driver->createToken('driverToken')->plainTextToken;
        $response = [
            'user' => DriverResource::collection($driver),
            'token' => $token,
        ];
        return response($response,201);
    }
    public function driver_logout(Request $request) {
        Auth::logout();
        return [
            'message'=> 'Logged out'
        ];
    }
    public function test(Request $request) {
        return response(['message' => "sukses"],201);
    }
    public function pegawai_update(Request $request) {
        User::where('id',auth()->user()->id)->update($request->all());
        if (auth()->user()->driver_id) {
            Driver::where('id',auth()->user()->driver_id)->update($request->all());
        }
        return response([
            'data' => User::where('id',auth()->user()->id)->get()->first(),
            'message' => "Berhasil mengupdate data",
        ]);
    }
    public function ganti_password(Request $request) {
        $fields = $request->all();
        $user = User::where('id',auth()->user()->id)->first();
        if (!$user | !Hash::check($fields['password'],$user->password)) {
            return response([
                'status' => 'wrong-password',
            ]);
        }
        User::where('id',auth()->user()->id)->update([
            'password'=> bcrypt($fields['new_password']),
        ]);
        return response([
            'status' => 'berasil',
        ]);
    }
    public function update_photo_profile(Request $request) {
        $forms = [];
        if ($request->file('image')) {
            $forms['foto_url'] = "https://p2hdriver.satriabahana.co.id/".$request->file('image')->store('profile');
        }
        
        User::where('id',auth()->user()->id)->update($forms);
        if (auth()->user()->driver_id) {
            Driver::where('id',auth()->user()->driver_id)->update($forms);
        }
        return response($forms['foto_url']);
    }
}
