<?php

namespace App\Filament\Resources\RefundRequestResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    // RefundRequest modelindeki hasMany ilişki adın (genelde 'items' veya 'refundRequestItems' olur kanka)
    protected static string $relationship = 'refundRequestItem'; 

    protected static ?string $title = 'İade Edilecek Sipariş Kalemleri';

    public function form(Form $form): Form
    {
        return $form->schema([]); // İade kalemleri sonradan editlenmez mq, form bomboş!
    }

    public function table(Table $table): Table
    {
        return $table
            // Kanka performansı artırmak için orderItem ilişkisini N+1 problemine karşı önden yüklüyoruz
            ->modifyQueryUsing(fn ($query) => $query->with('orderItem.product'))
            ->recordTitleAttribute('id')
            ->columns([
                
                Tables\Columns\TextColumn::make('order_item_id')
                    ->label('Sipariş Kalem ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Admin isterse açıp bakar kanka

                // ======================================================================
                // ÜRÜN ADINI ZİNCİRLEME İLİŞKİDEN ÇEKİYORUZ
                // ======================================================================
                // Not: RefundRequestItem -> orderItem -> product şeklinde bir bağ olduğunu varsayıyoruz
                // Eğer doğrudan advert'e bağlıysa 'orderItem.advert.title' yaparsın kanka
                Tables\Columns\TextColumn::make('orderItem.product.name')
                    ->label('İade Edilen Ürün Adı')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('İade Adedi')
                    ->badge()
                    ->color('warning')
                    ->sortable(),

                // ======================================================================
                // SENİN ŞEMADAKİ 'AMOUNT' ALANINI DİREKT BURAYA BASTIK MQ!
                // ======================================================================
                Tables\Columns\TextColumn::make('amount')
                    ->label('İade Edilecek Tutar')
                    ->money('TRY')
                    ->color('success')
                    ->weight('bold')
                    ->sortable(),
            ])
            ->filters([
                // Filtreye gerek yok, zaten az ürün olur kanka
            ])
            ->headerActions([
                // Admin kafasına göre iade kalemi ekleyemez
            ])
            ->actions([
                // Admin kafasına göre iade kalemi silemez
            ])
            ->bulkActions([
                // Toplu işlem de yok, pürüzsüz Read-Only mq
            ]);
    }
}