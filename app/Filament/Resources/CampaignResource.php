<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignResource\Pages;
use App\Filament\Resources\CampaignResource\RelationManagers\ActiveProductDiscountsRelationManager;
use App\Models\Campaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\Alignment;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Campaigns';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Kampanya Adı')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Kampanya Aktif Mi?')
                            ->required()
                            ->default(true),
                            
                        Forms\Components\DateTimePicker::make('start_date')
                            ->label('Başlangıç Tarihi')
                            ->seconds(false),

                        Forms\Components\DateTimePicker::make('end_date')
                            ->label('Bitiş Tarihi')
                            ->seconds(false),

                        // ======================================================================
                        // SİHİRLİ DOKUNUŞ: MASAÜSTÜ VE MOBİL GÖRSELLERİ YAN YANA KOYUYORUZ KANKA!
                        // ======================================================================
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\FileUpload::make('image')
                                    ->label('Masaüstü Banner Görseli (Desktop)')
                                    ->image()
                                    ->directory('campaign-banners/desktop')
                                    ->imageResizeTargetWidth('1920') // İstersen ideal responsive boyut sınırları koyabilirsin
                                ,
                                Forms\Components\FileUpload::make('mobile_image')
                                        ->label('Mobil Banner Görseli (Mobile)')
                                        ->image()
                                        ->directory('campaign-banners/mobile')
                                        ->imageResizeTargetWidth('768') // Mobil için dikey veya kare ideal boyutlar
                            ]),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
              //  Tables\Columns\TextColumn::make('id')->sortable(),
                
                // Kanka kampanya listesinde de ufak önizleme ikonları olsun, şık dursun:
                Tables\Columns\ImageColumn::make('image')
                    ->label('Web Banner'),

                    /*
                Tables\Columns\ImageColumn::make('mobile_image')
                    ->label('Mobil Banner'),

                */

                Tables\Columns\TextColumn::make('title')
                    ->label('Kampanya Adı')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Durum')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Başlangıç')
                    ->dateTime('d/m/Y H:i'),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Bitiş')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Süresiz'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Kampanya Durumu')
                    ->options([
                        '1' => 'Sadece Aktifler',
                        '0' => 'Sadece Pasifler/Bitenler',
                    ])
                    ->placeholder('Tümü'),
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

    public static function getRelations(): array
    {
        return [
            ActiveProductDiscountsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}