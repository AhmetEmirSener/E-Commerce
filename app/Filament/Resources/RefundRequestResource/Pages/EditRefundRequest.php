<?php

namespace App\Filament\Resources\RefundRequestResource\Pages;

use App\Filament\Resources\RefundRequestResource;
use App\Models\Refund;
use App\Models\RefundItem;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use Illuminate\Support\Facades\DB;

class EditRefundRequest extends EditRecord
{
    protected static string $resource = RefundRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ======================================================================
            // SENİN API KODUNU ATEŞLEYECEK O CANAVAR BUTON KANKA!
            // ======================================================================
            Actions\Action::make('processIyzicoRefund')
                ->label('Iyzico İadesini Tamamla')
                ->icon('heroicon-o-credit-card')
                ->color('success')
                
                // Kanka senin koddaki throw_unless($refundRequest->status === 'received') mantığını 
                // direkt butonun görünürlüğüne bağladık, depoya gelmeden butona basılamaz!
                ->visible(fn ($record) => $record->status === 'received') 
                
                // ======================================================================
                // MODAL FORMU: API'nin beklediği $data['refund_items'] dizisini hazırlar
                // ======================================================================
                ->form([
                    Forms\Components\Repeater::make('refund_items')
                        ->label('İade Edilecek Kalemlerin Son Kontrolü')
                        ->schema([
                            Forms\Components\Hidden::make('id'),
                            
                            Forms\Components\TextInput::make('product_name')
                                ->label('Ürün Adı')
                                ->disabled(), // Sadece admin görsün diye
                                
                            Forms\Components\Select::make('status')
                                ->label('Karar')
                                ->options([
                                    'approved' => 'Onayla (İade Et)',
                                    'rejected' => 'Reddet (Hasarlı vs.)',
                                ])
                                ->required(),
                                
                            Forms\Components\TextInput::make('quantity')
                                ->label('İade Edilecek Adet')
                                ->numeric()
                                ->required(),
                        ])
                        ->addable(false)   // Yeni boş satır eklenemez
                        ->deletable(false) // Satır silinemez
                        ->columns(3),
                        
                    Forms\Components\Textarea::make('admin_note')
                        ->label('Admin Notu (Opsiyonel)')
                        ->rows(2),
                ])
                
                // Form açılırken mevcut veritabanındaki ürünleri tık diye doldurur kanka
                ->fillForm(function ($record) {
                    return [
                        'refund_items' => $record->refundRequestItem->map(fn($item) => [
                            'id'           => $item->id,
                            'product_name' => $item->orderItem->product->name,
                            'status'       => 'approved', // Varsayılan olarak onayla gelsin
                            'quantity'     => $item->quantity,
                        ])->toArray(),
                        'admin_note'   => $record->admin_note,
                    ];
                })
                
                // ======================================================================
                // ACTION İÇİ: SENİN YAZDIĞIN KODUN BİREBİR AYNISI BURADA ÇALIŞIYOR MQ!
                // ======================================================================
                ->action(function (array $data, $record) {
                    
                    // İhtiyacımız olan servisi bodoslama çağırıyoruz
                    $iyzicoService = app(\App\Services\Iyzico\IyzicoService::class);
                    $refundRequest = $record;

                    try {
                        DB::transaction(function() use ($data, $refundRequest, $iyzicoService) {

                            $approvedItems = collect();

                            // İlişki adın refundRequestItem mi yoksa items mi? Ben senin koddaki gibi bıraktım kanka
                            // Eğer items yaptıysan burayı $refundRequest->items() diye düzeltirsin.
                            foreach($data['refund_items'] as $item){
                                // Senin koddaki $refundRequest->refundRequestItem->find() mantığı
                                $requestItem = $refundRequest->refundRequestItem()->find($item['id']); 

                                if (!$requestItem) continue;

                                throw_if(
                                    $item['status'] === 'approved' && $item['quantity'] > $requestItem->quantity,
                                    \Exception::class, "Geçersiz miktar tespit edildi: {$requestItem->id}"
                                );

                                $requestItem->status  = $item['status'];
                                $requestItem->approved_quantity = $item['status'] === 'approved' ? $item['quantity'] : 0;
                                $requestItem->save();

                                if($item['status'] === 'approved'){
                                    $requestItem->calculatedAmount = $requestItem->orderItem->price * $item['quantity'];
                                    $approvedItems->push($requestItem);
                                }
                            }

                            $allItems = $refundRequest->refundRequestItem()->get();
                            
                            $refundRequest->status = match(true) {
                                $allItems->every(fn($i) => $i->status === 'approved') => 'completed',
                                $allItems->every(fn($i) => $i->status === 'rejected') => 'rejected',
                                default                                                => 'partial',
                            };
                            
                            $refundRequest->received_at = now();
                            $refundRequest->admin_note  = $data['admin_note'] ?? $refundRequest->admin_note;
                            $refundRequest->save();

                            if ($approvedItems->isEmpty()) return;

                            $totalAmount = $approvedItems->sum(fn($i) => $i->calculatedAmount);
                            $providerRefundIds = [];

                            foreach($approvedItems as $approvedItem){
                                // Laravel request()->ip() ile o an işlemi yapan adminin IP'sini alıyoruz
                                $result = $iyzicoService->refundPayment(
                                    $approvedItem->orderItem->payment_transaction_id,
                                    $approvedItem->calculatedAmount,
                                    request()->ip() 
                                );

                                throw_if($result->getStatus() !== 'success',
                                    \Exception::class, 'Iyzico iade başarısız: ' . $result->getErrorMessage());

                                $providerRefundIds[] = $result->getPaymentTransactionId();
                            }

                            $refund = Refund::create([
                                'order_id'           => $refundRequest->order_id,
                                'user_id'            => $refundRequest->user_id,
                                'refund_request_id'  => $refundRequest->id,
                                'amount'             => $totalAmount,
                                'status'             => 'success',
                                'provider_refund_id' => implode(',', $providerRefundIds),
                            ]);

                            foreach ($approvedItems as $approvedItem) {
                                RefundItem::create([
                                    'refund_id'     => $refund->id,
                                    'order_item_id' => $approvedItem->order_item_id,
                                    'quantity'      => $approvedItem->approved_quantity,
                                    'amount'        => $approvedItem->calculatedAmount,
                                ]);
                            }

                            // Stokları geri basıyoruz mq
                            foreach ($approvedItems as $approvedItem) {
                                $approvedItem->orderItem->product()->increment('stock', $approvedItem->approved_quantity);
                            }
                        });

                        // Başarı bildirimi
                        \Filament\Notifications\Notification::make()
                            ->title('İşlem Tamamlandı')
                            ->body('Iyzico iadesi başarıyla gerçekleşti ve stoklar güncellendi.')
                            ->success()
                            ->send();

                    } catch (\Throwable $th) {
                        // Eğer throw_if veya Iyzico patlarsa adminin ekranına şak diye kırmızı uyarı düşer kanka!
                        \Filament\Notifications\Notification::make()
                            ->title('İade İşlemi Başarısız!')
                            ->body($th->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\DeleteAction::make(),
        ];
    }
}