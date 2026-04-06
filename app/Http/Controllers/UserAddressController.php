<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UserAddressRequest;
use App\Http\Requests\UpdateUserAddressRequest;
use App\Http\Resources\AddressResource;



class UserAddressController extends Controller
{
    public function createAddress(UserAddressRequest $request){
        try {
            $data = $request->validated();
            $user_id = $request->get('auth_user')->id;
            
            UserAddress::create([...$data,'user_id'=>$user_id]);

            return response()->json(['message'=>'Adres oluşturma başarılı.']);
            


        } catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);
        }
    }

    public function updateAddress(UpdateUserAddressRequest $request,$id){
        try {
            $data = $request->validated();
            $user_id = $request->get('auth_user')->id;
            $address = UserAddress::findOrFail($id);

            if ($address->user_id !== $user_id) {
                return response()->json(['message' => 'Bu adrese erişim yetkiniz yok.'], 403);
            }
            
            $defaultAddress = UserAddress::where('user_id',$user_id)->where('is_default',1)->first();

            if($defaultAddress && $defaultAddress->id != $address->id){
                
                $defaultAddress->is_default=false;
                $defaultAddress->save();

            }

            $address->is_default = true;

            $address->update($data);

            return response()->json(['message'=>'Adres güncelleme başarılı.']);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error'=>'Adres bulunamadı.'],404);

        }catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);
        }
    }

    public function deleteAddress(UpdateUserAddressRequest $request){
        try {
            $data = $request->validated();

            $user_id = $request->get('auth_user')->id;

            $address=UserAddress::findOrFail($data['address_id']);
            if($address->user_id!==$user_id){
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

    public function getAddress(Request $request){
        try {
            $user_id = $request->get('auth_user')->id;
            $address= UserAddress::where('user_id',$user_id)->orderByDesc('is_default')->get();
            if($address->count()==0) return response()->json(['message'=>'Kayıtlı adres yok.'],400);
            return AddressResource::collection($address);

            
        } catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()],500);
        }
    }
    
    public function getDefaultAddress(Request $request){
        try {
            $user_id = $request->get('auth_user')->id;

            $address = UserAddress::where('user_id',$user_id)->where('is_default',1)->first();
            
            if($address->user_id!==$user_id){
                return response()->json(['message' => 'Bu adrese erişim yetkiniz yok.'], 403);
            }

            return new AddressResource($address);
        } catch (\Throwable $th) {
            return response()->json(['message'=>$th->getMessage()],500);

        }
    }
}
