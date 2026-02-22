<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrdineConfermaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $ordine;
    protected string $pdfBytes;
    protected string $filename;
    public string $viewName;
public string $utente;

    public function __construct($ordine, string $pdfBytes, string $filename , string $utente,string $viewName)
    {
        $this->ordine = $ordine;
        $this->pdfBytes = $pdfBytes;
        $this->filename = $filename;
        $this->utente = $utente;
        $this->viewName = $viewName;

    }

    public function build()
    {
        $n = $this->ordine->Nordine ?? $this->ordine->ID;

        return $this->subject("Conferma Ordine #{$n}")
            ->view( $this->viewName) // corpo email
            ->attachData($this->pdfBytes, $this->filename, [
                'mime' => 'application/pdf',
            ]);
    }
}
