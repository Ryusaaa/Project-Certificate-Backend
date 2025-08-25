<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CertificateGenerated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $recipientName,
        public string $certificateNumber,
        public string $downloadUrl,
        // pdfPath is the storage path (relative to disk) where worker will read the file
        public string $pdfPath,
        // optional filename for attachment
        public string $pdfFilename = 'certificate.pdf'
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Certificate Generated',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.certificate-generated',
            with: [
                'recipientName' => $this->recipientName,
                'certificateNumber' => $this->certificateNumber,
                'downloadUrl' => $this->downloadUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        // Attachment will be read at send time by the queue worker from storage
        if (Storage::disk('public')->exists($this->pdfPath)) {
            $data = Storage::disk('public')->get($this->pdfPath);
            return [
                Attachment::fromData(fn () => $data, $this->pdfFilename)->withMime('application/pdf')
            ];
        }

        return [];
    }
}
