<?php

namespace App\Http\Controllers;

use App\Models\SavedCard;
use Illuminate\Http\Request;
use App\Services\Iyzico\IyzicoService;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Http\Requests\SavedCards\DeleteSavedCardRequest;

class SavedCardController extends Controller
{
    protected IyzicoService $iyzicoService;

    public function __construct(IyzicoService $iyzicoService){

        $this->iyzicoService= $iyzicoService;
    }

    public function deleteSavedCard(Request $request,$id){
        
        $user = $request->get('auth_user');

        $deletedCard = SavedCard::where('id', $id)
        ->where('user_id', $user->id)
        ->first();

        if (!$deletedCard) {
            return response()->json([
                'message' => 'Kart bulunamadı'
            ], 404);
        }

        try {

            $result = $this->iyzicoService->deleteSavedCard($deletedCard->card_user_key,$deletedCard->card_token);

            if ($result->getStatus() !== 'success') {
                Log::channel('saved_cards')->error('Iyzico kart silinemedi',
                [
                    'user_id'    => $deletedCard->user_id,
                    'card_token' => $deletedCard->card_token,
                    'error'      => $result->getErrorMessage(),
                ]);
                return response()->json(['message' => 'Kart silinemedi, lütfen tekrar deneyin.'], 400);

            }

            DB::transaction(function () use ($deletedCard){
                if ($deletedCard->is_default) {
                    SavedCard::where('user_id', $deletedCard->user_id)
                        ->where('id', '!=', $deletedCard->id)
                        ->oldest()
                        ->limit(1)
                        ->update(['is_default' => true]);
                }
                $deletedCard->delete();
            });

            Log::channel('saved_cards')->info('Kart silindi', [
                'user_id'    => $deletedCard->user_id,
                'card_token' => $deletedCard->card_token,
            ]);

            return response()->json(['message' => 'Kart başarıyla silindi']);

           

        } catch (\Throwable $th) {
            Log::channel('saved_cards')->error('deleteSavedCard error: ' . $th->getMessage());
            return response()->json(['message' => 'Bir hata oluştu, lütfen tekrar deneyin.'], 500);
        }

       
        return response()->json([
            'message' => 'Kart başarıyla silindi'
        ]);
    }
}
