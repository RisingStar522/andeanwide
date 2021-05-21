<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Traits\HasSaveDocument;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    use HasSaveDocument;

    public function attachDocumentToCompany(Request $request)
    {
        $company = Company::where('user_id', Auth::id())->firstOrFail();

        $request->validate([
            'title' => 'required',
            'description' => 'nullable',
            'document' => 'required|file|mimes:pdf|max:10240'
        ]);

        $url = $this->saveDocument($request->document, Auth::user(), 'documents');
        $company->documents()->create([
            'title' => $request->title,
            'description' => $request->description,
            'url' => $url
        ]);

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
