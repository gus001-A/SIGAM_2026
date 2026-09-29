<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso por correo de una asignación (tarea u orden de mantenimiento).
 * Se envía de forma síncrona (sin cola) para no depender de que haya un
 * `queue:work` corriendo — es un correo puntual, no un envío masivo.
 */
class AvisoAsignacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $titulo,
        public string $cuerpo,
        public string $url,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->titulo);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.aviso-asignacion',
            with: [
                'titulo' => $this->titulo,
                'cuerpo' => $this->cuerpo,
                'url' => $this->url,
            ],
        );
    }
}
