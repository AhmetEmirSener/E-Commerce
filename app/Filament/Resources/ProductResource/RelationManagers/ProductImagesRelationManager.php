<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model; 
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

  public function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\FileUpload::make('path')
            ->label('Görsel')
            ->image()
            ->directory('product-images')
            ->required(),
        Forms\Components\TextInput::make('title')
            ->label('Başlık')
            ->nullable(),
        Forms\Components\TextInput::make('sort')
            ->label('Sıra')
            ->numeric()
            ->default(1),
        Forms\Components\Toggle::make('is_main')
            ->label('Ana Görsel'),
    ]);
}

public function table(Table $table): Table
{
    return $table
        ->recordTitleAttribute('path')
        ->columns([
            Tables\Columns\ImageColumn::make('path')
                ->label('Görsel'),
            Tables\Columns\TextColumn::make('title')
                ->label('Başlık'),
            Tables\Columns\TextColumn::make('sort')
                ->label('Sıra')
                ->sortable(),
            Tables\Columns\IconColumn::make('is_main')
                ->label('Ana Görsel')
                ->boolean(),
        ])
        ->filters([])
        ->headerActions([
            Tables\Actions\CreateAction::make()
            ->after(function (Model $record) {
                        static::handleMainImageSync($record);
                    }),
        ])
        ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function (Model $record) {
                        // $record = Güncellenen ProductImage modeli
                        static::handleMainImageSync($record);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function (Model $record) {
                        // Eğer ana görsel silindiyse, ürün tablosundaki image alanını temizle veya sıradakini ata
                        $product = $record->product;
                        if ($record->is_main) {
                            $nextImage = $product->images()->first();
                            if ($nextImage) {
                                $nextImage->update(['is_main' => true]);
                                $product->update(['image' => $nextImage->path]);
                            } else {
                                $product->update(['image' => null]);
                            }
                        }
                    }),
            ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
}


protected static function handleMainImageSync(Model $productImage): void
    {
        if ($productImage->is_main) {
            if (!$productImage->relationLoaded('product')) {
            $productImage->load('product');
            }

            $product = $productImage->product;

            $product->images()
                ->where('id', '!=', $productImage->id)
                ->update(['is_main' => false]);
            
            // 2. Ürünün ana tablodaki image alanına bu görselin yolunu yaz
            $product->update([
                'image' => $productImage->path
            ]);
        }
    }

}
