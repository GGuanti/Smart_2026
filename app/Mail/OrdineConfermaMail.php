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

    public function __construct($ordine, string $pdfBytes, string $filename)
    {
        $this->ordine = $ordine;
        $this->pdfBytes = $pdfBytes;
        $this->filename = $filename;
    }

    public function build()
    {
        $n = $this->ordine->Nordine ?? $this->ordine->ID;

        return $this->subject("Conferma Ordine #{$n}")
            ->view('emails.ordine_conferma') // corpo email
            ->attachData($this->pdfBytes, $this->filename, [
                'mime' => 'application/pdf',
            ]);
    }
}
