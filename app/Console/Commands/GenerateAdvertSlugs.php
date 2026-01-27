<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Advert;
use Illuminate\Support\Str;
class GenerateAdvertSlugs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-advert-slugs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Advert::whereNull('slug')->orWhere('slug', '')->chunk(100, function ($adverts) {
            foreach ($adverts as $advert) {
                $advert->slug = Str::slug($advert->title) . '-' . $advert->id;
                $advert->save();
            }
        });
    
        $this->info('advert sluglar done');
    }
}
