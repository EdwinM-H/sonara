<?php

namespace App\Http\Controllers;

use App\Models\EntrepreneurProfile;
use App\Models\VerificationDocument;
use App\Notifications\VerificationStatusNotification;
use App\Services\Audit\AuditService;
use App\Services\Verification\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function __construct(
        protected VerificationService $verification,
        protected AuditService $audit,
    ) {
    }

    public function index()
    {
        $profile = auth()->user()->entrepreneurProfile;
        $documents = $profile->documents()->latest('uploaded_at')->get();
        $latest = $profile->latestDocument;

        $documentDays = $this->verification->documentDaysRemaining($profile);
        $validationDays = $this->verification->validationDaysRemaining($profile);

        return view('entrepreneur.documents.index', compact('profile', 'documents', 'latest', 'documentDays', 'validationDays'));
    }

    public function store(Request $request)
    {
        $profile = auth()->user()->entrepreneurProfile;

        $validated = $request->validate([
            'document' => [
                'required',
                'file',
                'max:5120',
                'mimes:pdf,jpg,jpeg,png,webp',
            ],
        ], [
            'document.required' => 'Selecciona un archivo para cargar.',
            'document.mimes' => 'Solo se permiten archivos PDF, JPG, PNG o WEBP.',
            'document.max' => 'El archivo no debe superar los 5 MB.',
        ]);

        if ($profile->verification_status === EntrepreneurProfile::VERIF_APROBADO) {
            return back()->withErrors(['document' => 'Tu cuenta ya está verificada.']);
        }

        if ($this->verification->isExpired($profile)) {
            return back()->withErrors(['document' => 'El plazo para cargar documentación ha vencido. Contacta con soporte.']);
        }

        $file = $validated['document'];
        $originalName = $file->getClientOriginalName();
        $storedName = Str::uuid().'.'.$file->getClientOriginalExtension();
        $cleanName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);

        $path = $file->storeAs(
            'documents/'.$profile->id,
            $storedName,
            ['disk' => 'local'],
        );

        $document = VerificationDocument::create([
            'entrepreneur_profile_id' => $profile->id,
            'document_type' => VerificationDocument::TYPE_CARNET,
            'original_name' => mb_substr($cleanName, 0, 190),
            'stored_name' => $storedName,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'status' => 'recibido',
            'uploaded_at' => now(),
        ]);

        $this->verification->registerDocument($profile, $document);

        $this->audit->log('document_uploaded', VerificationDocument::class, $document->id, 'Documento de acreditación cargado.');

        return back()->with('success', 'Tu documento fue cargado correctamente. El administrador lo revisará.');
    }

    public function show(VerificationDocument $document)
    {
        $this->authorize('view', $document);

        if (! Storage::disk('local')->exists($document->path)) {
            abort(404, 'El documento ya no está disponible.');
        }

        return Storage::disk('local')->download($document->path, $document->original_name);
    }
}