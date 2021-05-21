<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait HasSaveDocument {

    public function saveDocument($file, $user=null, $path=null, $prepend=null)
    {
        if(is_null($file)) return null;

        if(is_null($user) && is_null($path)) {
            $path = 'documents';
        } else if(isset($user) && is_null($path)) {
            $path = "documents/user/$user->id";
        } else if(isset($user) && isset($path)) {
            $path = "documents/user/$user->id/" . $path;
        }

        if(is_null($prepend)) {
            $name = time() . '.' . $file->getClientOriginalExtension();
        } else {
            $name = $prepend . time() . '.' . $file->getClientOriginalExtension();
        }

        $path = $file->storeAs($path, $name, 'public');
        return asset('') . 'storage/' . $path;
    }
}
