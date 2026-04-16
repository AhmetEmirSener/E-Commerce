<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CartService;
use App\Services\Iyzico\IyzicoService;
use App\Http\Requests\Payment\CheckInstallments;


class InstallmentController extends Controller
{
    protected CartService $cartService;
    protected IyzicoService $iyzicoService;
    
    public function __construct(CartService $cartService, IyzicoService $iyzicoService){
        $this->cartService =$cartService;

        $this->iyzicoService = $iyzicoService;




    }


    public function getInstallments(CheckInstallments $request){
        try {
            $data = $request->validated();
            $user = $request->get('auth_user');
            $cartItems = $user->cartItems()->with('product.activeDiscount')->get();

            if($cartItems->isEmpty()) return response()->json(['message'=>'Sepetiniz boş.'],400);

            $subTotal = $cartItems->sum('total');
            $total = $this->cartService->calculateTotalwCargoFee($subTotal);

            $result = $this->iyzicoService->getInstallments($data['card_number'],$total);

            if($result->getStatus() !== 'success'){
                return response()->json(['error'=>$result->getErrorMessage()]);
            }

            $installments=$this->iyzicoService->handleInstallments($result,$total);
            

            return response()->json([
                'installments'=>$installments,
                'card_type'=>$result->getInstallmentDetails()[0]->getCardType() ?? null,
                'card_family'=>$result->getInstallmentDetails()[0]->getCardFamilyName() ?? null
            ]);
        } catch (\Throwable $th) {
            return response()->json(['message'=>$th->getMessage()],500);
        }
    }



    

}
