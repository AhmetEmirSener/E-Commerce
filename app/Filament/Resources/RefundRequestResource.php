<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RefundRequestResource\Pages;
use App\Filament\Resources\RefundRequestResource\RelationManagers\ItemsRelationManager;

use App\Models\RefundRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RefundRequestResource extends Resource
{
    protected static ?string $model = RefundRequest::class;

    // İadeyi temsil eden geri dönüş ikonu kanka
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'İade Talepleri';

    protected static ?string $navigationGroup = 'Sipariş & Operasyon';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        // ======================================================================
                        // SOL VE ORTA: İADE DETAYLARI (SADECE OKUNABİLİR KANKA)
                        // ======================================================================
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Card::make()
                                    ->schema([
                                        Forms\Components\Placeholder::make('info')
                                            ->label('📦 İade Edilen Sipariş Bilgileri')
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('order_id')
                                            ->label('Sipariş No')
                                            ->relationship('order', 'id') // Order ID veya Sipariş Kodu kanka
                                            ->disabled(),

                                        Forms\Components\Select::make('user_id')
                                            ->label('Müşteri')
                                            ->relationship('user', 'name')
                                            ->disabled(),

                                        Forms\Components\TextInput::make('reason')
                                            ->label('İade Sebebi')
                                            ->disabled()
                                            ->columnSpanFull(),
                                    ])->columns(2),

                                // KARGO VE LOJİSTİK (Admin doldurur veya kargo firmasından API ile düşer)
                                Forms\Components\Card::make()
                                    ->schema([
                                        Forms\Components\Placeholder::make('cargo_info')
                                            ->label('🚚 İade Kargo Durumu')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('cargo_company')
                                            ->label('Kargo Firması')
                                            ->placeholder('Örn: Yurtiçi Kargo'),

                                        Forms\Components\TextInput::make('cargo_tracking_code')
                                            ->label('Kargo Takip Kodu')
                                            ->placeholder('Örn: 123456789'),

                                        Forms\Components\DateTimePicker::make('shipped_at')
                                            ->label('Kargoya Verilme Tarihi')
                                            ->seconds(false),

                                        Forms\Components\DateTimePicker::make('received_at')
                                            ->label('Depoya Ulaşma Tarihi')
                                            ->seconds(false),
                                    ])->columns(2),
                            ])
                            ->columnSpan(2),

                        // ======================================================================
                        // SAĞ TARAF: YÖNETİM VE DURUM KONTROLÜ
                        // ======================================================================
                        Forms\Components\Card::make()
                            ->schema([
                                Forms\Components\Placeholder::make('action_title')
                                    ->label('⚙️ İade Yönetimi'),

                                Forms\Components\Select::make('status')
                                    ->label('İade Durumu')
                                    ->options([
                                        'pending' => 'Bekliyor',
                                        'approved' => 'Onaylandı (Kargo Bekleniyor)',
                                        'shipped' => 'Müşteri Kargoladı',
                                        'received' => 'Depoya Ulaştı',
                                        'completed' => 'İade Tamamlandı (Para İadesi Yapıldı)',
                                        'rejected' => 'Reddedildi',
                                        'partial' => 'Kısmi İade',
                                    ])
                                    ->required()
                                    ->default('pending')
                                    ->native(false),

                                Forms\Components\Textarea::make('admin_note')
                                    ->label('Admin Notu (Müşteri Görmez)')
                                    ->rows(4)
                                    ->placeholder('İade reddedildiyse sebebi veya depo kontrol notları...'),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->latest()) // En yeniler en üstte kanka
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('İade No')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('order_id')
                    ->label('Sipariş No')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Müşteri')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'info' => fn ($state) => in_array($state, ['approved', 'shipped']),
                        'success' => fn ($state) => in_array($state, ['received', 'completed']),
                        'danger' => 'rejected',
                        'gray' => 'partial',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'Bekliyor',
                        'approved' => 'Onaylandı',
                        'shipped' => 'Kargoladı',
                        'received' => 'Depoda',
                        'completed' => 'Tamamlandı',
                        'rejected' => 'Red',
                        'partial' => 'Kısmi',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('cargo_tracking_code')
                    ->label('Takip Kodu')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Talep Tarihi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('İade Durumu')
                    ->options([
                        'pending' => 'Bekleyenler',
                        'approved' => 'Onaylananlar',
                        'shipped' => 'Yoldakiler',
                        'received' => 'Depoya Ulaşanlar',
                        'completed' => 'Tamamlananlar',
                        'rejected' => 'Reddedilenler',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('İncele & Yönet'),
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
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRefundRequests::route('/'),
            'edit' => Pages\EditRefundRequest::route('/{record}/edit'),
        ];
    }

    // Admin manuel olarak "Ben iade talebi oluşturayım" diyemesin kanka, sistemden düşmeli:
    public static function canCreate(): bool
    {
        return false;
    }
}