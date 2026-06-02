<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Ürün Yorumları';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        // 1. İLİŞKİLER (Sadece okuma modu)
                        Forms\Components\Select::make('user_id')
                            ->label('Yorum Yapan Kullanıcı')
                            ->relationship('user', 'name')
                            ->disabled()
                            ->required(),

                        Forms\Components\Select::make('advert_id')
                            ->label('İlan / Ürün')
                            ->relationship('advert', 'title')
                            ->disabled()
                            ->required(),

                        // 2. YORUM METRİKLERİ
                        Forms\Components\TextInput::make('rating')
                            ->label('Yıldız Skoru')
                            ->prefix('⭐')
                            ->disabled()
                            ->required()
                            ->numeric(),

                        Forms\Components\TextInput::make('status')
                            ->label('Kullanıcı Tercih Statüsü')
                            ->helperText('Kullanıcının kendi belirlediği durum (göster/gizle vb.)')
                            ->disabled()
                            ->required(),

                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('Sistem Onay Tarihi')
                            ->helperText('Yorumu onaylamak için üstteki butonu kullanabilir ya da buraya tarih girebilirsiniz.')
                            ->seconds(false),

                        Forms\Components\TextInput::make('order_item_id')
                            ->label('Sipariş Kalem ID')
                            ->disabled()
                            ->numeric(),

                        // 3. BEĞENİ SAYILARI
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('like_count')
                                    ->label('Beğeni Sayısı')
                                    ->disabled()
                                    ->numeric(),

                                Forms\Components\TextInput::make('dislike_count')
                                    ->label('Beğenmeme Sayısı')
                                    ->disabled()
                                    ->numeric(),
                            ]),

                        // 4. METİN VE MEDYA
                        Forms\Components\Textarea::make('comment')
                            ->label('Kullanıcı Yorumu')
                            ->rows(4)
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('image')
                            ->label('Yoruma Eklenen Fotoğraf')
                            ->image()
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Kullanıcı')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('advert.title')
                    ->label('İlan / Ürün')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Skor')
                    ->badge()
                    ->color(fn ($state) => $state >= 4 ? 'success' : ($state >= 3 ? 'warning' : 'danger'))
                    ->formatStateUsing(fn ($state) => "⭐ {$state}")
                    ->sortable(),

                Tables\Columns\TextColumn::make('comment')
                    ->label('Yorum')
                    ->limit(40)
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Kullanıcı Statüsü')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Onay Tarihi')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Onay Bekliyor ⏳')
                    ->sortable(),
            ])
            ->filters([
                // ======================================================================
                // DEFAULT DEĞERLİ, JİLET GİBİ ÇALIŞAN FİLTRE YAPISI
                // ======================================================================
                Tables\Filters\SelectFilter::make('approved_status')
                    ->label('Sistem Onay Durumu')
                    ->options([
                        'pending' => 'Sadece Onay Bekleyenler',
                        'approved' => 'Sadece Onaylanmış Yorumlar',
                    ])
                    ->default('pending') // Sayfa ilk açıldığında tık diye bekleyenleri getirir
                    ->query(function (Builder $query, array $data) {
                        $val = $data['value'] ?? null;
                        
                        if ($val === 'approved') {
                            return $query->whereNotNull('approved_at');
                        }
                        if ($val === 'pending') {
                            return $query->whereNull('approved_at');
                        }
                        return $query;
                    })
                    ->placeholder('Tüm Yorumları Göster (Arşiv)'),
                ]);
            /*
            ->actions([
                // ======================================================================
                // TABLODA SATIR İÇİ "DİREKT ONAYLA" BUTONU
                // ======================================================================
                Tables\Actions\Action::make('approveReview')
                    ->label('Onayla')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->button() 
                    ->visible(fn ($record) => $record->approved_at === null) 
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['approved_at' => now()]);

                        \Filament\Notifications\Notification::make()
                            ->title('Yorum Onaylandı')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()->label('İncele / Düzenle'),
            ])


                ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    // ======================================================================
                    // SEÇİLENLERİ TOPLU ONAYLAMA BUTONU
                    // ======================================================================
                    Tables\Actions\BulkAction::make('bulkApprove')
                        ->label('Seçilenleri Onayla')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each(fn ($record) => $record->update(['approved_at' => now()]));
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Seçilen Tüm Yorumlar Onaylandı')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);

                */
            
            
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
            'create' => Pages\CreateReview::route('/create'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }
}