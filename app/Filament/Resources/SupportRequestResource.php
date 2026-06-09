<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportRequestResource\Pages;
use App\Models\SupportRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupportRequestResource extends Resource
{
    protected static ?string $model = SupportRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy'; // Müşteri destek can simidi ikonu kanka

    protected static ?string $navigationLabel = 'Destek Talepleri';

    protected static ?string $navigationGroup = 'Müşteri Hizmetleri';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        // ======================================================================
                        // SOL VE ORTA: MÜŞTERİ BİLGİLERİ VE MESAJI (SADECE OKUNABİLİR KANKA)
                        // ======================================================================
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Card::make()
                                    ->schema([
                                        Forms\Components\Placeholder::make('customer_info')
                                            ->label('👤 İletişim Bilgileri')
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('user_id')
                                            ->label('Kayıtlı Kullanıcı')
                                            ->relationship('user', 'name')
                                            ->disabled(), // Admin elleyemez mq

                                        Forms\Components\Select::make('order_id')
                                            ->label('İlgili Sipariş (Varsa)')
                                            ->relationship('order', 'id') // Order modeline göre bağla kanka
                                            ->disabled(),

                                        Forms\Components\TextInput::make('contact_name')
                                            ->label('Ziyaretçi Adı Soyadı')
                                            ->disabled(),

                                        Forms\Components\TextInput::make('contact_email')
                                            ->label('E-Posta Adresi')
                                            ->disabled(),

                                        Forms\Components\TextInput::make('contact_phone')
                                            ->label('Telefon Numarası')
                                            ->disabled(),

                                        Forms\Components\TextInput::make('contact_preference')
                                            ->label('İletişim Tercihi')
                                            ->disabled(),
                                    ])->columns(2),

                                Forms\Components\Card::make()
                                    ->schema([
                                        Forms\Components\Placeholder::make('ticket_info')
                                            ->label('📝 Talep Detayı'),

                                        Forms\Components\TextInput::make('topic')
                                            ->label('Konu / Başlık')
                                            ->disabled(),

                                        Forms\Components\Textarea::make('message')
                                            ->label('Müşteri Mesajı')
                                            ->rows(6)
                                            ->disabled(),
                                    ]),
                            ])
                            ->columnSpan(2),

                        // ======================================================================
                        // SAĞ TARAF: ADMİNİN İŞLEM YAPACAĞI YER (STATUS GÜNCELLEME)
                        // ======================================================================
                        Forms\Components\Card::make()
                            ->schema([
                                Forms\Components\Placeholder::make('action_title')
                                    ->label('⚙️ Talep Yönetimi'),

                                Forms\Components\Select::make('status')
                                    ->label('Talep Durumu')
                                    ->options([
                                        'open' => 'Açık (Yeni)',
                                        'in_progress' => 'İnceleniyor',
                                        'resolved' => 'Çözüldü',
                                        'closed' => 'Kapatıldı',
                                    ])
                                    ->required()
                                    ->default('open')
                                    ->native(false), // Şık bir select dropdown olsun kanka
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // ======================================================================
            // SAYFA AÇILINCA DİREKT AÇIK VE İŞLEMDE OLANLAR LİSTELENSİN KANKA
            // ======================================================================
            ->modifyQueryUsing(function (Builder $query) {
                return $query->orderByRaw("FIELD(status, 'open', 'in_progress', 'resolved', 'closed')")
                             ->latest();
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Talep No')
                    ->sortable()
                    ->searchable(),

                // Eğer user_id varsa adını yaz, yoksa contact_name yazsın kanka akıllı sütun!
                Tables\Columns\TextColumn::make('customer')
                    ->label('Müşteri')
                    ->getStateUsing(fn ($record) => $record->user_id ? $record->user->name : $record->contact_name)
                    ->searchable(['contact_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('topic')
                    ->label('Konu')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->colors([
                        'danger' => 'open',
                        'warning' => 'in_progress',
                        'success' => 'resolved',
                        'gray' => 'closed',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'open' => 'Açık',
                        'in_progress' => 'İnceleniyor',
                        'resolved' => 'Çözüldü',
                        'closed' => 'Kapatıldı',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Talep Durumuna Göre')
                    ->options([
                        'open' => 'Açık Talepler',
                        'in_progress' => 'İncelenen Talepler',
                        'resolved' => 'Çözülen Talepler',
                        'closed' => 'Kapananlar',
                    ]),
            ])
            ->actions([
                // Kanka tablo üzerinden tek tıkla "Çözüldü" işaretleme butonu çaktım, admini yormayalım:
                Tables\Actions\Action::make('markAsResolved')
                    ->label('Çözüldü')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, ['open', 'in_progress']))
                    ->action(function ($record) {
                        $record->update(['status' => 'resolved']);
                        \Filament\Notifications\Notification::make()
                            ->title('Talep başarıyla çözüldü olarak işaretlendi!')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()->label('İncele'),
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
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportRequests::route('/'),
            'edit' => Pages\EditSupportRequest::route('/{record}/edit'),
        ];
    }
}