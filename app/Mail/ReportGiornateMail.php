<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;


class ReportGiornateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $pdfPath;
    public $codCliente;
    public $dataInizio;
    public $dataFine;
    public $cliente;
    public $subjectLine;
    public $utenteNome;
    public $clienteCognome;
    public $clienteNome;

    public function __construct($pdfPath, $codCliente, $dataInizio = null, $dataFine = null, $cliente = null, $utenteNome = null, $clienteCognome = '', $clienteNome = '')
    {
        $this->pdfPath    = $pdfPath;
        $this->codCliente = $codCliente;
        $this->dataInizio = $dataInizio;
        $this->dataFine   = $dataFine;
        $this->cliente    = $cliente;
        $this->utenteNome = $utenteNome;
        $this->clienteCognome = $clienteCognome;
        $this->clienteNome    = $clienteNome;
    }
    public function build()
    {
        return $this->subject('Report Giornate')
            ->view('emails.report_giornate')
            ->with([
                'codCliente' => $this->codCliente,
                'dataInizio' => $this->dataInizio,
                'dataFine'   => $this->dataFine,
                'cliente'    => $this->cliente,
                'utenteNome'  => $this->utenteNome,
                'clienteCognome'  => $this->clienteCognome,
                'clienteNome'     => $this->clienteNome,
            ])
            ->attach($this->pdfPath, [
                'as'   => 'report_giornate.pdf',
                'mime' => 'application/pdf',
            ]);
    }
    public function envelope(): Envelope
    {
        /** @var null|string $addr */
        $addr = config('mail.from.address');
        /** @var null|string $name */
        $name = config('mail.from.name');

        $fromAddress = is_string($addr) && $addr !== '' ? $addr : 'no-reply@localhost';
        $fromName    = is_string($name) && $name !== '' ? $name : (string) config('app.name', 'Laravel');

        return new Envelope(
            subject: (string) $this->subjectLine,
            from: new Address($fromAddress, $fromName),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.report_giornate',
            with: []
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as('report_giornate.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
