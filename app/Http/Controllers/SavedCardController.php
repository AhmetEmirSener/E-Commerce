<?php

namespace App\Http\Controllers;

use App\Models\SavedCard;
use Illuminate\Http\Request;
use App\Services\Iyzico\IyzicoService;

use Illuminate\Support\Facades\DB;
use App\Http\Requests\SavedCards\DeleteSavedCardRequest;

class SavedCardController extends Controller
{
    protected IyzicoService $iyzicoService;

    public function __construct(IyzicoService $iyzicoService){

        $this->iyzicoService= $iyzicoService;
    }

    public function deleteSavedCard(DeleteSavedCardRequest $request){
        
        $data = $request->validated();
        $user = $request->get('auth_user');

        $savedCards = SavedCard::where('user_id',$user->id)->get();
        if (!$savedCards) {
            return response()->json([
                'message' => 'Kart bulunamadı'
            ], 404);
        }
        $deletedCard = $savedCards->where('id',$data['saved_card_id'])->first();
        if (!$deletedCard) {
            return response()->json([
                'message' => 'Kart bulunamadı'
            ], 404);
        }

        $result = $this->iyzicoService->deleteSavedCard($deletedCard->card_user_key,$deletedCard->card_token);

        if ($result->getStatus() !== 'success') {
            return response()->json([
                'message' => $result->getErrorMessage()
            ], 400);
        }
        DB::transaction(function () use ($savedCards,$deletedCard){
            if($deletedCard->is_default && $savedCards->count() > 1){
                $newDefault = $savedCards->where('id', '!=', $deletedCard->id)
                ->where('is_default', false)
                ->first();
                if ($newDefault) {
                    $newDefault->update(['is_default' => true]);
                }
            }
    
            $deletedCard->delete();
    
        });
       
        return response()->json([
            'message' => 'Kart başarıyla silindi'
        ]);
    }
}
