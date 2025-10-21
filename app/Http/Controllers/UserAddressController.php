<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UserAddressRequest;
use App\Http\Requests\UpdateUserAddressRequest;



class UserAddressController extends Controller
{
    public function createAddress(UserAddressRequest $request){
        try {
            $data = $request->validated();
            
            UserAddress::create([...$data,'user_id'=>Auth::user()->id]);

            return response()->json(['message'=>'Adres oluşturma başarılı.']);
            


        } catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);
        }
    }

    public function updateAddress(UpdateUserAddressRequest $request,$id){
        try {
            $data = $request->validated();
            $address = UserAddress::findOrFail($id);

            if ($address->user_id !== Auth::user()->id) {
                return response()->json(['message' => 'Bu adrese erişim yetkiniz yok.'], 403);
            }
            $address->update($data);

            return response()->json(['message'=>'Adres güncelleme başarılı.']);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error'=>'Adres bulunamadı.'],404);

        }catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);
        }
    }

    public function deleteAddress($id){
        try {

            $address=UserAddress::findOrFail($id);
            if($address->user_id!==Auth::user()->id){
                return response()->json(['message' => 'Bu adrese erişim yetkiniz yok.'], 403);
            }
            $address->delete();

            return response()->json(['message'=>'Adres silme başarılı.']);

        }catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error'=>'Adres bulunamadı.'],404);

        }
         catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);
        }
    }

    public function getAddress(){
        try {
            $userId = Auth::user()->id;
            $address= UserAddress::where('user_id',$userId)->get();
            if($address->count()==0) return response()->json(['message'=>'Kayıtlı adres yok.'],400);
            return response()->json(['data'=>$address]);
            
        } catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);
        }
    }
}
