<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CargoFeeResource\Pages;
use App\Models\CargoFee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CargoFeeResource extends Resource
{
    protected static ?string $model = CargoFee::class;

    // Kamyon ikonu kanka tam kargo lojistiğine uygun
    protected static ?string $navigationIcon = 'heroicon-o-truck'; 

    protected static ?string $navigationLabel = 'Kargo Ücretleri';

    protected static ?string $navigationGroup = 'Sistem Ayarları';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Kargo Ücreti')
                            ->numeric()
                            ->prefix('₺')
                            ->required()
                            ->placeholder('Örn: 49.90')
                            ->helperText('Siparişe yansıyacak sabit kargo bedeli.'),

                        Forms\Components\TextInput::make('free_shipping_threshold')
                            ->label('Ücretsiz Kargo Barajı')
                            ->numeric()
                            ->prefix('₺')
                            ->required()
                            ->default(100.00)
                            ->placeholder('Örn: 250')
                            ->helperText('Sepet tutarı bu değeri geçerse kargo bedava olur kanka.'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif Olarak Kullanılsın Mı?')
                            ->default(true)
                            ->helperText('Aynı anda sadece bir kargo kuralının aktif olmasına dikkat edin.'),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Kargo Ücreti')
                    ->money('TRY') // Tl simgesini şık bir şekilde kendi koyar kanka
                    ->sortable(),

                Tables\Columns\TextColumn::make('free_shipping_threshold')
                    ->label('Ücretsiz Kargo Barajı')
                    ->money('TRY')
                    ->sortable(),

                // ======================================================================
                // SİHİRLİ DOKUNUŞ: TABLODAN TEK TIKLA AKTİF/PASİF YAPMA
                // ======================================================================
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktiflik Durumu')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Son Güncelleme')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Durum')
                    ->options([
                        '1' => 'Aktif Olanlar',
                        '0' => 'Pasif Olanlar',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCargoFees::route('/'),
            'create' => Pages\CreateCargoFee::route('/create'),
            'edit' => Pages\EditCargoFee::route('/{record}/edit'),
        ];
    }
}   