<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Middleware\Authenticate;
use Facade\FlareClient\Http\Response;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // Fungsi u/ login 
    public function login(Request $request)
    {
        try {
            // validasi input
            $request->validate([
                'email' => 'email|required',
                'password' => 'required',
            ]);

            // mengecek login / kredential
            $credentials = request(['email', 'password']);
            if (!Auth::attempt($credentials)) {
                return ResponseFormatter::error([
                    'message' => 'Unauthorized'
                ], 'Authentication Failed', 500);
            }

            // Jika hash tidak sesuai maka beri error
            $user = User::where('email', $request->email)->first();
            if (!Hash::check($request->password, $user->password, [])) {
                throw new \Exception('Invalid Credentials');
            }
            // Jika Berhasil maka Login
            $tokenResult = $user->createToken('authToken')->plainTextToken;
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 'Authenticated');
        } catch (Exception $error) {
            return ResponseFormatter::error(
                [
                    'message' => 'Something wen wrong',
                    'error' => $error,
                ],
                'Authentication Failed',
                500
            );
        }
    }

    //Fungsi register 
    public function register(Request $request)
    {
        try {
            // validasi
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unix:users'],
                'password' => $this->passwordRules()
            ]);

            // Jika validasi sudah benar maka membuat user (membuat data)
            User::create([
                // field yang ada di database
                'name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
                'houseNumber' => $request->houseNumber,
                'phoneNumber' => $request->phoneNumber,
                'city' => $request->city,
                'password' => Hash::make($request->password),
            ]);

            // ambil data yang telah disimpan
            $user = User::where('email', $request->email)->first();

            // mengambilToken
            $tokenResult = $user->createToken('authToken')->plainTextToken;

            // Kembalikan token dan data user
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ]);

            // Jika mengalami kesalahan
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'something when wrong',
                'error' => $error
            ], 'Authentication Failed', 500);
        }
    }

    // Logout
    public function logout(Request $request)
    {
        //Mengambil token  
        $token = $request->user()->currentAccessToken()->delete();

        // isi variable $token biasanya boolean
        return ResponseFormatter::success($token, 'Token Revoked');
    }

    // mengambil profile date / data user 
    public function fetch(Request $request)
    {
        return ResponseFormatter::success($request->user(), 'Data Profile user berhasil diambil');
    }

    // Update Profile
    public function updateProfile(Request $request)
    {
        // mengambil semua data
        $data = $request->all();

        // Variable Auth::user() -> mengarah ke table user
        $user = Auth::user();
        $user->update($data);

        // mengembalikan data yang berhasil update
        return ResponseFormatter::success($user, 'Profile Updated');
    }

    // Update Foto
    public function updatePhoto(Request $request)
    {
        // validasi membutuhkan gambar kurang dari 2mb
        $validator = Validator::make($request->all(), [
            'file' => 'required|image|max:2048'
        ]);

        // jika gagal
        if ($validator->fails()) {
            return ResponseFormatter::error(
                ['error' => $validator->errors()],
                'update photo fails',
                401
            );
        }

        // jika berhasil upload foot kemudian simpan ke db
        if ($request->file('file')) {
            // upload
            $file = $request->file->store('assets/user' . 'public');

            // simpan foto (url), gambarnya tetap di folder
            $user = Auth::user();
            $user->profile_photo_path = $file;
            $user->update();

            return ResponseFormatter::success([$file], 'File successfully upload');
        }
    }
}
