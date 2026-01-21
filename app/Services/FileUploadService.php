<?php

namespace App\Services;

class FileUploadService
{

    public function storeFile($model,$file,string $folder='uploads',string $column='image'){
        $path=$file->store($folder,'public');

        $model->{$column} = $path;
        $model->save();
    }

}
